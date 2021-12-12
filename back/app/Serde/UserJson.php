<?php

namespace App\Serde;

use JsonMapper\Middleware\Attributes\MapFrom;

class UserJson extends BaseModelJson
{
    public ?string $email = null;
    #[MapFrom("apiKey")]
    public ?string $api_key = null;
}
