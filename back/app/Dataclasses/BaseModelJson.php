<?php

namespace App\Dataclasses;

use JsonMapper\Middleware\Attributes\MapFrom;

class BaseModelJson
{
    // Base model
    public mixed $id = null;
    public ?string $uuid = null;

    #[MapFrom("extCreatedById")]
    public ?int $ext_created_by_id = null;

    public ?int $ordering = null;
    public ?int $hidden = null;
    // Base model end   

    
}
