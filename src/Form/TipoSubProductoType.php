<?php

namespace App\Form;

use App\Entity\TipoProducto;
use App\Entity\TipoSubProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoSubProductoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipoProducto', EntityType::class, array(
                    'label' => 'Producto',
                    'class' => TipoProducto::class,
                    'required' => true,
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
                    'placeholder' => '-- Elija el producto --'
                )
            )
            ->add('nombre', TextType::class, array(
                    'required' => true,
                    'label' => 'Sub producto',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('habilitado', null, array(
                    'required' => false,
                    'label' => 'Habilitado',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoSubProducto::class,
        ]);
    }
}
