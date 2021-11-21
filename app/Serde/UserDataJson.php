<?php

namespace App\Serde;

use JsonMapper\Middleware\Attributes\MapFrom;

class UserDataJson extends BaseModelJson
{
    #[MapFrom("userId")]
    public ?int $userId = null;
    public ?string $key = null;
    public ?string $value = null;
}