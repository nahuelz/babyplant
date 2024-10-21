<?php

namespace App\Form;

use App\Entity\TipoMesada;
use App\Entity\TipoProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoMesadaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, array(
                    'required' => true,
                    'label' => 'N° Mesada',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('capacidad', IntegerType::class, array(
                    'required' => true,
                    'label' => 'Capacidad',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
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
                    'placeholder' => '-- Elija el producto --',
                    'auto_initialize' => false)
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
            'data_class' => TipoMesada::class,
        ]);
    }
}
