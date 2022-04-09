<?php

namespace App\Models;

use App\Helper;
use App\Protos\BaseModel;
use App\Protos\StorageProto;
use App\Protos\StorageRecordProto;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\RepeatedFieldIter;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Storage extends Model
{

    protected $table = 'storage';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // base model
        'id', 'uuid', 'ext_created_by_id', 'ordering',  'hidden',
        // base model end

        'value', 'key', 'user_id', 'sandbox'
    ];

    public function encode()
    {
        $v = new StorageProto();
        // base model
        $v->setBaseModel(new BaseModel());
        Helper::encodeBaseModel($this, $v->getBaseModel());
        // base model end
        if ($this->value != null) {
            $v->setValue($this->value);
        }
        if ($this->key != null) {
            $v->setKey($this->key);
        }
        if ($this->user_id != null) {
            $v->setUserId($this->user_id);
        }
        if ($this->sandbox != null) {
            $v->setSandbox($this->sandbox);
        }
        if ($this->storageRecords != null) {
            $v->setStorageRecords($this->storageRecords->map(function ($sr) {
                return $sr->encode();
            })->toArray());
        }

        return $v;
    }
    public static function decode(StorageProto $vx): Storage
    {
        $v = new Storage();
        // base model
        if ($vx->hasBaseModel()) {
            Helper::decodeBaseModel($vx->getBaseModel(), $v);
        }
        // base model end
        if ($vx->hasValue()) {
            $v->value = $vx->getValue();
        }
        if ($vx->hasKey()) {
            $v->key = $vx->getKey();
        }
        if ($vx->hasUserId()) {
            $v->user_id = $vx->getUserId();
        }
        if ($vx->hasSandbox()) {
            $v->sandbox = $vx->getSandbox();
        }
        if ($vx->getStorageRecords()) {
            $v->storage_records = array_map(function ($sr) {
                return StorageRecord::decode($sr);
            }, iterator_to_array($vx->getStorageRecords()));
        }

        return $v;
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function storageRecords()
    {
        return $this->hasMany(StorageRecord::class, 'storage_id');
    }
}
