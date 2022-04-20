<?php

use App\Dataclasses\AdminPassphraseJson;
use App\Dataclasses\StorageJson;
use App\Helper;
use App\Models\Storage;
use App\Models\StorageRecord;
use App\Models\User;
use App\Protos\StorageRecordProto;
use App\Protos\StorageRecordsProto;
use App\Protos\StorgeAuthInfoProto;
use App\Protos\StorageProto;
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
    $stor = new StorageProto();
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

function syncV2(Request $r, bool $sandbox)
{
    try {
        // Deserialize storage json
        $st = new StorageProto();
        $st->mergeFromJsonString($r->getContent());

        /** @var App\Protos\StorgeAuthInfoProto */
        $authInfo = (function () use ($st, $r, $sandbox) {
            if ($sandbox) {
                $a = new StorgeAuthInfoProto();
                $a->setEmail(
                    ($st->hasEmail() ? $st->getEmail() : '') .
                        '@sandbox'
                );
                return $a;
            } else {
                return Helper::getInfoFromAuthV2(
                    $r->header('auth-type'),
                    $r->header('authorization')
                );
            }
        })();


        // return $a;
        $decodedStorage = Storage::decode($st);
        $decodedStorage->sandbox = true;

        if ($authInfo?->getEmail() != null && $authInfo?->getEmail() != '') {
            /** @var User */
            $u = (function () use ($authInfo) {
                $foundUser = User::where('email', '=', $authInfo->getEmail())->first();

                if ($foundUser) {
                    return $foundUser;
                } else {
                    return User::updateOrCreate(['id' => null], (array) ['email' => $authInfo->getEmail()]);
                }
            })();

            // dd($u->id);

            /** @var Storage */
            $stRes = (function () use ($st, $u, $decodedStorage) {
                /** @var Storage  */
                $foundSt = Storage::query()
                    ->where(
                        'key',
                        '=',
                        ($st->hasKey()
                            ? $st->getKey()
                            : '')

                    )
                    ->where('user_id', '=', $u?->id)
                    ->first();

                // $st->setUserId($u->id);
                // dd($foundSt->toArray());
                if ($foundSt) {
                    return $foundSt;
                } else {
                    $decodedStorage->user_id = $u->id;
                    return Storage::updateOrCreate(['id' => null], $decodedStorage->toArray());
                }
            })();

            // Synchronise with last updated at

            foreach (($st?->getStorageRecords() ?? []) as  $srProto) {
                /** @var App\Models\StorageRecord */
                $sr = StorageRecord::decode($srProto);

                // dd($sr);
                $sr->storage_id = $stRes?->id;

                if ($sr?->id == null) {
                    StorageRecord::updateOrCreate(['id' => null], $sr->toArray());
                } else {
                    $foundSr = StorageRecord::where('id', '=', $sr->id)->first();

                    if ($foundSr && ($foundSr?->updated ?? 0) < ($sr->updated ?? 0)) {
                        StorageRecord::updateOrCreate(['id' => $sr->id], $sr->toArray());
                    }
                }
            }


            /** @var Storage */
            // dd($stRes->id);
            $savedStorage = Storage::query()->find($stRes->id);
            $savedStorage->storageRecords;

            // return response()->json($savedStorage);
            return response($stRes?->encode()->serializeToJsonString())
                ->header('content-type',  'application/json');
        } else {
            return response('failed to get email', 500);
        }
    } catch (Exception $e) {
        return response('sync error' . $e, 500);
    }


    // return response($bod->serializeToJsonString())
    //     ->header('content-type', 'application/json');
}


// V2, use protobuf for sync
$router->group(['prefix' => 'api/v2'], function () use ($router) {
    // Sandbox mode
    $router->post('/sandbox/sync', function (Request $r) {
        return syncV2($r, true);
    });

    $router->post('/sync', function (Request $r) {
        return syncV2($r, false);
    });

    $router->post('/genapikey', function (Request $r) {
        $auth = Helper::getInfoFromAuthV2(
            $r->header('auth-type'),
            $r->header('authorization')
        );

        if ($auth != null && $auth->hasEmail()) {
            /** @var User|null */
            $u = User::query()->where('email', $auth->getEmail())->first();

            if ($u) {

                if ($u->api_key == null) {
                    $u->api_key = uniqid($u->email, true);
                }

                User::updateOrCreate(
                    ['id' => $u->id],
                    $u->toArray()
                );

                return response($u->api_key)->header('content-type', 'application/json');
            } else {
                return response('Email not found')->status(500);
            }
        } else {
            return response('Error getting auth info')->status(500);
        }
    });


    $router->get('/test-storage-records-proto', function () {
        $v = new StorageRecordsProto;
        $v->setStorageRecords(StorageRecord::all()->map(function ($sr) {
            /** @var App\Models\StorageRecord */
            $sr = $sr;
            return $sr->encode();
        })->toArray());
        return response($v->serializeToJsonString())->header('content-type', 'application/json');
    });

    $router->get('/storage-records-all', function () {
        return StorageRecord::all();
    });
    $router->post('/storage-records-proto', function (Request $r) {
        $vx = new StorageRecordProto();
        $vx->mergeFromJsonString($r->getContent());

        $v = StorageRecord::decode($vx);
        return $v;
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
