<?php

namespace App\Dataclasses;

use JsonMapper\Middleware\Attributes\MapFrom;

class StorageJson extends BaseModelJson
{
    public ?string $value = null;
    public ?string $key = null;

    #[MapFrom("userId")]
    public ?int $user_id = null;

    /**
     * @var App\Dataclasses\StorageRecordJson[]
     */
    #[MapFrom("storageRecords")]
    public ?array $storage_records = [];
}
