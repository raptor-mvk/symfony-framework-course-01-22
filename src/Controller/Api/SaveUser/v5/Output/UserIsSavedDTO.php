<?php

namespace App\Controller\Api\SaveUser\v5\Output;

use App\Entity\Traits\SafeLoadFieldsTrait;

class UserIsSavedDTO
{
    use SafeLoadFieldsTrait;

    public int $id;

    public string $login;

    public int $age;

    public bool $isActive;

    public function getSafeFields(): array
    {
        return ['id', 'login', 'age', 'isActive'];
    }
}
