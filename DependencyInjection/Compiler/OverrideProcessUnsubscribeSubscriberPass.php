<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideProcessUnsubscribeSubscriberPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber')) {
            return;
        }

        $definition = $container->getDefinition('Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber');
        $definition->setClass('MauticPlugin\MauticUnsubscribeBundle\EventListener\ProcessUnsubscribeSubscriber');
    }
}
