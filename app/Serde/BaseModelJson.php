<?php

namespace App\Serde;

use JsonMapper\Middleware\Attributes\MapFrom;

class BaseModelJson
{
    public ?int $id = null;
    public ?string $uuid = null;

    #[MapFrom("extCreatedById")]
    public ?int $ext_created_by_id = null;
    public ?int $ordering  = null;
    public ?int $hidden = null;
}
