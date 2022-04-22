<?php

namespace UnitTests;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FixturedTestCase extends WebTestCase
{
    private ?ContainerAwareLoader $fixtureLoader;

    private AbstractExecutor $fixtureExecutor;

    public function setUp(): void
    {
        self::bootKernel();
        $this->initFixtureExecutor();
    }

    public function tearDown(): void
    {
        $em = $this->getDoctrineManager();
        $em->clear();
        $em->getConnection()->close();
        gc_collect_cycles();
        parent::tearDown();
    }

    protected function initFixtureExecutor(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getDoctrineManager();
        $this->fixtureExecutor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
    }

    protected function addFixture(FixtureInterface $fixture): void
    {
        $this->getFixtureLoader()->addFixture($fixture);
    }

    protected function executeFixtures(): void
    {
        $this->fixtureExecutor->execute($this->getFixtureLoader()->getFixtures());
        $this->fixtureLoader = null;
    }

    protected function getReference(string $refName): object
    {
        return $this->fixtureExecutor->getReferenceRepository()->getReference($refName);
    }

    private function getFixtureLoader(): ContainerAwareLoader
    {
        if (!isset($this->fixtureLoader)) {
            $this->fixtureLoader = new ContainerAwareLoader(self::getContainer());
        }

        return $this->fixtureLoader;
    }

    public function getDoctrineManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManager();
    }
}
