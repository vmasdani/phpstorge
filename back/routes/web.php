<?php

use App\Helper;
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
                $u = User::where('email', '=', $a->email)->first();

                if ($u) {
                    return User::updateOrCreate(['id' => $u->id], (array) $u);
                } else {
                    return User::updateOrCreate(['id' => null], (array) ['email' => $a->email]);
                }
            } else {
                return response('Email is null', 500);
            }
        } catch (Exception $e) {
            return response('sync error', 500);
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
