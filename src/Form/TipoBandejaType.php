<?php

namespace App\Form;

use App\Entity\TipoBandeja;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoBandejaType extends AbstractType
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
            ->add('estandar', ChoiceType::class, array(
                    'label' => 'Estandar',
                    'choices' => array(
                        'No' => false,
                        'Si' => true
                    ),
                    'required' => true,
                    'placeholder' => '-- Elija --',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoBandeja::class,
        ]);
    }
}
