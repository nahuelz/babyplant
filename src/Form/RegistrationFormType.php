<?php

namespace App\Form;

use App\Entity\Grupo;
use App\Entity\TipoUsuario;
use App\Entity\Usuario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RegistrationFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add(
                'tipoUsuario',
                EntityType::class,
                array(
                    'class' => TipoUsuario::class,
                    'required' => true,
                    'label' => 'Tipo de Usuario',
                    'placeholder' => '-- Elija --',
                    'attr' => array(
                        'placeholder' => 'Escriba el tipo aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    )
                )
            )
            ->add('email', TextType::class, array(
                'attr' => array('class' => 'form-control'))
            )
            ->add('username', TextType::class, array(
                'label' => 'Usuario',
                'attr' => array('class' => 'form-control'))
            )
            ->add('nombre', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
            )
                ->add('apellido', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
                )
                ->add('cuit', TextType::class, array(
                    'attr' => array('class' => 'form-control'))
                )
                ->add('razonSocial', TextType::class, array(
                        'attr' => array('class' => 'form-control'),
                        'required'=>true)
                )
                ->add('plainPassword', PasswordType::class, [
                    // instead of being set onto the object directly,
                    // this is read and encoded in the controller
                    'mapped' => false,
                    'label' => 'Contraseña',
                    'constraints' => [
                        new NotBlank([
                            'message' => 'Por favor ingrese una contraseña',
                                ]),
                        new Length([
                            'min' => 6,
                            'minMessage' => 'La contraseña debe contener al menos {{ limit }} caracteres',
                            // max length allowed by Symfony for security reasons
                            'max' => 4096,
                                ]),
                    ],
                    'attr' => array('class' => 'form-control')
                ])
                ->add('grupos', EntityType::class, [
                    'class' => Grupo::class,
                    'required' => true,
                    'multiple' => true,
                    'label' => 'Grupos',
                    'placeholder' => '-- Elija grupo --',
                    'attr' => array(
                        'placeholder' => 'Escriba el grupo aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija grupo --',
                        'tabindex' => '5'),
                ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Usuario::class
        ]);
    }

}
