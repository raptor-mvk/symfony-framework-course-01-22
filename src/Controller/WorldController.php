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

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function hello(): Response
    {
        /** @var User $user */
        $user = $this->userManager->findUser(3);
        $userId = $user->getId();
        $this->userManager->updateUserLoginWithDBALQueryBuilder($userId, 'User is updated by DBAL');
        $this->userManager->clearEntityManager();
        $user = $this->userManager->findUser($userId);

        return $this->json($user->toArray());
    }
}
