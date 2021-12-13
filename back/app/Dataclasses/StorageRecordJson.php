<?php

namespace App\Dataclasses;

use JsonMapper\Middleware\Attributes\MapFrom;

class StorageRecordJson extends BaseModelJson
{
    #[MapFrom("storageId")]
    public ?int $storage_id = null;
    // public ?string $key = null;
    public ?string $value = null;
    public ?int $created = null;
    public ?int $updated = null;
    public ?int $deleted = null;
    
}
