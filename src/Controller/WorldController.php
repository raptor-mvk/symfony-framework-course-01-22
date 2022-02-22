<?php

namespace App\Controller;

use App\Service\FormatService;
use App\Service\GreeterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class WorldController extends AbstractController
{
    private GreeterService $greeterService;

    private FormatService $formatService;

    public function __construct(FormatService $formatService, GreeterService $greeterService)
    {
        $this->greeterService = $greeterService;
        $this->formatService = $formatService;
    }

    public function hello(): Response
    {
        $result = $this->formatService->format($this->greeterService->greet('world'));

        return new Response("<html><body>$result</body></html>");
    }
}
