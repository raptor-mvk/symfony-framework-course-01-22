<?php

namespace App\Transformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\DTO\UserDTO;
use App\Entity\Subscription;
use App\Entity\User;

class UserTransformer implements DataTransformerInterface
{
    /**
     * @param User $user
     */
    public function transform($user, string $to, array $context = []): UserDTO
    {
        /** @var User $user */
        $userDTO = new UserDTO();
        $userDTO->login = $user->getLogin();
        $userDTO->email = $user->getEmail();
        $userDTO->phone = $user->getPhone();
        $userDTO->followers = array_map(
            static function (Subscription $subscription): string {
                return $subscription->getFollower()->getLogin();
            },
            $user->getSubscriptionFollowers()
        );
        $userDTO->followed = array_map(
            static function (Subscription $subscription): string {
                return $subscription->getAuthor()->getLogin();
            },
            $user->getFollowed()
        );

        return $userDTO;
    }

    /**
     * @param User $user
     */
    public function supportsTransformation($user, string $to, array $context = []): bool
    {
        return UserDTO::class === $to && ($user instanceof User);
    }
}
