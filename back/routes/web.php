<?php

use App\Helper;
use App\Models\Storage;
use App\Models\StorageRecord;
use App\Models\User;
use App\Serde\UserJson;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Attributes\MapFrom;


$router->get('/', function (Request $request) use ($router) {
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
});


$router->group(['prefix' => 'api/v1'], function () use ($router) {
    $router->get('/storages',  function (Request $request) use ($router) {
        return Storage::all();
    });

    $router->post('/info',  function (Request $request) use ($router) {
        try {
            return json_encode(Helper::getInfoFromAuth(
                $request->header('auth_type'),
                $request->header('authorization')
            ),);
        } catch (Exception $e) {
            return response('GET error', 500);
        }
    });

    $router->post('/sync',  function (Request $request) use ($router) {
        try {
            $a = Helper::getInfoFromAuth(
                $request->header('auth_type'),
                $request->header('authorization')
            );

            if ($a->email != null && $a->email != '') {
                $u = null;
                $foundUser = User::where('email', '=', $a->email)->first();

                if ($foundUser) {
                    $u = User::updateOrCreate(['id' => $foundUser?->id], (array) $u);
                } else {
                    $u = User::updateOrCreate(['id' => null], (array) ['email' => $a->email]);
                }

                foreach ($u?->storages as $s) {
                    $s?->storageRecords;
                }

                return $u;
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
                $request->header('auth_type'),
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
                $savedStorage = Storage::updateOrCreate(['id' => null], [
                    'key' => uniqid(),
                    'user_id' => $u?->id
                ]);


                for ($i = 0; $i < 10; $i++) {
                    StorageRecord::updateOrCreate(
                        ['id' => null],
                        [
                            'created' => round(microtime(true) * 1000),
                            'updated' => round(microtime(true) * 1000),
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
