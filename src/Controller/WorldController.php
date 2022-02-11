<?php

namespace App\Controller;

use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorldController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function hello(): Response
    {
        $users = $this->userManager->findUsersByLogin('Ivan Ivanov');

        return $this->json(array_map(static fn(User $user) => $user->toArray(), $users));
    }
}
