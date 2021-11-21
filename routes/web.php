<?php

use App\Models\User;
use App\Serde\UserJson;
use Illuminate\Http\Request;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Attributes\MapFrom;


$router->post('/testobject', function (Request $request) use ($router) {
    $user = new UserJson;

    (new JsonMapperFactory)->bestFit()->mapObjectFromString(
        $request->getContent(),
        $user
    );

    return response(json_encode($user), 200, ['content-type' => 'json']);
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
