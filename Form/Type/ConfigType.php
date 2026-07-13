<?php

declare(strict_types=1);

namespace MauticPlugin\MauticUnsubscribeBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
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
    /**
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                    new Type('integer'),
                    new NotBlank(),
                    new GreaterThanOrEqual(['value' => 1]),
                ],
            ]
        );

        $builder->get('nhi')->addModelTransformer(new CallbackTransformer(
            fn ($originalValue) =>
                // Transform the integer to string for the form field
                (string) $originalValue,
            fn ($submittedValue) =>
                // Transform the submitted string back to integer
                (int) $submittedValue
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
