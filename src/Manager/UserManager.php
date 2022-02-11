<?php

namespace App\Manager;

use App\Entity\User;

class UserManager
{
    public function create(string $login): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setCreatedAt();
        $user->setUpdatedAt();

        return $user;
    }
}
