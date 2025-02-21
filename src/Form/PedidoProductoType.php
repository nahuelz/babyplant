<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\TipoBandeja;
use App\Entity\TipoOrigenSemilla;
use App\Entity\TipoProducto;
use App\Entity\TipoSubProducto;
use App\Entity\TipoVariedad;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PedidoProductoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('tipoProducto', EntityType::class, array(
                    'label' => 'Producto',
                    'class' => TipoProducto::class,
                    'required' => true,
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija el producto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el producto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el producto --',
                    'auto_initialize' => false)
            )
            ->add('tipoSubProducto', EntityType::class, array(
                    'label' => 'Sub Producto',
                    'class' => TipoSubProducto::class,
                    'required' => true,
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija el sub producto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el sub producto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el sub producto --',
                    'auto_initialize' => false)
            )
            ->add('tipoVariedad', EntityType::class, array(
                    'class' => TipoVariedad::class,
                    'label' => 'Variedad',
                    'required' => true,
                    'choice_label' => function ($variedad) {
                        return $variedad->getNombre();
                    },
                    'attr' => array(
                        'placeholder' => '-- Elija la variedad --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la variedad --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la variedad --',
                    'auto_initialize' => false)
            )
            ->add(
                'tipoBandeja',
                EntityType::class,
                array(
                    'class' => TipoBandeja::class,
                    'required' => true,
                    'label' => 'Bandeja',
                    'placeholder' => '-- Elija la bandeja --',
                    'attr' => array(
                        'placeholder' => 'Escriba el tipo aquí.',
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
            ->add('cantSemillas', TextType::class, array(
                    'required' => true,
                    'label' => 'Cantidad de semillas',
                    'attr' => array(
                        'placeholder' => 'Cantidad de semillas necesarias',
                        'class' => 'form-control',
                        'readonly' => 'true',
                        'tabindex' => '5'))
            )
            ->add('cantidadBandejasPedidas', TextType::class, array(
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas',
                        'class' => 'form-control'))
            )
            ->add(
                'tipoOrigenSemilla',
                EntityType::class,
                array(
                    'class' => TipoOrigenSemilla::class,
                    'required' => true,
                    'label' => 'Tipo origen semilla',
                    'placeholder' => '-- Elija el origen de la semilla --',
                    'attr' => array(
                        'placeholder' => '-- Elija el origen de la semilla --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el origen de la semilla --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('fechaSiembraPedido', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'years' => range(2024, 2050),
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Fecha de Siembra',
                    'placeholder' => 'Escriba la fecha de siembra',
                    'attr' => array(
                        'readonly' => 'true',
                        'disabled' => 'disabled',
                        'placeholder' => 'Escriba la fecha de siembra',
                        'class' => 'form-control datepicker',
                        'tabindex' => '5'
                    ))
            )
            ->add('fechaEntregaPedido', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Fecha de Entrega',
                    'attr' => array(
                        'placeholder' => 'Fecha de Entrega',
                        'class' => 'form-control datepicker',
                        'data-date-start-date' => "+20d",
                        'tabindex' => '5'
                    ))
            )
            ->add('cantDiasProduccion', TextType::class, array(
                    'label' => 'Dias en produccion',
                    'required' => true,
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('cantDiasProduccionSelect', ChoiceType::class, array(
                    'label' => 'Dias en produccion',
                    'required' => false,
                    'mapped' => false,
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'),
                    'choices' => array(
                        '20 días' => 20,
                        '28 días' => 28,
                        '30 días' => 30,
                        '40 días' => 40,
                        '50 días' => 50,
                        '60 días' => 60,
                        '70 días' => 70,
                        '80 días' => 80,
                        '90 días' => 90
                    ),
                    'data' => 20
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => PedidoProducto::class,
        ]);
    }

}
