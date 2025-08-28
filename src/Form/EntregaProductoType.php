<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\EntregaProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntregaProductoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('pedidoProducto', EntityType::class, array(
                    'label' => 'PRODUCTOS DEL CLIENTE EN EL INVERNACULO',
                    'class' => pedidoProducto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el pedido producto --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el pedido producto --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.id', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el pedido producto --',
                    'auto_initialize' => false)
            )
            ->add('precioUnitario', null, array(
                    'required' => false,
                    'label' => 'PRECIO UNITARIO',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('cantidadBandejas', null, array(
                    'required' => true,
                    'label' => 'CANTIDAD DE BANDEJAS A ENTREGAR',
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas',
                        'min' => 1,
                        'class' => 'form-control')
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => EntregaProducto::class,
        ]);
    }

}
