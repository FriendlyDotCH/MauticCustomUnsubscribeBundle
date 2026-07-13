<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\MauticUnsubscribeBundle\Helper\HashHelper;
use MauticPlugin\MauticUnsubscribeBundle\Integration\FriendlyUnsubscribeIntegration;
use MauticPlugin\MauticUnsubscribeBundle\Service\UnsubscribeLinkService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UnsubscribeTokenSubscriber implements EventSubscriberInterface
{
    private FriendlyUnsubscribeIntegration $integration;

    public function __construct(
        private LoggerInterface $logger,
        private HashHelper $hashHelper,
        IntegrationsHelper $integrationsHelper,
        private UnsubscribeLinkService $unsubscribeLinkService,
    ) {
        $integration = $integrationsHelper->getIntegration(FriendlyUnsubscribeIntegration::NAME);
        \assert($integration instanceof FriendlyUnsubscribeIntegration);
        $this->integration = $integration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailSend', 255],
        ];
    }

    public function onEmailSend(EmailSendEvent $event): void
    {
        $config = $this->integration->getIntegrationConfiguration();
        if (!$config->isPublished()) {
            return;
        }

        $this->logger->info('UnsubscribeTokenSubscriber->onEmailSend');

        $contact = $event->getLead();
        if (!isset($contact['id'], $contact['email'])) {
            return;
        }

        $content   = $event->getContent();

        $this->logger->debug('UnsubscribeTokenSubscriber->onEmailSend', ['content' => $content]);
        $contactId = $contact['id'];
        $tokens    = [];
        $result    = [
            'orgToken'        => '{customunsubscribe=fieldname text="Abbestellen"}',
            'field'           => null,
            'unsubscribeText' => 'Abbestellen',
            'color'           => '#000000',
        ];

        $matches = [];
        preg_match_all(
            '/\{(?<full>customunsubscribe=(?<field>[\w_]+)(?:\s+text="(?<text>[^"]*)")?(?:\s+color="(?<color>[^"]*)")?)\}/',
            $content,
            $matches,
            \PREG_SET_ORDER
        );

        $this->logger->debug('UnsubscribeTokenSubscriber->onEmailSend', ['matches' => $matches]);

        if (!empty($matches[0])) {
            $match              = $matches[0];
            $result['orgToken'] = '{'.$match['full'].'}';
            $result['field']    = $match['field'];

            if (isset($match['text']) && '' !== $match['text']) {
                $result['unsubscribeText'] = $match['text'];
            }
            if (isset($match['color']) && '' !== $match['color']) {
                $result['color'] = $match['color'];
            }
        } else {
            return;
        }

        $orgToken        = $result['orgToken'];
        $field           = $result['field'];
        $unsubscribeText = $result['unsubscribeText'];
        $color           = $result['color'];

        // --- Generate hash version only ---
        $hash = $this->hashHelper->generateUnsubscribeHash(
            (int) $contactId,
            $field,
            $contact['email']
        );

        $unsubscribeUrl = $this->unsubscribeLinkService->getBodyLink(
            $contactId,
            $field,
            $contact['email'],
            $hash
        );

        $headerLink = $this->unsubscribeLinkService->getHeaderLink(
            $contactId,
            $field,
            $contact['email'],
            $hash
        );

        $event->addTextHeader('List-Unsubscribe', $headerLink);

        $style = sprintf('style="color: %s; text-decoration: underline;"', $color);

        $tokens[$orgToken] = sprintf(
            '<a href="%s" %s mautic:disable-tracking="true">%s</a>',
            $unsubscribeUrl,
            $style,
            $unsubscribeText
        );

        // --- Hidden NHI link ---
        $hiddenUrl       = $this->unsubscribeLinkService->getHiddenLink($contactId);
        $tokens['{nhi}'] = sprintf(
            '<a href="%s" mautic:disable-tracking="true" style="display:none;font-size:1px;color:transparent;">.</a>',
            $hiddenUrl
        );

        // --- Logging ---
        $logData = json_encode([
            'field'           => $field,
            'unsubscribeText' => $unsubscribeText,
            'unsubscribeUrl'  => $unsubscribeUrl,
            'headerLink'      => $headerLink,
            'color'           => $color,
            'contactId'       => $contactId,
            'tokens'          => $tokens,
        ], \JSON_PRETTY_PRINT);

        $this->logger->debug('UnsubscribeTokenSubscriber', ['logData' => $logData]);

        $event->addTokens($tokens);
    }
}
