<?php

namespace App\Controller\Api\GetUsersByQueryWithAggregation\v1;

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

    #[Rest\Get('/api/v1/get-users-by-query-with-aggregation')]
    #[QueryParam(name: 'query')]
    #[QueryParam(name: 'field')]
    public function getUsersByQueryWithAggregationAction(string $query, string $field): Response
    {
        return $this->handleView($this->view($this->userManager->findUserByQueryWithAggregation($query, $field), 200));
    }
}
