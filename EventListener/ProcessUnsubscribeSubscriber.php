<?php

namespace MauticPlugin\MauticUnsubscribeBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber as CoreProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;

class ProcessUnsubscribeSubscriber extends CoreProcessUnsubscribeSubscriber
{
    public function devLog2(string $message): void
    {
        file_put_contents('/var/www/html/mylog.txt', date('Y-m-d H:i:s').' '.$message.PHP_EOL, FILE_APPEND);
    }

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
        private FeedbackLoop $looper
    ) {
        parent::__construct($unsubscriber, $looper);
    }

    /**
     * Add an unsubscribe email to the List-Unsubscribe header if applicable.
     */
    public function onEmailSend(EmailSendEvent $event): void
    {
        $this->devLog2('this is from devlog22222222222222 - empty');
        /**
         * @todo Disable logic below if custom unsubscribe is enabled
         */

        /*$helper = $event->getHelper();
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
        }*/
    }
}
