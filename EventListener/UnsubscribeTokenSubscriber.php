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

        // Match tokens like {customunsubscribe=fieldname}
        preg_match_all('/\{customunsubscribe=([\w]+)\}/', $content, $matches);
        $fields = $matches[1] ?? [];

        foreach ($fields as $field) {
            $linkTag = sprintf('<a href="%s" mautic:disable-tracking="true">Abbestellen</a>', $this->router->generate(
                'mautic_unsubscribe',
                ['id' => $contactId, 'field' => $field],
                UrlGeneratorInterface::ABSOLUTE_URL
            ));
            $tokens["{customunsubscribe=$field}"] = $linkTag;
        }

        // Add hidden nhi link.
        $nhiLinkTag = sprintf(
            '<a href="%s" mautic:disable-tracking="true" style="display:none;font-size:1px;color:transparent;">.</a>',
            $this->router->generate('hidden_link', ['id' => $contactId], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $tokens['{nhi}'] = $nhiLinkTag;

        $logData = json_encode([
            'fields'    => $fields,
            'contactId' => $contactId,
            'tokens'    => $tokens,
        ], \JSON_PRETTY_PRINT);
        $this->logger->debug(
            'UnsubscribeTokenSubscriber:',
            ['logData' => $logData]
        );
        $event->addTokens($tokens);
    }
}
