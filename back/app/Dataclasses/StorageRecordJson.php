<?php

namespace App\Dataclasses;

use JsonMapper\Middleware\Attributes\MapFrom;

class StorageRecordJson extends BaseModelJson
{
    #[MapFrom("storageId")]
    public mixed $storage_id = null;
    // public ?string $key = null;
    public ?string $value = null;
    public mixed $created = null;
    public mixed $updated = null;
    public mixed $deleted = null;
    
}
