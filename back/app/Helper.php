<?php

namespace App;

use App\Dataclasses\AuthInfo;
use App\Dataclasses\GoogleResponseJson;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use JsonMapper\Cache\NullCache;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Attributes\Attributes;
use JsonMapper\Middleware\DocBlockAnnotations;
use JsonMapper\Middleware\TypedProperties;

class Helper
{
    static function getInfoFromAuth(?string $authType, ?string $token,): ?AuthInfo
    {
        switch ($authType) {
            case 'google':
                $g = new GoogleResponseJson;

                Helper::deserializeJsonFromString(
                    (new Client())->getAsync('https://oauth2.googleapis.com/tokeninfo?id_token=' . $token)->wait()?->getBody()?->getContents(),
                    $g
                );

                $a = new AuthInfo;
                $a->name = $g->givenName . ' ' . $g->familyName;
                $a->email = $g->email;
                $a->picture = $g->picture;

                return $a;

            case 'facebook':
                // TODO: to be implemented
                return null;
            case 'jwt':
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));


                $a = new AuthInfo;
                $a->isAdmin = count($decoded) > 0 ? $decoded[0]?->admin : false;

                return $a;
            default:
                return null;
        }
    }

    static function deserializeJsonFromString(?string $str, $obj)
    {
        (new JsonMapperFactory())->create(
            new PropertyMapper(),
            new Attributes(),
            new TypedProperties(new NullCache),
            new DocBlockAnnotations(new NullCache)
        )->mapObjectFromString($str, $obj);
    }
}
