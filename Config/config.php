<?php

declare(strict_types=1);

return [
    'name'        => 'Unsubscribe Plugin',
    'description' => 'Allows contacts to unsubscribe via a simple URL.',
    'version'     => '1.0.0',
    'author'      => 'Joey Keller',
    'routes'      => [
        'public' => [
            'friendly_unsubscribe' => [
                'path'       => '/friendly-unsubscribe/{id}/{field}',
                'controller' => 'MauticPlugin\MauticUnsubscribeBundle\Controller\UnsubscribeController::unsubscribeAction',
            ],
            'friendly_hidden_link' => [
                'path'       => '/friendly-unsubscribe/nhi/{id}',
                'controller' => 'MauticPlugin\MauticUnsubscribeBundle\Controller\HiddenLinkController::trackRedirectAction',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.unsubscribe_token_subscriber' => [
                'class'     => MauticPlugin\MauticUnsubscribeBundle\EventListener\UnsubscribeTokenSubscriber::class,
                'arguments' => [
                    'router',
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],
];
