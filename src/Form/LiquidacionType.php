<?php

namespace App\Form;

use App\Entity\ConceptoLiquidacion;
use App\Entity\Liquidacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LiquidacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonlyAttr = $options['editable'] ? [] : ['readonly' => 'readonly'];
        $disabledAttr = $options['editable'] ? [] : ['disabled' => 'disabled'];

        if ($options['incluir_sueldo']) {
            $builder
                ->add('sueldoBruto', NumberType::class, [
                    'required' => true,
                    'label' => 'Sueldo bruto',
                    'scale' => 2,
                    'attr' => array_merge([
                        'class' => 'form-control',
                        'pattern' => '[0-9]+([,][0-9]+)?',
                    ], $readonlyAttr),
                ])
                ->add('deducciones', NumberType::class, [
                    'required' => true,
                    'label' => 'Deducciones',
                    'scale' => 2,
                    'attr' => array_merge([
                        'class' => 'form-control',
                        'pattern' => '[0-9]+([,][0-9]+)?',
                    ], $readonlyAttr),
                ]);
        }

        $builder->add('observaciones', TextareaType::class, [
            'required' => false,
            'label' => 'Observaciones',
            'attr' => array_merge(['class' => 'form-control', 'rows' => 3], $readonlyAttr),
        ]);

        if ($options['incluir_conceptos']) {
            $builder
                ->add('concepto', ConceptoLiquidacionType::class, [
                    'required' => false,
                    'mapped' => false,
                    'data_class' => ConceptoLiquidacion::class,
                    'label' => false,
                    'validation_groups' => false,
                ])
                ->add('conceptos', CollectionType::class, [
                    'entry_type' => ConceptoLiquidacionType::class,
                    'entry_options' => [
                        'label' => false,
                        'attr' => ['class' => 'concepto-item'],
                        'validation_groups' => false,
                    ],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'by_reference' => false,
                    'prototype' => false,
                    'mapped' => false,
                    'label' => false,
                    'attr' => array_merge(['class' => 'hidden'], $disabledAttr),
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Liquidacion::class,
            'incluir_sueldo' => true,
            'incluir_conceptos' => true,
            'editable' => true,
        ]);

        $resolver->setAllowedTypes('incluir_sueldo', 'bool');
        $resolver->setAllowedTypes('incluir_conceptos', 'bool');
        $resolver->setAllowedTypes('editable', 'bool');
    }
}
