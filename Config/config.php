<?php

declare(strict_types=1);

return [
    'name'        => 'Unsubscribe Plugin',
    'description' => 'Allows contacts to unsubscribe via a simple URL.',
    'version'     => '1.0.0',
    'author'      => 'Joey Keller',
    'routes'      => [
        'public' => [
            'mautic_unsubscribe' => [
                'path'       => '/unsubscribe/{id}/{field}',
                'controller' => 'MauticPlugin\\MauticUnsubscribeBundle\\Controller\\UnsubscribeController::unsubscribeAction',
            ],
            'hidden_link' => [
                'path'       => '/nhi/{id}',
                'controller' => 'MauticPlugin\\MauticUnsubscribeBundle\\Controller\\HiddenLinkController::trackRedirect',
            ],
        ],
    ],
    'services' => [
        'events' => [
            'mautic.unsubscribe_token_subscriber' => [
                'class'     => 'MauticPlugin\\MauticUnsubscribeBundle\\EventListener\\UnsubscribeTokenSubscriber',
                'arguments' => [
                    'router',
                    'monolog.logger.mautic',
                ],
            ],
        ],
    ],
];
