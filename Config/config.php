<?php

declare(strict_types=1);

return [
    'name'        => 'Unsubscribe Plugin',
    'description' => 'Allows contacts to unsubscribe via a simple URL.',
    'version'     => '2.0.0',
    'author'      => 'Joey Keller',
    'routes'      => [
        'public' => [
            // Secure unsubscribe with hash
            'friendly_unsubscribe_secure' => [
                'path'       => '/friendly-unsubscribe/secure/{email}/{hash}/{field}',
                'controller' => 'MauticPlugin\MauticUnsubscribeBundle\Controller\UnsubscribeController::unsubscribeSecureAction',
                'method'     => 'GET|POST|HEAD',
            ],
            'friendly_hidden_link' => [
                'path'       => '/friendly-unsubscribe/nhi/{id}',
                'controller' => 'MauticPlugin\MauticUnsubscribeBundle\Controller\HiddenLinkController::trackRedirectAction',
                'method'     => 'GET|POST',
                'arguments'  => [
                    'mautic.lead.model.lead',
                    'mautic.friendlyunsubscribe.hash_helper',
                ],
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
                    'mautic.friendlyunsubscribe.hash_helper',
                    'mautic.integrations.helper',
                    'mautic.friendlyunsubscribe.unsubscribe_link_service',
                ],
            ],
        ],
    ],
];
