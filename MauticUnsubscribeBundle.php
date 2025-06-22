<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\MauticUnsubscribeBundle\DependencyInjection\Compiler\OverrideMailHelperPass;
use MauticPlugin\MauticUnsubscribeBundle\DependencyInjection\Compiler\OverrideProcessUnsubscribeSubscriberPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MauticUnsubscribeBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideMailHelperPass());
        $container->addCompilerPass(new OverrideProcessUnsubscribeSubscriberPass());
    }
}
