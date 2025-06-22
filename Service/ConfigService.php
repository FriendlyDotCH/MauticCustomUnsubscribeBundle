<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Service;

use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class ConfigService
{
    private $integration;

    public function __construct(
        private IntegrationsHelper $integrationsHelper,
        private IntegrationHelper $integrationHelper
    ) {
        $this->integration = null;

        try {
            /**
             * @todo Add more specific exception.
             */
            $integration = $this->integrationHelper->getIntegrationObject('CustomUnsubscribe');
            $this->integrationsHelper->addIntegration($integration);
            $this->integrationsHelper->getIntegrationConfiguration($integration);
            $this->integration = $integration;
        } catch (\Exception $e) {
        }
    }

    public function get($key, $default): mixed
    {
        if (!$this->integration) {
            return $default;
        }

        return $this->integration->getIntegrationConfiguration()->getApiKeys()[$key] ?? $default;
    }
}
