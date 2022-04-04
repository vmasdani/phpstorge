<?php

use App\Dataclasses\AdminPassphraseJson;
use App\Dataclasses\StorageJson;
use App\Helper;
use App\Models\Storage;
use App\Models\StorageRecord;
use App\Models\User;
use App\Protos\StorgeSyncRecord;
use App\Serde\UserJson;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Hidehalo\Nanoid\Client as NanoidClient;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Attributes\MapFrom;

$router->get('/protobuf-test', function () {
    $stor = new StorgeSyncRecord;
    $stor->setEmail('valianmasdani@gmail.com');

    return response($stor->serializeToJsonString())
        ->header('content-type', 'application/json');
});

$router->get(
    '/',
    function (Request $request) use ($router) {
        return view(
            'home',
            [
                'data' => json_encode(
                    [
                        'baseUrl' => env('BASE_URL'),

                        'googleOauthClientKey' => env('GOOGLE_OAUTH_CLIENT_KEY')
                    ]
                )
            ]
        );
    }
);

$router->group(['prefix' => 'admin'], function () use ($router) {
    $router->get(
        '/',
        function () use ($router) {
            return view(
                'admin',
                [
                    'data' =>
                    json_encode(
                        [
                            'data' => [
                                'users' =>  User::all(),
                                'baseUrl' => env('BASE_URL')
                            ],
                        ]
                    )
                ]

            );
        }
    );
});

// V2, use protobuf for sync
$router->group(['prefix' => 'api/v2'], function () use ($router) {
    $router->post('/sync', function (Request $r) {
        $bod = new StorgeSyncRecord();
        $bod->mergeFromJsonString($r->getContent());

        try {
            // Deserialize storage json
            $st = new StorgeSyncRecord();
            $st->mergeFromJsonString($r->getContent());

            $a = Helper::getInfoFromAuthV2(
                $r->header('auth-type'),
                $r->header('authorization')
            );

            // return $a;

            if ($a?->getEmail() != null && $a?->getEmail() != '') {
                $u = null;
                $foundUser = User::where('email', '=', $a->getEmail())->first();

                if ($foundUser) {
                    $u  = User::updateOrCreate(['id' => $foundUser?->id], (array) $u);
                } else {
                    $u = User::updateOrCreate(['id' => null], (array) ['email' => $a->email]);
                }

                $stRes = null;
                $foundSt = Storage::query()
                    ->where('key', '=', $st->getKey())
                    ->where('user_id', '=', $u?->id)
                    ->first();

                $st->setUserId($u->id);

                if ($foundSt) {
                    $st->setId($foundSt?->id);
                    $stRes = Storage::updateOrCreate(['id' => $foundSt?->id], (array) $st);
                } else {
                    $stRes = Storage::updateOrCreate(['id' => null], (array) $st);
                }

                // Synchronise with last updated at

                foreach (($st?->getStorageRecords() ?? []) as  $sr) {
                    /** @var App\Protos\StorageRecord */
                    $sr = $sr;
                    $sr->setStorageId($stRes?->id) ;

                    if ($sr?->id == null) {
                        StorageRecord::updateOrCreate(['id' => null], (array) $sr);
                    } else {
                        $foundSr = StorageRecord::where('id', '=', $sr->getId())->first();

                        if ($foundSr && ($foundSr?->updated ?? 0) < ($sr->getUpdated() ?? 0)) {
                            StorageRecord::updateOrCreate(['id' => $sr->getId()], (array) $sr);
                        }
                    }
                }

                $stRes?->storageRecords;

                return $stRes;
            } else {
                return response('Email is null', 500);
            }
        } catch (Exception $e) {
            return response('sync error' . $e, 500);
        }


        return response($bod->serializeToJsonString())
            ->header('content-type', 'application/json');
    });
});

$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->group(['prefix' => 'admin'], function () use ($router) {
        $router->post('/login', function (Request $request) use ($router) {
            $p = new AdminPassphraseJson;
            Helper::deserializeJsonFromString($request->getContent(), $p);

            if (env('JWT_SECRET') == '') {
                return response('JWT secret is null', status: 403);
            } else if ($p?->passphrase != env('ADMIN_PASSPHRASE')) {
                return response('Admin passphrase wrong', status: 403);
            } else {
                return JWT::encode(array(
                    [
                        'exp' => (time())  + 86400 * 365 * 10,
                        'admin' => true
                    ]
                ), env('JWT_SECRET'), 'HS256');
            }
        });

        $router->get('/users', function (Request $request) use ($router) {
            $a = Helper::getInfoFromAuth("jwt", $request->header('authorization'));

            if ($a->isAdmin) {
                return User::all()->map(
                    function ($u) {
                        $u->storages;

                        return $u;
                    }
                );
            } else {
                response('Unauthorized', status: 403);
            }
        });

        $router->post('/users-gen-api-key/{id}', function (
            Request $request,
            $id
        ) use ($router) {
            $a = Helper::getInfoFromAuth("jwt", $request->header('authorization'));

            if ($a->isAdmin) {
                $u = User::where('id', '=', $id)->first();

                if ($u) {
                    return User::updateOrCreate(
                        ['id' => $u?->id],
                        [
                            'api_key' => (new NanoidClient)->generateId(size: 32)
                        ]
                    );
                }
            } else {
                response('Unauthorized', status: 403);
            }
        });
    });


    $router->get('/generate',  function (Request $request) use ($router) {
        return (new NanoidClient())->generateId(size: 32);
    });

    $router->get('/storages',  function (Request $request) use ($router) {
        return Storage::all();
    });

    $router->post('/info',  function (Request $request) use ($router) {
        try {
            return json_encode(Helper::getInfoFromAuth(
                $request->header('auth-type'),
                $request->header('authorization')
            ),);
        } catch (Exception $e) {
            return response('GET error', 500);
        }
    });

    $router->post('/sync',  function (Request $request) use ($router) {
        try {
            // Deserialize storage json
            $st = new StorageJson;
            Helper::deserializeJsonFromString($request->getContent(), $st);

            // return;
            // return response()->json($request->header('authorization'));

            // return (
            //     $request->headers

            // );

            $a = Helper::getInfoFromAuth(
                $request->header('auth-type'),
                $request->header('authorization')
            );

            // return $a;

            if ($a?->email != null && $a?->email != '') {
                $u = null;
                $foundUser = User::where('email', '=', $a->email)->first();

                if ($foundUser) {
                    $u  = User::updateOrCreate(['id' => $foundUser?->id], (array) $u);
                } else {
                    $u = User::updateOrCreate(['id' => null], (array) ['email' => $a->email]);
                }

                $stRes = null;
                $foundSt = Storage::where('key', '=', $st?->key)->where('user_id', '=', $u?->id)->first();

                $st->user_id = $u?->id;

                if ($foundSt) {
                    $st->id = $foundSt?->id;
                    $stRes = Storage::updateOrCreate(['id' => $foundSt?->id], (array) $st);
                } else {
                    $stRes = Storage::updateOrCreate(['id' => null], (array) $st);
                }

                // Synchronise with last updated at

                foreach (($st?->storage_records ?? []) as $sr) {
                    // dd((array) $sr);
                    $sr->storage_id = $stRes?->id;

                    if ($sr?->id == null) {
                        StorageRecord::updateOrCreate(['id' => null], (array) $sr);
                    } else {
                        $foundSr = StorageRecord::where('id', '=', $sr?->id)->first();

                        if ($foundSr && ($foundSr?->updated ?? 0) < ($sr?->updated ?? 0)) {
                            StorageRecord::updateOrCreate(['id' => $sr?->id], (array) $sr);
                        }
                    }
                }

                $stRes?->storageRecords;

                return $stRes;
            } else {
                return response('Email is null', 500);
            }
        } catch (Exception $e) {
            return response('sync error' . $e, 500);
        }
    });

    $router->post('/sync-test-add',  function (Request $request) use ($router) {
        try {
            $a = Helper::getInfoFromAuth(
                $request->header('auth-type'),
                $request->header('authorization')
            );

            if ($a->email != null && $a->email != '') {
                $u = null;

                $foundUser = User::where('email', '=', $a->email)->first();

                if ($foundUser) {
                    $u = User::updateOrCreate(['id' => $foundUser->id], (array) $u);
                } else {
                    $u = User::updateOrCreate(['id' => null], (array) ['email' => $a->email]);
                }

                // Save storage
                $savedStorage = Storage::where('key', '=', 'abcde')->where('user_id', '=', $u?->id)->first();

                if ($savedStorage) {

                    for ($i = 0; $i < 10; $i++) {
                        StorageRecord::updateOrCreate(
                            ['id' => null],
                            [
                                'created' => round(microtime(true) * 1000),
                                'updated' => round(microtime(true) * 1000),
                                'deleted' => round(microtime(true) * 1000),

                                'uuid' => uniqid(),
                                'storage_id' => $savedStorage?->id,
                                'value' => json_encode(
                                    [
                                        'customer' => 'masdani',
                                        'friend' => 'bagus noel',
                                        'skills' => [
                                            [
                                                'uuid' => uniqid(),
                                                'name' => 'skill ' . uniqid()
                                            ],
                                            [
                                                'uuid' => uniqid(),
                                                'name' => 'another skill ' . uniqid()
                                            ],
                                        ]
                                    ]
                                )
                            ]
                        );
                    }

                    foreach (($u)->storages as $st) {
                        $st->storageRecords;
                    }


                    return $u;
                }
            } else {
                return response('Email is null', 500);
            }
        } catch (Exception $e) {
            return response('sync error' . $e, 500);
        }
    });



    $router->get('/users',  function (Request $request) use ($router) {
        return response()->json(User::orderBy('id', 'desc')->get());
    });

    $router->post('/users',  function (Request $request) use ($router) {
        $u = new UserJson;

        (new JsonMapperFactory)->bestFit()->mapObjectFromString(
            $request->getContent(),
            $u
        );

        return response()->json(User::updateOrCreate(['id' => $u->id], (array) $u));
    });
});
