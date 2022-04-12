<?php

namespace App\Controller\Api\GetUsersByQuery\v1;

use App\Manager\UserManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    #[Rest\Get('/api/v1/get-users-by-query')]
    #[QueryParam(name: 'query')]
    #[QueryParam(name: 'perPage', requirements: '\d+')]
    #[QueryParam(name: 'page', requirements: '\d+')]
    public function getUsersByQueryAction(string $query, int $perPage, int $page): Response
    {
        return $this->handleView($this->view($this->userManager->findUserByQuery($query, $perPage, $page), 200));
    }
}
