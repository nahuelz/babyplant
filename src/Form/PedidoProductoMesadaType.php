<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\PedidoProductoMesada;
use App\Entity\TipoMesada;
use App\Entity\TipoProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PedidoProductoMesadaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('tipoMesada', EntityType::class, array(
                    'label' => 'Tipo Mesada',
                    'mapped' => false,
                    'class' => TipoProducto::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija el tipo mesada --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija el tipo mesada --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija el tipo mesada --',
                    'auto_initialize' => false)
            )
            ->add('mesada', EntityType::class, array(
                    'label' => 'Mesada',
                    'mapped' => true,
                    'class' => TipoMesada::class,
                    'required' => true,
                    'attr' => array(
                        'placeholder' => '-- Elija la mesada --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la mesada --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la mesada --',
                    'auto_initialize' => false)
            )
            ->add('cantidadBandejas', IntegerType::class, array(
                    'required' => true,
                    'mapped' => true,
                    'label' => 'Cantidad Bandejas',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => PedidoProductoMesada::class,
        ]);
    }

}
