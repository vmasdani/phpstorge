<?php

namespace App\Dataclasses;

use JsonMapper\Middleware\Attributes\MapFrom;

class GoogleResponseJson
{
    public ?string $email = null;
    public ?string $picture = null;

    #[MapFrom('given_name')]
    public ?string $givenName = null;

    #[MapFrom('family_name')]
    public ?string $familyName = null;
}
