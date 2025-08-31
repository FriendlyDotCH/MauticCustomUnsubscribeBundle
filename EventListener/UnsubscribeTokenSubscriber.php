<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\MauticUnsubscribeBundle\Helper\HashHelper;
use MauticPlugin\MauticUnsubscribeBundle\Integration\FriendlyUnsubscribeIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeTokenSubscriber implements EventSubscriberInterface
{
    private $router;

    private $logger;

    private $hashHelper;

    private $integration;

    public function __construct(
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        HashHelper $hashHelper,
        IntegrationsHelper $integrationsHelper,
    ) {
        $this->router      = $router;
        $this->logger      = $logger;
        $this->hashHelper  = $hashHelper;
        $this->integration = $integrationsHelper->getIntegration(FriendlyUnsubscribeIntegration::NAME);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailSend', 255],
        ];
    }

    public function onEmailSend(EmailSendEvent $event)
    {
        $config = $this->integration?->getIntegrationConfiguration();
        if (!$config || !$config->isPublished()) {
            return;
        }

        $this->logger->info('UnsubscribeTokenSubscriber->onEmailSend');

        $contact = $event->getLead();
        if (!isset($contact['id'])) {
            return;
        }

        $content   = $event->getContent();
        $contactId = $contact['id'];
        $tokens    = [];

        $matches = [];
        preg_match_all(
            '/\{(?<full>customunsubscribe=(?<field>[\w_]+)(?:\s+text="(?<text>[^"]*)")?(?:\s+color="(?<color>[^"]*)")?)\}/',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $result = [
            'orgToken'        => '{customunsubscribe=fieldname text="Abbestellen"}',
            'field'           => null,
            'unsubscribeText' => 'Abbestellen',
            'color'           => '#000000', // Default color if not provided
        ];

        // Process first match if found
        if (!empty($matches[0])) {
            $match              = $matches[0];
            $result['orgToken'] = '{'.$match['full'].'}';
            $result['field']    = $match['field'] ?? null;

            if (isset($match['text']) && '' !== $match['text']) {
                $result['unsubscribeText'] = $match['text'];
            }

            if (isset($match['color']) && '' !== $match['color']) {
                $result['color'] = $match['color'];
            }
        }

        $orgToken        = $result['orgToken'];
        $field           = $result['field'];
        $unsubscribeText = $result['unsubscribeText'];
        $color           = $result['color'];

        $unsubscribeUrl  = $this->router->generate(
            'friendly_unsubscribe',
            ['id' => $contactId, 'field' => $field],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $hashValues = $this->integration->isSupported('hashLeadId');
        if ($hashValues) {
            $hash = $this->hashHelper->generateUnsubscribeHash(
                (int) $contactId,
                $field,
                $contact['email'] ?? ''
            );

            $unsubscribeUrl = $this->router->generate(
                'friendly_unsubscribe_secure',
                ['hash' => $hash, 'field' => $field, 'email' => $contact['email']],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $event->addTextHeader('List-Unsubscribe', sprintf('<%s>', $unsubscribeUrl));

        $style = sprintf('style="color: %s; text-decoration: underline;"', $color);

        $tokens[$orgToken] = sprintf(
            '<a href="%s" %s mautic:disable-tracking="true">%s</a>',
            $unsubscribeUrl,
            $style,
            $unsubscribeText
        );

        // Add hidden nhi link.
        $hiddenUrl  = $this->router->generate('friendly_hidden_link', ['id' => $contactId], UrlGeneratorInterface::ABSOLUTE_URL);
        $nhiLinkTag = sprintf(
            '<a href="%s" %s mautic:disable-tracking="true" style="display:none;font-size:1px;color:transparent;">.</a>',
            $hiddenUrl,
            $style
        );
        $tokens['{nhi}'] = $nhiLinkTag;

        $logData = json_encode([
            'field'           => $field,
            'unsubscribeText' => $unsubscribeText,
            'unsubscribeUrl'  => $unsubscribeUrl,
            'color'           => $color,
            'contactId'       => $contactId,
            'tokens'          => $tokens,
        ],
            \JSON_PRETTY_PRINT);
        $this->logger->debug(
            'UnsubscribeTokenSubscriber:',
            ['logData' => $logData]
        );
        $event->addTokens($tokens);
    }
}
