<?php

namespace App\Service;

class GreeterService
{
    private string $greet;

    public function __construct(string $greet)
    {
        $this->greet = $greet;
    }

    public function greet(string $name): string
    {
        return $this->greet.', '.$name.'!';
    }
}
