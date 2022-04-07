<?php

namespace App\Models;

use App\Helper;
use App\Protos\BaseModel;
use App\Protos\StorageRecordProto;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class StorageRecord extends Model
{

    protected $table = 'storage_record';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // base model
        'id', 'uuid', 'ext_created_by_id', 'ordering',  'hidden',
        // base model end
        'storage_id',  'value', 'created', 'updated', 'deleted'
    ];

    public function  encode(): StorageRecordProto
    {
        $v = new StorageRecordProto();
        // base model
        $v->setBaseModel(new BaseModel());
        Helper::encodeBaseModel($this, $v->getBaseModel());

        // base model end
        if ($this->storage_id != null) {
            $v->setStorageId($this->storage_id);
        }
        if ($this->value != null) {
            $v->setValue($this->value);
        }
        if ($this->created != null) {
            $v->setCreated($this->created);
        }
        if ($this->updated != null) {
            $v->setUpdated($this->updated);
        }
        if ($this->deleted != null) {
            $v->setDeleted($this->deleted);
        }



        return $v;
    }
    public static function  decode(StorageRecordProto $vx): StorageRecord
    {
        $v = new StorageRecord();
        // base model
        if ($vx->hasBaseModel()) {
            Helper::decodeBaseModel($vx->getBaseModel(), $v);
        }
        // base model end
        if ($vx->hasStorageId()) {
            $v->storage_id = $vx->getStorageId();
        }
        if ($vx->hasValue()) {
            $v->value = $vx->getValue();
        }
        if ($vx->hasCreated()) {
            $v->created = $vx->getCreated();
        }
        if ($vx->hasUpdated()) {
            $v->updated = $vx->getUpdated();
        }
        if ($vx->hasDeleted()) {
            $v->deleted = $vx->getDeleted();
        }
        
        return $v;
    }



    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
}
