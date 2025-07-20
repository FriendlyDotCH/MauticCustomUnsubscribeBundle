<?php

namespace MauticPlugin\MauticUnsubscribeBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\MauticUnsubscribeBundle\Form\Type\ConfigType;

class FriendlyUnsubscribeIntegration extends BasicIntegration implements BasicInterface, ConfigFormInterface, IntegrationInterface, ConfigFormFeaturesInterface, BuilderInterface, ConfigFormAuthInterface
{
    use ConfigurationTrait;
    use DefaultConfigFormTrait;

    public const NAME         = 'friendlyunsubscribe';
    public const DISPLAY_NAME = 'Friendly Unsubscribe Integration';
    public const DB_NAME      = 'FriendlyUnsubscribe';

    public function __construct(
        private IntegrationRepository $integrationRepo
    ) {
        $this->integration = $this->integrationRepo->findOneByName(self::DB_NAME);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/MauticUnsubscribeBundle/Assets/img/icon.png';
    }

    public function getSupportedFeatures(): array
    {
        return ['hashLeadId' => 'Hash contact id in unsubscribe link'];
    }

    public function getAuthConfigFormName(): string
    {
        return ConfigType::class;
    }

    public function isSupported(string $featureName): bool
    {
        if (!$this->hasIntegrationConfiguration()) {
            return false;
        }

        return in_array(
            $featureName,
            $this->getIntegrationConfiguration()->getSupportedFeatures()
        );
    }
}
