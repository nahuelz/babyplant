<?php

namespace App\Form;

use App\Entity\Banco;
use App\Entity\Categoria;
use App\Entity\Empleado;
use App\Entity\ObraSocial;
use App\Entity\TipoModalidadPago;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmpleadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, array(
                    'required' => true,
                    'label' => 'Nombre',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('apellido', TextType::class, array(
                    'required' => true,
                    'label' => 'Apellido',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('nacionalidad', TextType::class, array(
                    'required' => false,
                    'label' => 'Nacionalidad',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('dni', TextType::class, array(
                    'required' => true,
                    'label' => 'DNI',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('cuil', TextType::class, array(
                    'required' => false,
                    'label' => 'CUIL',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('fechaIngreso', DateType::class, array(
                    'required' => true,
                    'label' => 'Fecha de ingreso',
                    'widget' => 'single_text',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('categoria', EntityType::class, array(
                    'label' => 'Categoría',
                    'class' => Categoria::class,
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija la categoría --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la categoría --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la categoría --',
                    'auto_initialize' => false)
            )
            ->add('obraSocial', EntityType::class, array(
                    'label' => 'Obra social',
                    'class' => ObraSocial::class,
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija la obra social --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la obra social --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la obra social --',
                    'auto_initialize' => false)
            )
            ->add('banco', EntityType::class, array(
                    'label' => 'Banco',
                    'class' => Banco::class,
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija el banco --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el banco --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el banco --',
                    'auto_initialize' => false)
            )
            ->add('telefono', TextType::class, array(
                    'required' => false,
                    'label' => 'Teléfono',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('modalidadPago', EntityType::class, array(
                    'label' => 'Modalidad de pago',
                    'class' => TipoModalidadPago::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija la modalidad de pago --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la modalidad de pago --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label required'),
                    'placeholder' => '-- Elija la modalidad de pago --',
                    'auto_initialize' => false)
            )
            ->add('observaciones', TextareaType::class, array(
                    'required' => false,
                    'label' => 'Observaciones',
                    'attr' => array(
                        'class' => 'form-control',
                        'rows' => 3,
                        'tabindex' => '5'))
            )
            ->add('activo', null, array(
                    'required' => false,
                    'label' => 'Activo',
                    'data' => true,
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Empleado::class,
        ]);
    }
}
