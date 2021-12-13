<?php

namespace App\Models;

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

        'value', 'key', 'user_id'
    ];

    protected $casts = [
        // base model
        'id' => 'integer',
        'uuid' => 'string',
        'ext_created_by_id' => 'integer',
        'ordering' => 'integer',
        'hidden' => 'boolean',
        // base model
        'value' => 'string',
        'key' => 'string',
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function storageRecords()
    {
        return $this->hasMany(StorageRecord::class, 'storage_id');
    }
}
