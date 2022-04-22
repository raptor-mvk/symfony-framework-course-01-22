<?php

namespace App\Controller\Api\SaveUser\v5\Input;

use App\Entity\Traits\SafeLoadFieldsTrait;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Annotations as OA;

class SaveUserDTO
{
    use SafeLoadFieldsTrait;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Length(max=32)
     * @OA\Property(property="login", example="my_user")
     */
    public string $login;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("string")
     * @Assert\Length(max=32)
     * @OA\Property(property="password", example="my_pass")
     */
    public string $password;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("array")
     * @OA\Property(property="roles", type="array", @OA\Items(type="string", example="ROLE_USER"))
     */
    public array $roles;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("numeric")
     */
    public int $age;

    /**
     * @Assert\NotBlank()
     * @Assert\Type("bool")
     * @OA\Property(property="isActive")
     */
    public bool $isActive;

    public function getSafeFields(): array
    {
        return ['login', 'password', 'roles', 'age', 'isActive'];
    }
}
