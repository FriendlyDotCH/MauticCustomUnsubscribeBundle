<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeTokenSubscriber implements EventSubscriberInterface
{
    private $router;
    private $logger;

    public function __construct(UrlGeneratorInterface $router, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND => ['onEmailSend', 0],
        ];
    }

    public function onEmailSend(EmailSendEvent $event)
    {
        $this->logger->info('UnsubscribeTokenSubscriber->onEmailSend');
        $contact = $event->getLead();
        if (!isset($contact['id'])) {
            return;
        }

        $content   = $event->getContent();
        $contactId = $contact['id'];
        $tokens    = [];

        // $matches[1] = field names (e.g. 'shop')
        // $matches[2] = text attribute values (e.g. 'My unsubscribe text'), or empty string if not present
        $matches = [];
        preg_match_all('/\{customunsubscribe=([\w]+)(?:\s+text="([^"]*)")?\}/', $content, $matches);

        $orgToken        = $matches[0][0] ?? '{customunsubscribe=fieldname}';
        $field           = $matches[1][0] ?? null;
        $unsubscribeText = $matches[2][0] ?? 'Abbestellen';
        $unsubscribeText = $unsubscribeText ?: 'Abbestellen';

        $this->logger->debug(
            'UnsubscribeTokenSubscriber1:',
            ['logData' => $matches]
        );
        $unsubscribeUrl  = $this->router->generate(
            'mautic_unsubscribe',
            ['id' => $contactId, 'field' => $field],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $tokens[$orgToken] = sprintf(
            '<a href="%s" mautic:disable-tracking="true">%s</a>',
            $unsubscribeUrl,
            $unsubscribeText
        );

        // Add hidden nhi link.
        $hiddenUrl  = $this->router->generate('hidden_link', ['id' => $contactId], UrlGeneratorInterface::ABSOLUTE_URL);
        $nhiLinkTag = sprintf(
            '<a href="%s" mautic:disable-tracking="true" style="display:none;font-size:1px;color:transparent;">.</a>',
            $hiddenUrl
        );
        $tokens['{nhi}'] = $nhiLinkTag;

        $logData = json_encode([
            'field'           => $field,
            'unsubscribeText' => $unsubscribeText,
            'unsubscribeUrl'  => $unsubscribeUrl,
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
