<?php

namespace App;

use App\Dataclasses\AuthInfo;
use App\Dataclasses\GoogleResponseJson;
use App\Models\User;
use App\Protos\BaseModel;
use App\Protos\StorgeAuthInfoProto;
use Exception;
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
                try {
                    $g = new GoogleResponseJson;

                    Helper::deserializeJsonFromString(
                        (new Client())->getAsync('https://oauth2.googleapis.com/tokeninfo?id_token=' . $token)->wait()?->getBody()?->getContents(),
                        $g
                    );

                    $a = new AuthInfo;
                    $a->name = $g->givenName . ' ' . $g->familyName;
                    $a->email = $g->email;
                    $a->picture = $g->picture;

                    // Find api key from db
                    $u  = User::query()
                        ->where('email', $g->email)
                        ->first();

                    if ($u) {
                        $a->apiKey = $u->api_key;
                    }

                    return $a;
                } catch (Exception $e) {
                    return null;
                }

            case 'facebook':
                // TODO: to be implemented
                return null;

            case 'jwt':
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

                $a = new AuthInfo;
                $a->isAdmin = count($decoded) > 0 ? $decoded[0]?->admin : false;

                return $a;
            case 'api_key':
                try {
                    // Find user from db
                    $u  = User::query()
                        ->where('email', explode(':', $token)[0])
                        ->first();

                    // Check user and  validate api key
                    if ($u && $u->api_key == $token) {
                        $a = new AuthInfo;
                        $a->name = $u->name;
                        $a->email = $u->email;
                        // $a->picture = $g->picture;
                        $a->apiKey = $u->api_key;

                        return $a;
                    } else {
                        return null;
                    }
                } catch (Exception $e) {
                    return null;
                }

            default:
                return null;
        }
    }

    static function getInfoFromAuthV2(?string $authType, ?string $token,): ?StorgeAuthInfoProto
    {
        switch ($authType) {
            case 'google':
                try {
                    $g = new GoogleResponseJson;

                    Helper::deserializeJsonFromString(
                        (new Client())->getAsync('https://oauth2.googleapis.com/tokeninfo?id_token=' . $token)->wait()?->getBody()?->getContents(),
                        $g
                    );

                    $a = new StorgeAuthInfoProto();
                    $a->setName($g->givenName . ' ' . $g->familyName);
                    $a->setEmail($g->email);
                    $a->setPicture($g->picture);

                    return $a;
                } catch (Exception $e) {
                    return null;
                }

            case 'facebook':
                // TODO: to be implemented
                return null;

            case 'jwt':
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

                $a = new StorgeAuthInfoProto();
                $a->setIsAdmin(
                    count($decoded) > 0
                        ? $decoded[0]?->admin
                        : false
                );

                return $a;

            case 'api_key':
                try {
                    // Find user from db
                    $u  = User::query()
                        ->where('email', explode(':', $token)[0])
                        ->first();


                    // Check user and  validate api key
                    if ($u && $u->api_key == $token) {
                        // dd($u->api_key); 

                        $a = new StorgeAuthInfoProto;

                        if ($u->name != null) {
                            $a->setName($u->name);
                        }
                        if ($u->email != null) {
                            $a->setEmail($u->email);
                        }
                        if ($u->api_key != null) {
                            $a->setApiKey($u->api_key);
                        }

                        return $a;
                    } else {
                        return null;
                    }
                } catch (Exception $e) {
                    return null;
                }
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

    public static function encodeBaseModel(mixed $v, BaseModel $vx)
    {
        if ($v->id !=  null) {
            $vx->setId($v->id);
        }
        if ($v->uuid !=  null) {
            $vx->setUuid($v->uuid);
        }
        if ($v->ext_created_by_id !=  null) {
            $vx->setExtCreatedById($v->ext_created_by_id);
        }
        if ($v->ordering !=  null) {
            $vx->setOrdering($v->ordering);
        }
        if ($v->hidden !=  null) {
            $vx->setHidden($v->hidden);
        }
        if ($v->created_at !=  null) {
            $vx->setCreatedAt($v->created_at->format(DATE_ATOM));
        }
        if ($v->updated_at !=  null) {
            $vx->setUpdatedAt($v->updated_at->format(DATE_ATOM));
        }
    }
    public static function decodeBaseModel(BaseModel $v, mixed $vx)
    {
        if ($v->hasId()) {
            $vx->id = $v->getId();
        }
        if ($v->hasUuid()) {
            $vx->uuid = $v->getUuid();
        }
        if ($v->hasExtCreatedById()) {
            $vx->ext_created_by_id = $v->getExtCreatedById();
        }
        if ($v->hasOrdering()) {
            $vx->ordering = $v->getOrdering();
        }
        if ($v->hasHidden()) {
            $vx->hidden = $v->getHidden();
        }
        if ($v->hasCreatedAt()) {
            $vx->created_at = $v->getCreatedAt();
        }
        if ($v->hasUpdatedAt()) {
            $vx->updated_at = $v->getUpdatedAt();
        }
    }
}
