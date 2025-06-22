<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Integration>
 */
class UnsubscribeSettingsType extends AbstractType
{
    public function __construct(
        private ConfigIntegrationsHelper $integrationsHelper
    ) {
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrationObject = $this->integrationsHelper->getIntegration($options['integration']->getName());
        $apiKeys           = $integrationObject->getIntegrationConfiguration()->getApiKeys() ?? [];

        // nhi
        $builder->add(
            'nhi',
            NumberType::class,
            [
                'label'      => 'mautic_custom_unsubscribe.nhi',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'data'  => $apiKeys['nhi'] ?? 3,
            ]
        );

        // allowedFields
        $builder->add(
            'allowedFields',
            TextType::class,
            [
                'label'      => 'mautic_custom_unsubscribe.allowed_fields',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'data'  => $apiKeys['allowedFields'] ?? '',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integration',
            ]
        );

        $resolver->setDefined(
            [
                'data_class'  => Integration::class,
            ]
        );
    }
}
