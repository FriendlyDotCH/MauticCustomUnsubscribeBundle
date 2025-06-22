<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber as CoreProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use MauticPlugin\MauticUnsubscribeBundle\Integration\CustomUnsubscribeIntegration;

class ProcessUnsubscribeSubscriber extends CoreProcessUnsubscribeSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::MONITORED_EMAIL_CONFIG => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 0],
            EmailEvents::EMAIL_ON_SEND          => ['onEmailSend', 0],
        ];
    }

    public function __construct(
        private Unsubscribe $unsubscriber,
        private FeedbackLoop $looper,
        private CustomUnsubscribeIntegration $unsubIntegration
    ) {
        parent::__construct($unsubscriber, $looper);
    }

    /**
     * Add an unsubscribe email to the List-Unsubscribe header if applicable.
     */
    public function onEmailSend(EmailSendEvent $event): void
    {
        $isPublished = $this->unsubIntegration?->getIntegrationConfiguration()?->getIsPublished();
        // if published ignore logic below.
        if ($isPublished) {
            return;
        }

        $helper = $event->getHelper();
        if ($helper && $unsubscribeEmail = $helper->generateUnsubscribeEmail()) {
            $headers          = $event->getTextHeaders();
            $existing         = $headers['List-Unsubscribe'] ?? '';
            $unsubscribeEmail = "<mailto:$unsubscribeEmail>";

            if ($existing) {
                if (!str_contains($existing, $unsubscribeEmail)) {
                    $updatedHeader = $existing.', '.$unsubscribeEmail;
                } else {
                    $updatedHeader = $existing;
                }
            } else {
                $updatedHeader = $unsubscribeEmail;
            }

            $event->addTextHeader('List-Unsubscribe', $updatedHeader);
        }
    }
}
