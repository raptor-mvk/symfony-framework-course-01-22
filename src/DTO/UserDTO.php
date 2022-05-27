<?php

namespace App\DTO;

class UserDTO
{
    public string $login;

    public ?string $email;

    public ?string $phone;

    public array $followers;

    public array $followed;
}
