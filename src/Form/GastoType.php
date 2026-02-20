<?php

namespace App\Form;

use App\Entity\Gasto;
use App\Entity\TipoConcepto;
use App\Entity\TipoProducto;
use App\Entity\TipoSubConcepto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            ->add('concepto', EntityType::class, array(
                    'label' => 'Tipo Concepto',
                    'class' => TipoConcepto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el concepto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el concepto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el concepto --',
                    'auto_initialize' => false)
            )
            ->add('subConcepto', EntityType::class, array(
                    'label' => 'Sub Concepto',
                    'class' => TipoSubConcepto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el sub concepto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el sub concepto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el sub concepto --',
                    'auto_initialize' => false)
            )
            ->add('monto', MoneyType::class, array(
                'required' => true,
                'label' => 'Monto',
                'currency' => '',  // O la moneda que uses
                'scale' => 2,
                'grouping' => false,
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