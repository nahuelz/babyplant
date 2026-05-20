<?php

namespace App\Form;

use App\Entity\Proveedor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProveedorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class, array(
                    'required' => true,
                    'label' => 'Nombre / Razón Social',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '1'))
            )
            ->add('cuit', TextType::class, array(
                    'required' => false,
                    'label' => 'CUIT/CUIL',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '2'))
            )
            ->add('email', EmailType::class, array(
                    'required' => false,
                    'label' => 'Email',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '3'))
            )
            ->add('telefono', TextType::class, array(
                    'required' => false,
                    'label' => 'Teléfono',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '4'))
            )
            ->add('direccion', TextType::class, array(
                    'required' => false,
                    'label' => 'Dirección',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('condicionIva', TextType::class, array(
                    'required' => false,
                    'label' => 'Condición IVA',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '6'))
            )
            ->add('observaciones', TextareaType::class, array(
                    'required' => false,
                    'label' => 'Observaciones',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '7',
                        'rows' => 4))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Proveedor::class,
        ]);
    }
}
