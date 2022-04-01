<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use JsonException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: '`user`')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
class User implements HasMetaTimestampsInterface, UserInterface, PasswordAuthenticatedUserInterface
{
    public const EMAIL_NOTIFICATION = 'email';
    public const SMS_NOTIFICATION = 'sms';

    #[ORM\Column(name: 'id', type: 'bigint', unique:true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[JMS\Groups(['user2'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: false)]
    #[JMS\Groups(['user1', 'elastica'])]
    private string $login;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTime $updatedAt;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: 'Tweet')]
    private Collection $tweets;

    #[ORM\ManyToMany(targetEntity: 'User', mappedBy: 'followers')]
    private Collection $authors;

    #[ORM\ManyToMany(targetEntity: 'User', inversedBy: 'authors')]
    #[ORM\JoinTable(name: 'author_follower')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'follower_id', referencedColumnName: 'id')]
    private Collection $followers;

    #[ORM\OneToMany(mappedBy: 'follower', targetEntity: 'Subscription')]
    private Collection $subscriptionAuthors;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: 'Subscription')]
    private Collection $subscriptionFollowers;

    #[ORM\Column(type: 'string', length: 120, nullable: false)]
    private string $password;

    #[ORM\Column(type: 'integer', nullable: false)]
    #[JMS\Type('int')]
    #[JMS\Groups(['user1', 'elastica'])]
    private int $age;

    #[ORM\Column(type: 'boolean', nullable: false)]
    #[JMS\Type('bool')]
    #[JMS\Groups(['user1'])]
    #[JMS\SerializedName('isActive')]
    private bool $isActive;

    #[ORM\Column(type: 'string', length: 1024, nullable: false)]
    private string $roles = '{}';

    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 11, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $phone;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $email;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[JMS\Type('string')]
    #[JMS\Groups(['elastica'])]
    private ?string $preferred;

    public function __construct()
    {
        $this->tweets = new ArrayCollection();
        $this->authors = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->subscriptionAuthors = new ArrayCollection();
        $this->subscriptionFollowers = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    public function setCreatedAt(): void {
        $this->createdAt = new DateTime();
    }

    public function getUpdatedAt(): DateTime {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): void {
        $this->updatedAt = new DateTime();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): void
    {
        $this->age = $age;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'password' => $this->password,
            'roles' => $this->getRoles(),
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'tweets' => array_map(static fn(Tweet $tweet) => $tweet->toArray(), $this->tweets->toArray()),
            'followers' => array_map(
                static fn(User $user) => ['id' => $user->getId(), 'login' => $user->getLogin()],
                $this->followers->toArray()
            ),
            'authors' => array_map(
                static fn(User $user) => ['id' => $user->getLogin(), 'login' => $user->getLogin()],
                $this->authors->toArray()
            ),
            'subscriptionFollowers' => array_map(
                static fn(Subscription $subscription) => [
                    'subscription_id' => $subscription->getId(),
                    'user_id' => $subscription->getFollower()->getId(),
                    'login' => $subscription->getFollower()->getLogin(),
                ],
                $this->subscriptionFollowers->toArray()
            ),
            'subscriptionAuthors' => array_map(
                static fn(Subscription $subscription) => [
                    'subscription_id' => $subscription->getId(),
                    'user_id' => $subscription->getAuthor()->getId(),
                    'login' => $subscription->getAuthor()->getLogin(),
                ],
                $this->subscriptionAuthors->toArray()
            ),
        ];
    }

    public function addTweet(Tweet $tweet): void
    {
        if (!$this->tweets->contains($tweet)) {
            $this->tweets->add($tweet);
        }
    }

    public function addFollower(User $follower): void
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
        }
    }

    public function addAuthor(User $author): void
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
        }
    }

    public function addSubscriptionAuthor(Subscription $subscription): void
    {
        if (!$this->subscriptionAuthors->contains($subscription)) {
            $this->subscriptionAuthors->add($subscription);
        }
    }

    public function addSubscriptionFollower(Subscription $subscription): void
    {
        if (!$this->subscriptionFollowers->contains($subscription)) {
            $this->subscriptionFollowers->add($subscription);
        }
    }

    /**
     * @return User[]
     */
    public function getFollowers(): array
    {
        return $this->followers->toArray();
    }

    public function resetFollowers(): void
    {
        $this->followers = new ArrayCollection();
    }

    /**
     * @return string[]
     *
     * @throws JsonException
     */
    public function getRoles(): array
    {
        $roles = json_decode($this->roles, true, 512, JSON_THROW_ON_ERROR);
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param string[] $roles
     *
     * @throws JsonException
     */
    public function setRoles(array $roles): void
    {
        $this->roles = json_encode($roles, JSON_THROW_ON_ERROR);
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUsername(): string
    {
        return $this->login;
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPreferred(): ?string
    {
        return $this->preferred;
    }

    public function setPreferred(?string $preferred): void
    {
        $this->preferred = $preferred;
    }
}
