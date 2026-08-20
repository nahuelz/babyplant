<?php

namespace App\Form;

use App\Entity\Prestamo;
use DateTime;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestamoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fecha', DateType::class, [
                'required' => true,
                'label' => 'Fecha de Solicitud',
                'widget' => 'single_text',
                'html5' => true,
                'data' => new DateTime(),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('monto', NumberType::class, [
                'required' => true,
                'label' => 'Monto ($)',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'min' => '1',
                ],
            ])
            ->add('observaciones', TextareaType::class, [
                'required' => false,
                'label' => 'Observaciones / Motivo',
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestamo::class,
        ]);
    }
}
