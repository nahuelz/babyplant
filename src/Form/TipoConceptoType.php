<?php

namespace App\Form;

use App\Entity\TipoConcepto;
use App\Entity\TipoGrupo;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TipoConceptoType extends AbstractType
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
            ->add('tipoGrupo', EntityType::class, array(
                'required' => false,
                'label' => 'Grupo',
                'class' => TipoGrupo::class,
                'choice_label' => 'nombre',
                'placeholder' => '-- Seleccione un grupo --',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '5'))
            )
            ->add('tipo', ChoiceType::class, array(
                    'required' => true,
                    'label' => 'Tipo',
                    'choices' => [
                        'Factura' => TipoConcepto::TIPO_FACTURA,
                        'Gasto'   => TipoConcepto::TIPO_GASTO,
                        'Ambos'   => TipoConcepto::TIPO_AMBOS,
                    ],
                    'placeholder' => '-- Elija el tipo --',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('habilitado', null, array(
                    'required' => false,
                    'label' => 'Habilitado',
                    'data' => true,
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TipoConcepto::class,
        ]);
    }
}
