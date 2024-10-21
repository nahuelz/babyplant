<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalidaCamaraType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mesada', PedidoProductoMesadaType::class, array(
                'required' => false,
                'mapped' => false,
                'data_class' => 'App\Entity\PedidoProductoMesada',
            ))
            ->add('mesadas', CollectionType::class, array(
                    'entry_type' => PedidoProductoMesadaType::class,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'label' => '',
                    'prototype_name' => '__mesadas__',
                    'label_attr' => array('class' => 'hidden'),
                    'attr' => array('class' => 'hidden'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PedidoProducto::class,
        ]);
    }
}
