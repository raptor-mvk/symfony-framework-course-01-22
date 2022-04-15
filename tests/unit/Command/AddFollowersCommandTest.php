<?php

namespace UnitTests\Command;

use App\Manager\UserManager;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Mockery;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use UnitTests\FixturedTestCase;
use UnitTests\Fixtures\MultipleUsersFixture;

class AddFollowersCommandTest extends FixturedTestCase
{
    private const COMMAND = 'followers:add';

    private static Application $application;

    public function setUp(): void
    {
        parent::setUp();

        self::$application = new Application(self::$kernel);
        $this->addFixture(new MultipleUsersFixture());
        $this->executeFixtures();
    }

    public function executeDataProvider(): array
    {
        return [
            'positive' => [20, 'login', "20 followers were created\n"],
            'zero' => [0, 'other_login', "0 followers were created\n"],
            'default' => [null, 'login3', "100 followers were created\n"],
            'negative' => [-1, 'login_too', "Count should be positive integer\n"],
        ];
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecuteReturnsResult(?int $followersCount, string $login, string $expected): void
    {
        $command = self::$application->find(self::COMMAND);
        $commandTester = new CommandTester($command);

        /** @var UserPasswordHasherInterface $encoder */
        $encoder = self::getContainer()->get('security.password_hasher');
        /** @var PaginatedFinderInterface $finder */
        $finder = Mockery::mock(PaginatedFinderInterface::class);
        /** @var FormFactoryInterface $formFactory */
        $formFactory = self::getContainer()->get('form.factory');
        $userManager = new UserManager($this->getDoctrineManager(), $formFactory, $encoder, $finder);
        $author = $userManager->findUserByLogin(MultipleUsersFixture::PRATCHETT);
        $params = ['authorId' => $author->getId()];
        $inputs = $followersCount === null ? ["\n"] : ["$followersCount\n"];
        $commandTester->setInputs($inputs);
        $commandTester->execute($params);
        $output = $commandTester->getDisplay();

        static::assertSame($expected, $output);
    }
}
