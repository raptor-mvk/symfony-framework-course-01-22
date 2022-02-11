<?php

namespace App\Controller;

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
        $user = $this->userBuilderService->createUserWithTweets(
            'J.R.R. Tolkien',
            ['The Hobbit', 'The Lord of the Rings']
        );

        return $this->json($user->toArray());
    }
}
