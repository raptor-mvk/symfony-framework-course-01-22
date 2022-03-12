<?php

namespace App\Controller\Api\v1;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/v1/token')]
class TokenController extends AbstractController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[Route(path: '', methods: ['POST'])]
    public function getTokenAction(Request $request): Response
    {
        $user = $request->getUser();
        $password = $request->getPassword();
        if (!$user || !$password) {
            return new JsonResponse(['message' => 'Authorization required'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->authService->isCredentialsValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid password or username'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['token' => $this->authService->getToken($user)]);
    }
}
