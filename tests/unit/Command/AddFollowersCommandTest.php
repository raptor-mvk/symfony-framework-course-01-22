<?php

namespace CodeceptionUnitTests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use UnitTests\FixturedTestCase;
use UnitTests\Fixtures\MultipleUsersFixture;

class AddFollowersCommandTest extends FixturedTestCase
{
    private const COMMAND = 'followers:add';

    /** @var Application */
    private static $application;

    public function setUp(): void
    {
        parent::setUp();

        self::$application = new Application(self::$kernel);
        $this->addFixture(new MultipleUsersFixture());
    }

    public function executeDataProvider(): array
    {
        return [
            'positive' => [20, "20 followers were created\n"],
            'zero' => [0, "0 followers were created\n"],
            'default' => [null, "100 followers were created\n"],
            'negative' => [-1, "Count should be positive integer\n"],
        ];
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteReturnsResult(?int $followersCount, string $expected): void
    {
        $command = self::$application->find(self::COMMAND);
        $commandTester = new CommandTester($command);
        $userService = self::$container->get('App\Service\UserService');
        $author = $userService->findByLogin(MultipleUsersFixture::PRATCHETT);
        $params = ['authorId' => $author->getId()];
        $inputs = $followersCount === null ? ["\n"] : ["$followersCount\n"];
        $commandTester->setInputs($inputs);
        $commandTester->execute($params);
        $output = $commandTester->getDisplay();
        static::assertStringEndsWith($expected, $output);
    }
}
