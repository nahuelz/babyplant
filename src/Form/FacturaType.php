<?php

namespace App\Form;

use App\Entity\Factura;
use App\Entity\TipoConcepto;
use App\Entity\TipoSubConcepto;
use App\Entity\ModoPago;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroFactura', TextType::class, array(
                'required' => true,
                'label' => 'Número de Factura',
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese número de factura',
                    'tabindex' => '1'
                )
            ))
            ->add('fecha', DateType::class, array(
                'required' => true,
                'label' => 'Fecha',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '2'),
                'data' => new \DateTime()
            ))
            ->add('concepto', EntityType::class, array(
                    'label' => 'Tipo Concepto',
                    'class' => TipoConcepto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el concepto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el concepto --',
                        'tabindex' => '3'
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
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija el sub concepto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el sub concepto --',
                        'tabindex' => '4'
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
            ->add('monto', null, array(
                    'required' => true,
                    'label' => 'Monto',
                    'attr' => array(
                        'class' => 'form-control monto-input',
                        'style' => 'font-size: 1.1rem; font-weight: bold;',
                        'tabindex' => '5'))
            )
            ->add('modoPago', EntityType::class, array(
                'required' => true,
                'label' => 'Modo de Pago',
                'class' => ModoPago::class,
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '6')
            ))
            ->add('tipoCambio', MoneyType::class, array(
                'required' => false,
                'label' => 'Tipo de Cambio',
                'currency' => '',
                'scale' => 2,
                'grouping' => false,
                'attr' => array(
                    'class' => 'form-control monto-input',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                    'placeholder' => '0,00',
                    'tabindex' => '7'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Factura::class,
        ]);
    }
}