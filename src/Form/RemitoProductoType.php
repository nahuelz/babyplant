<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\RemitoProducto;
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

class RemitoProductoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('pedidoProducto', EntityType::class, array(
                'label' => 'Producto',
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
            ->add('cantBandejas', TextType::class, array(
                'attr' => array(
                    'placeholder' => 'Escriba la cantidad de bandejas',
                    'class' => 'form-control')
                )
            )
            ->add('precioUnitario', null, array(
                    'required' => true,
                    'label' => 'Precio unitario',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => RemitoProducto::class,
        ]);
    }

}
