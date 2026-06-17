<?php

namespace App\Form;

use App\Entity\Factura;
use App\Entity\ModoPago;
use App\Entity\Proveedor;
use App\Entity\TipoGrupo;
use App\Form\FacturaDetalleType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
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
            ->add('tipoGrupo', EntityType::class, array(
                'required' => true,
                'label' => 'Tipo de Grupo',
                'class' => TipoGrupo::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('tg')
                        ->where('tg.habilitado = :habilitado')
                        ->setParameter('habilitado', true)
                        ->orderBy('tg.nombre', 'ASC');
                },
                'choice_label' => 'nombre',
                'placeholder' => 'Seleccione un tipo de grupo',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '4'
                )
            ))
            ->add('detalle', FacturaDetalleType::class, [
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\FacturaDetalle'
            ])
            ->add('detalles', CollectionType::class, [
                'entry_type' => FacturaDetalleType::class,
                'allow_delete' => true,
                'allow_add' => true,
                'label' => 'Detalles',
                'prototype_name' => '__detalles__',
                'label_attr' => array('class' => 'hidden'),
                'attr' => array('class' => 'hidden')
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Factura::class,
        ]);
    }
}