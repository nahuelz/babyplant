<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\TipoBandeja;
use App\Entity\TipoOrigenSemilla;
use App\Entity\TipoProducto;
use App\Entity\TipoSubProducto;
use App\Entity\TipoUsuario;
use App\Entity\TipoVariedad;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PedidoProductoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('tipoProducto', EntityType::class, array(
                    'label' => 'Tipo Producto',
                    'class' => TipoProducto::class,
                    'required' => true,
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija --',
                    'auto_initialize' => false)
            )
            ->add('tipoSubProducto', EntityType::class, array(
                    'label' => 'Tipo Sub Producto',
                    'class' => TipoSubProducto::class,
                    'required' => true,
                    'mapped' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija --',
                    'auto_initialize' => false)
            )
            ->add('tipoVariedad', EntityType::class, array(
                    'class' => TipoVariedad::class,
                    'label' => 'Tipo Variedad',
                    'required' => true,
                    'choice_label' => function ($variedad) {
                        return $variedad->getNombre();
                    },
                    'attr' => array(
                        'placeholder' => '-- Elija --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija --',
                    'auto_initialize' => false)
            )
            ->add(
                'tipoBandeja',
                EntityType::class,
                array(
                    'class' => TipoBandeja::class,
                    'required' => true,
                    'label' => 'Tipo de Bandeja',
                    'placeholder' => '-- Elija --',
                    'attr' => array(
                        'placeholder' => 'Escriba el tipo aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    )
                )
            )
            ->add('cantSemillas', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
            )
            ->add('cantBandejas', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
            )
            ->add(
                'origenSemilla',
                EntityType::class,
                array(
                    'class' => TipoOrigenSemilla::class,
                    'required' => true,
                    'label' => 'Tipo origen semilla',
                    'placeholder' => '-- Elija --',
                    'attr' => array(
                        'placeholder' => 'Escriba el tipo aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    )
                )
            )
            ->add('fechaSiembra', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'years' => range(2024, 2050),
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Fecha de Siembra',
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'data-date-end-date' => "0d",
                        'tabindex' => '5'
                    ))
            )
            ->add('fechaEntrega', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'years' => range(2024, 2050),
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Fecha de Entrega',
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'data-date-end-date' => "0d",
                        'tabindex' => '5'
                    ))
            )
            ->add('cantDiasProduccion', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => PedidoProducto::class,
        ]);
    }

}
