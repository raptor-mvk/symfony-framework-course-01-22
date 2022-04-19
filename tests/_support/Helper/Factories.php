<?php

namespace App\Tests\Helper;

use App\Entity\Subscription;
use App\Entity\Tweet;
use App\Entity\User;
use Codeception\Module;
use Codeception\Module\DataFactory;
use League\FactoryMuffin\Faker\Facade;

class Factories extends Module
{
    public function _beforeSuite($settings = [])
    {
        /** @var DataFactory $factory */
        $factory = $this->getModule('DataFactory');

        $factory->_define(
            User::class,
            [
                'login' => Facade::text(20)(),
                'password' => Facade::text(20)(),
                'age' => Facade::randomNumber(2)(),
                'is_active' => true,
                'phone' => '+0'.Facade::randomNumber(9, true)(),
                'email' => Facade::email()(),
                'preferred' => 'email',
            ]
        );
        $factory->_define(
            Tweet::class,
            [
                'author' => 'entity|'.User::class,
            ]
        );
        $factory->_define(
            Subscription::class,
            [
                'author' => 'entity|'.User::class,
                'follower' => 'entity|'.User::class,
            ]
        );
    }
}
