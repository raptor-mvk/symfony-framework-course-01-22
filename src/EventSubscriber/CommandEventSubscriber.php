<?php

namespace App\EventSubscriber;

use App\Command\AddFollowersCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => [['onCommand', 0]],
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command !== null && $command->getName() === AddFollowersCommand::FOLLOWERS_ADD_COMMAND_NAME) {
            $input = $event->getInput();
            $output = $event->getOutput();
            $helper = $command->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure want to execute this command?(y/n)', false);
            if (!$helper->ask($input, $output, $question)) {
                $event->disableCommand();
            }
        }
    }
}
