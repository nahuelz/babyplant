<?php

namespace App\Form;

use App\Entity\TipoBandeja;
use App\Entity\TipoProducto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoProductoType extends AbstractType
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
            ->add('cantDiasCamara', IntegerType::class, array(
                    'required' => true,
                    'label' => 'Dias en Camara',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('cantDiasInvernaculo', IntegerType::class, array(
                    'required' => false,
                    'label' => 'Dias en Invernaculo',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add(
                'tipoBandeja',
                EntityType::class,
                array(
                    'class' => TipoBandeja::class,
                    'required' => false,
                    'label' => 'Bandeja estandar',
                    'placeholder' => '-- Elija la bandeja --',
                    'attr' => array(
                        'placeholder' => 'Escriba el tipo aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('habilitado', null, array(
                    'required' => false,
                    'label' => 'Habilitado',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('colorPicker', ColorType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('color', TextType::class, [
                'label' => 'O escribí el código hexadecimal',
                'required' => true,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoProducto::class,
        ]);
    }
}
