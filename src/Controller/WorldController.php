<?php

namespace App\Controller;

use App\Service\GreeterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorldController extends AbstractController
{
    private GreeterService $greeterService;

    public function __construct(GreeterService $greeterService)
    {
        $this->greeterService = $greeterService;
    }

    public function hello(): Response
    {
        return new Response("<html><body>{$this->greeterService->greet('world')}</body></html>");
    }
}
