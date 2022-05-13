<?php

namespace App\Controller\Api\AddFollowers\v1;

use App\DTO\AddFollowersDTO;
use App\Manager\UserManager;
use App\Service\SubscriptionService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class Controller extends AbstractFOSRestController
{
    private SubscriptionService $subscriptionService;

    private UserManager $userManager;

    private MessageBusInterface $messageBus;

    public function __construct(SubscriptionService $subscriptionService, UserManager $userManager, MessageBusInterface $messageBus)
    {
        $this->subscriptionService = $subscriptionService;
        $this->userManager = $userManager;
        $this->messageBus = $messageBus;
    }

    #[Rest\Post(path: '/api/v1/add-followers')]
    #[RequestParam(name: 'userId', requirements: '\d+')]
    #[RequestParam(name: 'followersLogin')]
    #[RequestParam(name: 'count', requirements: '\d+')]
    #[RequestParam(name: 'async', requirements: '0|1')]
    public function addFollowersAction(int $userId, string $followersLogin, int $count, int $async): Response
    {
        $user = $this->userManager->findUserById($userId);
        if ($user !== null) {
            if ($async === 0) {
                $createdFollowers = $this->subscriptionService->addFollowers($user, $followersLogin, $count);
                $view = $this->view(['created' => $createdFollowers], 200);
            } else {
                for ($i = 0; $i < $count; $i++) {
                    $this->messageBus->dispatch(new AddFollowersDTO($user->getId(), "$followersLogin #$i", 1));
                }
                $view = $this->view(['success' => true], 200);
            }
        } else {
            $view = $this->view(['success' => false], 404);
        }

        return $this->handleView($view);
    }
}
