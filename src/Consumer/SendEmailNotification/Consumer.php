<?php

namespace App\Consumer\SendEmailNotification;

use App\Consumer\SendEmailNotification\Input\Message;
use App\Entity\User;
use App\Manager\EmailNotificationManager;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Consumer implements ConsumerInterface
{
    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    private EmailNotificationManager $emailNotificationManager;

    public function __construct(EntityManagerInterface $entityManager, ValidatorInterface $validator, EmailNotificationManager $emailNotificationManager)
    {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->emailNotificationManager = $emailNotificationManager;
    }

    public function execute(AMQPMessage $msg): int
    {
        try {
            $message = Message::createFromQueue($msg->getBody());
            $errors = $this->validator->validate($message);
            if ($errors->count() > 0) {
                return $this->reject((string)$errors);
            }
        } catch (JsonException $e) {
            return $this->reject($e->getMessage());
        }

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($message->getUserId());
        if (!($user instanceof User)) {
            return $this->reject(sprintf('User ID %s was not found', $message->getUserId()));
        }

        $this->emailNotificationManager->saveEmailNotification($user->getEmail(), $message->getText());

        $this->entityManager->clear();
        $this->entityManager->getConnection()->close();

        return self::MSG_ACK;
    }

    private function reject(string $error): int
    {
        echo "Incorrect message: $error";

        return self::MSG_REJECT;
    }
}
