<?php

namespace App\Form;


use App\Entity\Entrega;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntregaType extends AbstractType {

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
            ->add('clienteEntrega', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'label' => 'CLIENTE A ENTREGAR',
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
            ->add('entregaProducto', EntregaProductoType::class, array(
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\EntregaProducto',
            ))
            ->add('entregasProductos', CollectionType::class, array(
                    'entry_type' => EntregaProductoType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => '',
                    'prototype_name' => '__entregas_productos__',
                    'label_attr' => array('class' => 'hidden'),
                    'attr' => array('class' => 'hidden'))
            )
            ->add('remito', RemitoType::class, array(
                'required' => false
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Entrega::class,
        ]);
    }

}
