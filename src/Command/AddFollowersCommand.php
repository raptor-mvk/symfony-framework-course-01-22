<?php

namespace App\Command;

use App\DTO\SaveUserDTO;
use App\Entity\User;
use App\Service\SubscriptionService;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AddFollowersCommand extends Command
{
    use LockableTrait;

    public const FOLLOWERS_ADD_COMMAND_NAME = 'followers:add';

    private const DEFAULT_FOLLOWERS = 100;
    private const DEFAULT_LOGIN_PREFIX = 'Reader #';

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
        $this->setName(self::FOLLOWERS_ADD_COMMAND_NAME)
            ->setHidden(true)
            ->setDescription('Adds followers to author')
            ->addArgument('authorId', InputArgument::REQUIRED, 'ID of author')
            ->addOption('login', 'l', InputOption::VALUE_REQUIRED, 'Follower login prefix');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $authorId = (int)$input->getArgument('authorId');
        $user = $this->userManager->findUserById($authorId);
        if ($user === null) {
            $output->write("<error>User with ID $authorId doesn't exist</error>\n");
            return self::FAILURE;
        }
        $helper = $this->getHelper('question');
        $question = new Question('How many followers you want to add?', self::DEFAULT_FOLLOWERS);
        $count = (int)$helper->ask($input, $output, $question);
        if ($count < 0) {
            $output->write("<error>Count should be positive integer</error>\n");
            return self::FAILURE;
        }
        $login = $input->getOption('login') ?? self::DEFAULT_LOGIN_PREFIX;
        $result = $this->subscriptionService->addFollowers($user, $login.$authorId, $count);
        $output->write("<info>$result followers were created</info>\n");

        return self::SUCCESS;
    }
}
