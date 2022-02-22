<?php

namespace App\Symfony;

use App\Service\MessageService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GreeterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(MessageService::class)) {
            return;
        }
        $messageService = $container->findDefinition(MessageService::class);
        $greeterServices = $container->findTaggedServiceIds('app.greeter_service');
        foreach ($greeterServices as $id => $tags) {
            $messageService->addMethodCall('addGreeter', [new Reference($id)]);
        }
    }
}
