<?php

namespace App\Controller\Api\SaveUser\v5;

use App\Controller\Api\SaveUser\v5\Input\SaveUserDTO;
use App\Controller\Api\SaveUser\v5\Output\UserIsSavedDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

class SaveUserManager
{
    private EntityManagerInterface $entityManager;

    private SerializerInterface $serializer;

    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function saveUser(SaveUserDTO $saveUserDTO): UserIsSavedDTO
    {
        $this->logger->debug('This is debug message');
        $this->logger->info('This is info message');
        $this->logger->notice('This is notice message');
        $this->logger->warning('This is warning message');
        $this->logger->error('This is error message');
        $this->logger->critical('This is critical message');
        $this->logger->alert('This is alert message');
        $this->logger->emergency('This is emergency message');
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
