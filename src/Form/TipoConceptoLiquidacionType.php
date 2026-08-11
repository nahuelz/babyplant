<?php

namespace App\Form;

use App\Entity\TipoConceptoLiquidacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoConceptoLiquidacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, [
                'required' => true,
                'label' => 'Nombre',
                'attr' => [
                    'class' => 'form-control',
                    'tabindex' => '5',
                ],
            ])
            ->add('descripcion', TextType::class, [
                'required' => false,
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'tabindex' => '5',
                ],
            ])
            ->add('codigoInterno', TextType::class, [
                'required' => true,
                'label' => 'Código interno',
                'attr' => [
                    'class' => 'form-control',
                    'tabindex' => '5',
                ],
            ])
            ->add('tipo', ChoiceType::class, [
                'required' => true,
                'label' => 'Tipo',
                'choices' => [
                    'Ingreso' => TipoConceptoLiquidacion::INGRESO,
                    'Descuento' => TipoConceptoLiquidacion::DESCUENTO,
                ],
                'placeholder' => '-- Seleccione el tipo --',
                'attr' => [
                    'class' => 'form-control',
                    'tabindex' => '5',
                ],
            ])
            ->add('habilitado', null, [
                'required' => false,
                'label' => 'Habilitado',
                'data' => true,
                'attr' => [
                    'class' => 'form-control',
                    'tabindex' => '5',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoConceptoLiquidacion::class,
        ]);
    }
}
