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
        /** @var User $user */
        $user = $this->userManager->findUser(3);
        $this->userManager->updateUserLoginWithQueryBuilder($user->getId(), 'User is updated');

        return $this->json($user->toArray());
    }
}
