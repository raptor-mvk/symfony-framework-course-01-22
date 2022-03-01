<?php

namespace App\DTO;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class SaveUserDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $login;

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $password;

    #[Assert\NotBlank]
    public int $age;

    public bool $isActive;

    #[Assert\Type('array')]
    public array $followers;

    public function __construct(array $data)
    {
        $this->login = $data['login'] ?? '';
        $this->password = $data['password'] ?? '';
        $this->age = $data['age'] ?? 0;
        $this->isActive = $data['isActive'] ?? false;
        $this->followers = $data['followers'] ?? [];
    }

    public static function fromEntity(User $user): self
    {
        return new self([
            'login' => $user->getLogin(),
            'password' => $user->getPassword(),
            'age' => $user->getAge(),
            'isActive' => $user->isActive(),
            'followers' => array_map(
                static function (User $user) {
                    return [
                        'id' => $user->getId(),
                        'login' => $user->getLogin(),
                        'password' => $user->getPassword(),
                        'age' => $user->getAge(),
                        'isActive' => $user->isActive(),
                    ];
                },
                $user->getFollowers()
            ),
        ]);
    }
}
