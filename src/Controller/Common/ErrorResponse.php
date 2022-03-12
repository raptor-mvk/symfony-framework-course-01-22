<?php

namespace App\Controller\Common;

class ErrorResponse
{
    public bool $success = false;

    /** @var Error[] */
    public array $errors;

    public function __construct(Error ...$errors)
    {
        $this->errors = $errors;
    }
}
