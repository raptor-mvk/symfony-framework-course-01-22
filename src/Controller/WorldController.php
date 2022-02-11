<?php

namespace App\Controller;

use App\Manager\UserManager;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorldController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @throws JsonException
     */
    public function hello(): Response
    {
        $user = $this->userManager->create('My user');

        return $this->json($user->toArray());
    }
}
