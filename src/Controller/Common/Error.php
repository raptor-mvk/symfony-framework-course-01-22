<?php

namespace App\Controller\Common;

class Error
{
    public string $propertyPath;

    public string $message;

    public function __construct(string $propertyPath, string $message)
    {
        $this->propertyPath = $propertyPath;
        $this->message = $message;
    }
}
