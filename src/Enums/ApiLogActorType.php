<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Enums;

enum ApiLogActorType: string
{
    case Guest = 'guest';
    case User = 'user';
    case ApiUser = 'api_user';
}
