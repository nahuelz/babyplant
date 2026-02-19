<?php

namespace App\Form;

use App\Entity\Gasto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GastoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fecha', DateType::class, array(
                'required' => true,
                'label' => 'Fecha',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '1'),
                'data' => new \DateTime() // Fecha actual por defecto
            ))
            ->add('concepto', null, array(
                'required' => true,
                'label' => 'Concepto',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '2')
            ))
            ->add('monto', MoneyType::class, array(
                'required' => true,
                'label' => 'Monto',
                'currency' => '',  // O la moneda que uses
                'attr' => array(
                    'class' => 'form-control monto-input',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                    'placeholder' => '0,00',
                    'tabindex' => '6'
                )
            ))
            ->add('modoPago', null, array(
                'required' => true,
                'label' => 'Modo de Pago',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '4')
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gasto::class,
        ]);
    }
}