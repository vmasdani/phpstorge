<?php

namespace App\Models;

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

    protected $casts = [
        // base model
        'id' => 'integer',
        'uuid' => 'string',
        'ext_created_by_id' => 'integer',
        'ordering' => 'integer',
        'hidden' => 'boolean',
        // base model
        'storage_id' => 'integer',
        'value' => 'string',
        'created' => 'integer',
        'updated' => 'integer',
        'deleted' => 'integer',
        
    ];


    public function storage()
    {
        return $this->belongsTo(Storage::class, 'storage_id');
    }
}
