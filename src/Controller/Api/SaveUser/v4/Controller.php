<?php

namespace App\Controller\Api\SaveUser\v4;

use App\Entity\User;
use App\Manager\UserManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use Symfony\Component\HttpFoundation\Response;
use App\DTO\SaveUserDTO;

class Controller extends AbstractFOSRestController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    #[Rest\Post(path: '/api/v4/save-user')]
    #[RequestParam(name: 'login')]
    #[RequestParam(name: 'password')]
    #[RequestParam(name: 'roles')]
    #[RequestParam(name: 'age', requirements: '\d+')]
    #[RequestParam(name: 'login', requirements: 'true|false')]
    public function saveUserAction(string $login, string $password, string $roles, string $age, string $isActive): Response
    {
        $userDTO = new SaveUserDTO([
                'login' => $login,
                'password' => $password,
                'roles' => json_decode($roles, true, 512, JSON_THROW_ON_ERROR),
                'age' => (int)$age,
                'isActive' => $isActive === 'true']
        );
        $userId = $this->userManager->saveUserFromDTO(new User(), $userDTO);
        [$data, $code] = ($userId === null) ? [['success' => false], 400] : [['id' => $userId], 200];
        return $this->handleView($this->view($data, $code));
    }
}
