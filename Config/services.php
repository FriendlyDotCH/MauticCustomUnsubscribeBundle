<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [];

    $services->load(
        'MauticPlugin\\MauticUnsubscribeBundle\\',
        __DIR__.'/../'
    )
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set('mautic.integration.customunsubscribe')
    ->class(MauticPlugin\MauticUnsubscribeBundle\Integration\CustomUnsubscribeIntegration::class);
};
