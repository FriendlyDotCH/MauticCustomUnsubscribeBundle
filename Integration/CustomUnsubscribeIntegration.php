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
use MauticPlugin\MauticUnsubscribeBundle\Form\Type\UnsubscribeSettingsType;

class CustomUnsubscribeIntegration extends BasicIntegration implements BasicInterface, ConfigFormInterface, IntegrationInterface, ConfigFormFeaturesInterface, BuilderInterface, ConfigFormAuthInterface
{
    use ConfigurationTrait;
    use DefaultConfigFormTrait;

    public const NAME         = 'customunsubscribe';
    public const DISPLAY_NAME = 'Custom Unsubscribe Integration';

    public function __construct(
        private IntegrationRepository $integrationRepo,
    ) {
        $this->integration = $this->integrationRepo->findOneByName('CustomUnsubscribe');
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
        return [];
    }

    public function getAuthConfigFormName(): string
    {
        return UnsubscribeSettingsType::class;
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
