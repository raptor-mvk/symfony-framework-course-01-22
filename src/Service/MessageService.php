<?php

namespace App\Service;

class MessageService
{
    /** @var GreeterService[] */
    private array $greeterServices;
    /** @var FormatService[] */
    private array $formatServices;

    public function __construct()
    {
        $this->greeterServices = [];
        $this->formatServices = [];
    }

    public function addGreeter(GreeterService $greeterService): void
    {
        $this->greeterServices[] = $greeterService;
    }

    public function addFormatter(FormatService $formatService): void
    {
        $this->formatServices[] = $formatService;
    }

    public function printMessages(string $name): string
    {
        $result = '';
        foreach ($this->greeterServices as $greeterService) {
            $current = $greeterService->greet($name);
            foreach ($this->formatServices as $formatService) {
                $current = $formatService->format($current);
            }
            $result .= $current;
        }

        return $result;
    }
}
