<?php

namespace App\Symfony;

use App\Service\MessageService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormatterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(MessageService::class)) {
            return;
        }
        $messageService = $container->findDefinition(MessageService::class);
        $formatterServices = $container->findTaggedServiceIds('app.formatter_service');
        foreach ($formatterServices as $id => $tags) {
            $messageService->addMethodCall('addFormatter', [new Reference($id)]);
        }
    }
}
