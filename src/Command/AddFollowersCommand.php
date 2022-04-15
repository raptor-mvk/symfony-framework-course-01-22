<?php

namespace App\Command;

use App\Service\SubscriptionService;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddFollowersCommand extends Command
{
    private const DEFAULT_FOLLOWERS = 100;

    private UserManager $userManager;

    private SubscriptionService $subscriptionService;

    public function __construct(UserManager $userManager, SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->subscriptionService = $subscriptionService;
    }

    protected function configure(): void
    {
        $this->setName('followers:add')
            ->setDescription('Adds followers to author')
            ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
            ->addArgument('count', InputArgument::OPTIONAL, 'How many followers should be added');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authorId = (int)$input->getArgument('authorId');
        $user = $this->userManager->findUserById($authorId);
        if ($user === null) {
            $output->write("<error>User with ID $authorId doesn't exist</error>\n");
            return self::FAILURE;
        }
        $count = (int)($input->getArgument('count') ?? self::DEFAULT_FOLLOWERS);
        if ($count < 0) {
            $output->write("<error>Count should be positive integer</error>\n");
            return self::FAILURE;
        }
        $result = $this->subscriptionService->addFollowers($user, "Reader #{$authorId}", $count);
        $output->write("<info>$result followers were created</info>\n");
        return self::SUCCESS;
    }
}
