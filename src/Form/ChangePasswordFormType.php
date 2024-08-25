<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, array(
                'required' => true,
                'mapped' => false,
                'label' => 'Contraseña actual',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '5', 'placeholder' => 'Ingrese la contraseña actual'))
            )  
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Please enter a password',
                        ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'La contraseña debe contener al menos {{ limit }} caracteres',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                        ]),
                    ],
                    'label' => 'Nueva contraseña',
                    'attr' => array('class' => 'form-control', 'tabindex' => '5', 'placeholder' => 'Ingrese la nueva contraseña')
                ],
                'second_options' => [
                    'label' => 'Repita contraseña',
                    'attr' => array('class' => 'form-control', 'tabindex' => '5', 'placeholder' => 'Repita la nueva contraseña')
                ],
                'invalid_message' => 'The password fields must match.',
                // Instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true
        ]);
    }
}
