<?php

namespace App\Controller\Api\v1;

use App\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/v1/user')]
class UserController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    #[Route(path: '', methods: ['POST'])]
    public function saveUserAction(Request $request): Response
    {
        $login = $request->request->get('login');
        $userId = $this->userManager->saveUser($login);
        [$data, $code] = $userId === null ?
            [['success' => false], 400] :
            [['success' => true, 'userId' => $userId], 200];

        return new JsonResponse($data, $code);
    }

    #[Route(path: '', methods: ['GET'])]
    public function getUsersAction(Request $request): Response
    {
        $perPage = $request->query->get('perPage');
        $page = $request->query->get('page');
        $users = $this->userManager->getUsers($page ?? 0, $perPage ?? 20);
        $code = empty($users) ? 204 : 200;

        return new JsonResponse(['users' => $users], $code);
    }

    #[Route(path: '', methods: ['DELETE'])]
    public function deleteUserAction(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $result = $this->userManager->deleteUser($userId);

        return new JsonResponse(['success' => $result], $result ? 200 : 404);
    }

    #[Route(path: '', methods: ['PATCH'])]
    public function updateUserAction(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $login = $request->query->get('login');
        $result = $this->userManager->updateUser($userId, $login);

        return new JsonResponse(['success' => $result], $result ? 200 : 404);
    }

    #[Route(path: '/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUserByIdAction(int $id): Response
    {
        $result = $this->userManager->deleteUser($id);

        return new JsonResponse(['success' => $result], $result ? 200 : 404);
    }
}
