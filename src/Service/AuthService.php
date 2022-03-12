<?php

namespace App\Service;

use App\Manager\UserManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private UserManager $userManager;

    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserManager $userManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->userManager = $userManager;
        $this->passwordHasher = $passwordHasher;
    }

    public function isCredentialsValid(string $login, string $password): bool
    {
        $user = $this->userManager->findUserByLogin($login);
        if ($user === null) {
            return false;
        }

        return $this->passwordHasher->isPasswordValid($user, $password);
    }

    public function getToken(string $login): ?string
    {
        return $this->userManager->updateUserToken($login);
    }
}
