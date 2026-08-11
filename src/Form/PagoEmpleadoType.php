<?php

namespace App\Form;

use App\Entity\PagoEmpleado;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PagoEmpleadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('importe', NumberType::class, [
                'required' => true,
                'label' => 'Importe',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'pattern' => '[0-9]+([,][0-9]+)?',
                    'readonly' => 'readonly',
                ],
            ])
            ->add('fecha', DateType::class, [
                'required' => true,
                'label' => 'Fecha',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('observaciones', TextareaType::class, [
                'required' => false,
                'label' => 'Observaciones',
                'attr' => ['class' => 'form-control', 'rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PagoEmpleado::class,
        ]);
    }
}
