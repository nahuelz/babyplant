<?php

namespace App\Form;

use App\Entity\RazonSocial;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RazonSocialType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('cuit', TextType::class, array(
                    'label' => 'Cuit Razon Social',
                    'required' => true,
                    'attr' => array('class' => 'form-control'))
            )
            ->add('razonSocial', TextType::class, array(
                    'required' => true,
                    'attr' => array('class' => 'form-control'))
            );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => RazonSocial::class,
        ]);
    }

}