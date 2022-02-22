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
        uasort($greeterServices, static fn(array $tag1, array $tag2) => $tag1[0]['priority'] - $tag2[0]['priority']);
        foreach ($greeterServices as $id => $tags) {
            $messageService->addMethodCall('addGreeter', [new Reference($id)]);
        }
    }
}
