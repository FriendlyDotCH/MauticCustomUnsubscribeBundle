<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideMailHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('Mautic\EmailBundle\Helper\MailHelper')) {
            return;
        }

        $definition = $container->getDefinition('Mautic\EmailBundle\Helper\MailHelper');
        $definition->setClass('MauticPlugin\MauticUnsubscribeBundle\Helper\MailHelper');
    }
}
