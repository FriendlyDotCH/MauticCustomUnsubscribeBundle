<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class HashHelper
{
    private $secretKey;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        // Use the same secret key that Mautic uses for security
        $this->secretKey = $coreParametersHelper->get('secret_key');
    }

    /**
     * Generate a secure hash for the unsubscribe URL.
     */
    public function generateUnsubscribeHash(int $id, string $field, string $email = ''): string
    {
        // Create a hash that includes the ID, field, and a secret key
        $data = sprintf('%s|%s|%s|%s', $id, $field, $email, $this->secretKey);

        return hash('sha256', $data);
    }

    /**
     * Validate the hash in the unsubscribe URL.
     */
    public function validateUnsubscribeHash(string $hash, int $id, string $field, string $email = ''): bool
    {
        $expectedHash = $this->generateUnsubscribeHash($id, $field, $email);

        return hash_equals($expectedHash, $hash);
    }
}
