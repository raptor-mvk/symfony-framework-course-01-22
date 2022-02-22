<?php

namespace App;

use App\Symfony\FormatterPass;
use App\Symfony\GreeterPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FormatterPass());
        $container->addCompilerPass(new GreeterPass());
    }
}
