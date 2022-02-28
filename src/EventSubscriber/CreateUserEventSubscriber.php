<?php

namespace App\EventSubscriber;

use App\Event\CreateUserEvent;
use App\Manager\UserManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateUserEventSubscriber implements EventSubscriberInterface
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CreateUserEvent::class => 'onCreateUser'
        ];
    }

    public function onCreateUser(CreateUserEvent $event): void
    {
        $this->userManager->saveUser($event->getLogin());
    }
}
