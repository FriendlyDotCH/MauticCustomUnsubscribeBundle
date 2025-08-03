<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\ConfigIntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

/**
 * @extends AbstractType<Integration>
 */
class ConfigType extends AbstractType
{
    public function __construct(
        private ConfigIntegrationsHelper $integrationsHelper
    ) {
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationInstance = $options['integration'] ?? null;
        $config              = $integrationInstance?->getIntegrationConfiguration()?->getApiKeys();

        $builder->add(
            'nhi',
            TextType::class,
            [
                'label'       => 'mautic.friendlyunsubscribe.nhi',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'data'        => $config['nhi'] ?? 3,
                'constraints' => [
                    new Type(['type' => 'integer']),
                    new NotBlank(),
                    new GreaterThanOrEqual(['value' => 1]),
                ],
            ]
        );

        $builder->get('nhi')->addModelTransformer(new CallbackTransformer(
            function ($originalValue) {
                // Transform the integer to string for the form field
                return (string) $originalValue;
            },
            function ($submittedValue) {
                // Transform the submitted string back to integer
                return (int) $submittedValue;
            }
        ));

        $builder->add(
            'fields',
            TextType::class,
            [
                'label'      => 'mautic.friendlyunsubscribe.fields',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'data'       => $config['fields'] ?? '',
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
