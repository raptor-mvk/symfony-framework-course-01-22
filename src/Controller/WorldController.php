<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserBuilderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorldController extends AbstractController
{
    private UserBuilderService $userBuilderService;

    public function __construct(UserBuilderService $userBuilderService)
    {
        $this->userBuilderService = $userBuilderService;
    }

    public function hello(): Response
    {
        $users = $this->userBuilderService->createUserWithFollower(
            'J.R.R. Tolkien',
            'Ivan Ivanov'
        );

        return $this->json(array_map(static fn(User $user) => $user->toArray(), $users));
    }
}
