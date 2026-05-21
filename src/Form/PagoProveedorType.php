<?php

namespace App\Form;

use App\Entity\ModoPago;
use App\Entity\PagoProveedor;
use App\Entity\Proveedor;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PagoProveedorType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {

        $builder
            ->add('proveedor', EntityType::class, array(
                'required' => true,
                'label' => 'Proveedor',
                'class' => Proveedor::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.habilitado = :habilitado')
                        ->setParameter('habilitado', true)
                        ->orderBy('p.nombre', 'ASC');
                },
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione un proveedor',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '3'
                )
            ))
            ->add('monto', MoneyType::class, [
                'label' => 'Monto',
                'currency' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control monto-input',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                ]
            ])
            ->add('tipoMoneda', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Tipo de Moneda',
                'choices' => [
                    'Pesos Argentinos (ARS)' => 'ARS',
                    'Dólares Estadounidenses (USD)' => 'USD'
                ],
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '4'
                )
            ])
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
                    'tabindex' => '5'
                )
            ))
            ->add('fechaPago', DateType::class, array(
                'required' => true,
                'label' => 'Fecha',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '2'),
                'data' => new \DateTimeImmutable()
            ))
            ->add(
                'modoPago',
                EntityType::class,
                array(
                    'class' => ModoPago::class,
                    'required' => true,
                    'label' => 'Modo de pago',
                    'placeholder' => '-- Elija el modo de pago --',
                    'attr' => array(
                        'placeholder' => 'Escriba el modo de pago aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('numeroComprobante', TextType::class, array(
                'required' => false,
                'label' => 'Número de Comprobante',
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese número de comprobante',
                    'tabindex' => '1'
                )
            ))
            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'form-control'
                ]
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {

        $resolver->setDefaults([
            'data_class' => PagoProveedor::class,
        ]);
    }
}