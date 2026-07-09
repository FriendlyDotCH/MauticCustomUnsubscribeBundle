<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeLinkService
{
    public function __construct(
        private UrlGeneratorInterface $router,
    ) {
    }

    /**
     * Generate unsubscribe link for email body (plain URL).
     */
    public function getBodyLink(
        int $contactId,
        string $field,
        ?string $email = null,
        ?string $hash = null,
    ): string {
        return $this->generateUnsubscribeUrl($field, $email, $hash, 'body');
    }

    /**
     * Generate unsubscribe link for List-Unsubscribe header (RFC-2369).
     * URL only (no mailto).
     */
    public function getHeaderLink(
        int $contactId,
        string $field,
        ?string $email = null,
        ?string $hash = null,
    ): string {
        $url = $this->generateUnsubscribeUrl($field, $email, $hash, 'header');

        return "<{$url}>";
    }

    /**
     * Generate hidden NHI link (for invisible unsubscribe tracking).
     */
    public function getHiddenLink(int $contactId): string
    {
        return $this->router->generate(
            'friendly_hidden_link',
            ['id' => $contactId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Centralized URL generation logic.
     */
    private function generateUnsubscribeUrl(
        string $field,
        ?string $email = null,
        ?string $hash = null,
        ?string $origin = null,
    ): string {
        if (null === $hash || null === $email) {
            return '';
        }

        $url = $this->router->generate(
            'friendly_unsubscribe_secure',
            [
                'hash'  => $hash,
                'field' => $field,
                'email' => $email,
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        if (null !== $origin) {
            $separator = null === parse_url($url, PHP_URL_QUERY) ? '?' : '&';
            $url .= $separator.'origin='.urlencode($origin);
        }

        return $url;
    }
}
