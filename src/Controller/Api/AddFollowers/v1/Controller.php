<?php

namespace App\Controller\Api\AddFollowers\v1;

use App\Manager\UserManager;
use App\Service\SubscriptionService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    private SubscriptionService $subscriptionService;

    private UserManager $userManager;

    public function __construct(SubscriptionService $subscriptionService, UserManager $userManager)
    {
        $this->subscriptionService = $subscriptionService;
        $this->userManager = $userManager;
    }

    #[Rest\Post(path: '/api/v1/add-followers')]
    #[RequestParam(name: 'userId', requirements: '\d+')]
    #[RequestParam(name: 'followersLogin')]
    #[RequestParam(name: 'count', requirements: '\d+')]
    public function addFollowersAction(int $userId, string $followersLogin, int $count): Response
    {
        $user = $this->userManager->findUserById($userId);
        if ($user !== null) {
            $createdFollowers = $this->subscriptionService->addFollowers($user, $followersLogin, $count);
            $view = $this->view(['created' => $createdFollowers], 200);
        } else {
            $view = $this->view(['success' => false], 404);
        }

        return $this->handleView($view);
    }
}
