<?php

namespace App\Form;


use App\Entity\Entrega;
use App\Entity\Remito;
use App\Entity\TipoBandeja;
use App\Entity\TipoDescuento;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemitoType extends AbstractType {

    private $entityManager;

    /**
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('entrega', RemitoEntregaType::class, array(
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\Entrega',
            ))
            ->add('entregas', CollectionType::class, array(
                    'required' => false,
                    'mapped' => true,
                    'entry_type' => RemitoEntregaType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => '',
                    'prototype_name' => '__entregas__',
                    'label_attr' => array('class' => 'hidden'),
                    'attr' => array('class' => 'hidden'))
            )
            ->add('cliente', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'label' => 'CLIENTE',
                'placeholder' => '-- Elija --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija --',
                    'tabindex' => '5'
                ),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('x')
                        ->where('x.tipoUsuario = 1')
                        ->andWhere('x.habilitado = 1')
                        ->orderBy('x.apellido', 'ASC');
                },
            ))
            ->add(
                'tipoDescuento',
                EntityType::class,
                array(
                    'class' => TipoDescuento::class,
                    'required' => false,
                    'placeholder' => 'SIN DESCUENTO',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('cantidadDescuento', IntegerType::class, array(
                    'required' => false,
                    'constraints' => [
                        new LessThanOrEqual([
                            'value' => 100,
                            'message' => 'El porcentaje debe ser menor o igual a 100',
                        ]),
                        new GreaterThanOrEqual([
                            'value' => 1,
                            'message' => 'El porcentaje debe ser mayor o igual a 1',
                        ]),
                    ],
                    'attr' => array(
                        'placeholder' => 'Ingrese el porcentaje',
                        'class' => 'form-control',
                        'tabindex' => '5',
                        'min' => '1',
                        'max' => '100',
                        'oninput' => 'this.value = this.value.replace(/[^0-9]/g, "").slice(0, 3)'))
            )
            ->add('motivoDescuento', TextareaType::class, array(
                    'required' => false,
                    'attr' => array(
                        'rows' => 1,
                        'placeholder' => 'Descripción',
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Remito::class,
        ]);
    }

}
