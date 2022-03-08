<?php

namespace App\Controller\Api\GetUsers\v4;

use App\Manager\UserManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller extends AbstractFOSRestController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    #[Rest\Get(path: '/api/v4/get-users')]
    public function getUsersAction(Request $request): Response
    {
        $perPage = $request->request->get('perPage');
        $page = $request->request->get('page');
        $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
        $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;

        return $this->handleView($this->view(['users' => $users], $code));
    }
}
