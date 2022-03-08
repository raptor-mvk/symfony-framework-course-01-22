<?php

namespace App\Controller\Api\SaveUser\v5;

use App\Controller\Api\SaveUser\v5\Input\SaveUserDTO;
use App\Controller\Api\SaveUser\v5\Output\UserIsSavedDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;

class SaveUserManager
{
    private EntityManagerInterface $entityManager;

    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    public function saveUser(SaveUserDTO $saveUserDTO): UserIsSavedDTO
    {
        $user = new User();
        $user->setLogin($saveUserDTO->login);
        $user->setPassword($saveUserDTO->password);
        $user->setRoles($saveUserDTO->roles);
        $user->setAge($saveUserDTO->age);
        $user->setIsActive($saveUserDTO->isActive);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $result = new UserIsSavedDTO();
        $context = (new SerializationContext())->setGroups(['user1', 'user2']);
        $result->loadFromJsonString($this->serializer->serialize($user, 'json', $context));

        return $result;
    }

}
