<?php

namespace App\Tests\Helper;

use App\Entity\User;
use Codeception\Module;
use Codeception\Module\DataFactory;
use League\FactoryMuffin\Faker\Facade;

class Factories extends Module
{
    public function _beforeSuite($settings = []): void
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
    }
}
