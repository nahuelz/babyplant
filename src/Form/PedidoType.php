<?php

namespace App\Form;


use App\Entity\Pedido;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PedidoType extends AbstractType {

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
                'label' => 'Cliente',
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
            ->add('pedidoProducto', PedidoProductoType::class, array(
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\PedidoProducto',
            ))
            ->add('pedidosProductos', CollectionType::class, array(
                    'entry_type' => PedidoProductoType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => '',
                    'prototype_name' => '__pedidos_productos__',
                    'label_attr' => array('class' => 'hidden'),
                    'attr' => array('class' => 'hidden'))
            )
            ->add('observacion', TextareaType::class, array(
                    'required' => false,
                    'label' => 'Observaciones',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Pedido::class,
        ]);
    }

}
