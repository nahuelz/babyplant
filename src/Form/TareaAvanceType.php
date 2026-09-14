<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class TareaAvanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $porcentajes = range(0, 100, 10);

        $builder
            ->add('porcentajeAvance', ChoiceType::class, [
                'label' => 'Porcentaje de avance',
                'choices' => array_combine(
                    array_map(static fn (int $porcentaje): string => $porcentaje . '%', $porcentajes),
                    $porcentajes
                ),
            ])
            ->add('observacionAvance', TextareaType::class, [
                'label' => 'Observación',
                'required' => false,
                'attr' => ['rows' => 5],
            ]);
    }
}
