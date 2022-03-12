<?php

namespace App\Service;

use App\Manager\UserManager;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthService
{
    private UserManager $userManager;

    private UserPasswordHasherInterface $passwordHasher;

    private JWTEncoderInterface $jwtEncoder;

    private int $tokenTTL;

    public function __construct(UserManager $userManager, UserPasswordHasherInterface $passwordHasher, JWTEncoderInterface $jwtEncoder, int $tokenTTL)
    {
        $this->userManager = $userManager;
        $this->passwordHasher = $passwordHasher;
        $this->jwtEncoder = $jwtEncoder;
        $this->tokenTTL = $tokenTTL;
    }

    public function isCredentialsValid(string $login, string $password): bool
    {
        $user = $this->userManager->findUserByLogin($login);
        if ($user === null) {
            return false;
        }

        return $this->passwordHasher->isPasswordValid($user, $password);
    }

    public function getToken(string $login): string
    {
        $tokenData = ['username' => $login, 'exp' => time() + $this->tokenTTL];

        return $this->jwtEncoder->encode($tokenData);
    }
}
