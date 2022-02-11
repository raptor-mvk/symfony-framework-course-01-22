<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(string $login): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setCreatedAt();
        $user->setUpdatedAt();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }

    public function findUser(int $id): ?User
    {
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->find($id);

        return $user instanceof User ? $user : null;
    }

    public function subscribeUser(User $author, User $follower): void
    {
        $author->addFollower($follower);
        $follower->addAuthor($author);
        $this->entityManager->flush();
    }

    /**
     * @return User[]
     */
    public function findUsersByLogin(string $name): array
    {
        return $this->entityManager->getRepository(User::class)->findBy(['login' => $name]);
    }

    /**
     * @return User[]
     */
    public function findUsersByCriteria(string $login): array
    {
        $criteria = Criteria::create();
        /** @noinspection NullPointerExceptionInspection */
        $criteria->andWhere(Criteria::expr()->eq('login', $login));
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository(User::class);

        return $repository->matching($criteria)->toArray();
    }
}
