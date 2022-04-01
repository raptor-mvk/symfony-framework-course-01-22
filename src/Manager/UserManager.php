<?php

namespace App\Manager;

use App\DTO\SaveUserDTO;
use App\Entity\User;
use App\Form\LinkedUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    private EntityManagerInterface $entityManager;

    private FormFactoryInterface $formFactory;

    private UserPasswordHasherInterface $userPasswordHasher;

    private PaginatedFinderInterface $finder;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, UserPasswordHasherInterface $userPasswordHasher, PaginatedFinderInterface $finder)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        $this->userPasswordHasher = $userPasswordHasher;
        $this->finder = $finder;
    }

    public function saveUser(string $login): ?int
    {
        $user = new User();
        $user->setLogin($login);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user->getId();
    }

    public function updateUser(int $userId, string $login): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }
        $user->setLogin($login);
        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(User $user): bool
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    public function deleteUserById(int $userId): bool
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }
        return $this->deleteUser($user);
    }

    /**
     * @return User[]
     */
    public function getUsers(int $page, int $perPage): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->getUsers($page, $perPage);
    }

    public function getSaveForm(): FormInterface
    {
        return $this->formFactory->createBuilder()
            ->add('login', TextType::class)
            ->add('password', PasswordType::class)
            ->add('age', IntegerType::class)
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('submit', SubmitType::class)
            ->getForm();
    }

    public function saveUserFromDTO(User $user, SaveUserDTO $saveUserDTO): ?int
    {
        $user->setLogin($saveUserDTO->login);
        $user->setPassword($this->userPasswordHasher->hashPassword($user, $saveUserDTO->password));
        $user->setAge($saveUserDTO->age);
        $user->setIsActive($saveUserDTO->isActive);
        $user->setRoles($saveUserDTO->roles);
        $user->setPhone($saveUserDTO->phone);
        $user->setEmail($saveUserDTO->email);
        $user->setPreferred($saveUserDTO->preferred);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user->getId();
    }

    public function getUpdateForm(int $userId): ?FormInterface
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }

        return $this->formFactory->createBuilder(FormType::class, SaveUserDTO::fromEntity($user))
            ->add('login', TextType::class)
            ->add('password', PasswordType::class)
            ->add('age', IntegerType::class)
            ->add('isActive', CheckboxType::class, ['required' => false])
            ->add('submit', SubmitType::class)
            ->add('followers', CollectionType::class, [
                'entry_type' => LinkedUserType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
            ])
            ->setMethod('PATCH')
            ->getForm();
    }

    public function updateUserFromDTO(int $userId, SaveUserDTO $userDTO): bool
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }

        $user->resetFollowers();

        foreach ($userDTO->followers as $followerData) {
            $followerUserDTO = new SaveUserDTO($followerData);
            /** @var User $followerUser */
            if (isset($followerData['id'])) {
                $followerUser = $userRepository->find($followerData['id']);
                if ($followerUser !== null) {
                    $this->saveUserFromDTO($followerUser, $followerUserDTO);
                }
            } else {
                $followerUser = new User();
                $this->saveUserFromDTO($followerUser, $followerUserDTO);
            }
            $user->addFollower($followerUser);
        }

        return $this->saveUserFromDTO($user, $userDTO);
    }

    public function findUserById(int $userId): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->find($userId);
    }

    public function findUserByLogin(string $login): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['login' => $login]);

        return $user;
    }

    public function updateUserToken(string $login): ?string
    {
        $user = $this->findUserByLogin($login);
        if ($user === null) {
            return false;
        }
        $token = base64_encode(random_bytes(20));
        $user->setToken($token);
        $this->entityManager->flush();

        return $token;
    }

    public function findUserByToken(string $token): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['token' => $token]);

        return $user;
    }

    /**
     * @return User[]
     */
    public function findUserByQuery(string $query, int $perPage, int $page): array
    {
        $paginatedResult = $this->finder->findPaginated($query);
        $paginatedResult->setMaxPerPage($perPage);
        $paginatedResult->setCurrentPage($page);
        $result = [];
        array_push($result, ...$paginatedResult->getCurrentPageResults());

        return $result;
    }
}
