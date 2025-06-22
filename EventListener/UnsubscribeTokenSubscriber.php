<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticUnsubscribeBundle\Integration\CustomUnsubscribeIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeTokenSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $router;
    private LoggerInterface $logger;

    private CustomUnsubscribeIntegration $unsubIntegration;

    public function __construct(
        UrlGeneratorInterface $router,
        LoggerInterface $logger,
        CustomUnsubscribeIntegration $unsubIntegration,
    ) {
        $this->router           = $router;
        $this->logger           = $logger;
        $this->unsubIntegration = $unsubIntegration;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailSend', 255],
        ];
    }

    public function onEmailSend(EmailSendEvent $event): void
    {
        $this->logger->info('UnsubscribeTokenSubscriber->onEmailSend triggered');

        $isPublished = $this->unsubIntegration?->getIntegrationConfiguration()?->getIsPublished();
        if (!$isPublished) {
            return;
        }

        $contact = $event->getLead();

        if (!is_array($contact) || empty($contact['id'])) {
            return;
        }

        $contactId = $contact['id'];
        $content   = $event->getContent();
        $tokens    = [];

        $matches = [];
        preg_match_all('/\{customunsubscribe=([\w]+)(?:\s+text="([^"]*)")?\}/', $content, $matches);

        if (empty($matches[0])) {
            return;
        }

        $orgToken        = $matches[0][0];
        $field           = $matches[1][0] ?? null;
        $unsubscribeText = $matches[2][0] ?? '';
        $unsubscribeText = '' !== trim($unsubscribeText) ? $unsubscribeText : 'Abbestellen';

        // Generate unsubscribe URL
        $unsubscribeUrl = $this->router->generate(
            'mautic_custom_unsubscribe',
            ['id' => $contactId, 'field' => $field],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Add List-Unsubscribe header
        $event->addTextHeader('List-Unsubscribe', sprintf('<%s>', $unsubscribeUrl));
        $event->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

        // Replace token in email content
        $tokens[$orgToken] = sprintf(
            '<a href="%s" mautic:disable-tracking="true">%s</a>',
            $unsubscribeUrl,
            $unsubscribeText
        );

        // Add hidden nhi link
        $hiddenUrl = $this->router->generate(
            'hidden_link',
            ['id' => $contactId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $tokens['{nhi}'] = sprintf(
            '<a href="%s" mautic:disable-tracking="true" style="display:none;font-size:1px;color:transparent;">.</a>',
            $hiddenUrl
        );

        // Log data for debugging
        $this->logger->debug('UnsubscribeTokenSubscriber token data', [
            'field'           => $field,
            'unsubscribeText' => $unsubscribeText,
            'unsubscribeUrl'  => $unsubscribeUrl,
            'contactId'       => $contactId,
            'tokens'          => $tokens,
        ]);

        // Apply tokens to the event
        $event->addTokens($tokens);
    }
}
