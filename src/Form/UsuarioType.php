<?php

namespace App\Form;

use App\Entity\Enum\CondicionIva;
use App\Entity\Enum\TipoDocumento;
use App\Entity\Grupo;
use App\Entity\RazonSocial;
use App\Entity\TipoUsuario;
use App\Entity\Usuario;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                    'attr' => array('class' => 'form-control',
                        'required'=>true))
            )
            ->add('apellido', TextType::class, array(
                    'attr' => array('class' => 'form-control'),
                    'required'=>true)
            )
            ->add('tipoDocumento', ChoiceType::class, [
                'required' => false,
                'label' => 'Tipo de Documento',
                'choices' => TipoDocumento::getChoices(),
                'choice_value' => fn (?TipoDocumento $choice) => $choice?->value,
                'data' => $options['data']?->getTipoDocumento() ?? TipoDocumento::CUIT,
                'attr' => [
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija --',
                    'tabindex' => '5',
                ],
            ])
            ->add('cuit', TextType::class, array(
                    'label' => 'N° Documento',
                    'attr' => array('class' => 'form-control'),
                    'required'=>true)
            )
            ->add('domicilio', TextType::class, array(
                    'required' => false,
                    'attr' => array('class' => 'form-control'))
            )
            ->add('celular', TextType::class, array(
                    'required' => false,
                    'attr' => array('class' => 'form-control'))
            )
            ->add('condicionIva', ChoiceType::class, [
                'required' => false,
                'label' => 'Condición IVA',
                'choices' => CondicionIva::getChoices(),
                'choice_value' => fn (?CondicionIva $choice) => $choice?->value,
                'placeholder' => '-- Elija --',
                'attr' => [
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija --',
                    'tabindex' => '5',
                ],
            ])
            ->add('tieneRazonSocial', ChoiceType::class, array(
                    'required' => true,
                    'label' => '¿Tiene razon social?',
                    'choices' => array(
                        'No' => false,
                        'Si' => true
                    ),
                    'placeholder' => '-- Elija --',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'tabindex' => '5'))
            )
            ->add('razonSocial', EntityType::class, array(
                'class' => RazonSocial::class,
                'required' => false,
                'label' => 'Razon Social',
                'placeholder' => '-- Elija --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija --',
                    'tabindex' => '5'
                ),
            ))
            ->add('grupos', EntityType::class,[
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class
        ]);
    }
}