<?php

namespace App\Form;

use App\Entity\ConceptoLiquidacion;
use App\Entity\TipoConceptoLiquidacion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConceptoLiquidacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('tipoConceptoLiquidacion', EntityType::class, [
                'class' => TipoConceptoLiquidacion::class,
                'choice_label' => 'nombre',
                'placeholder' => '-- Elija el tipo de concepto --',
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('t')
                        ->where('t.habilitado = 1')
                        ->andWhere('t.fechaBaja IS NULL')
                        ->orderBy('t.nombre', 'ASC');
                },
                'required' => true,
                'label' => 'Concepto',
                'attr' => ['class' => 'form-control choice concepto-tipo'],
                'choice_attr' => function (TipoConceptoLiquidacion $tipo) {
                    return [
                        'data-tipo' => $tipo->getTipo(),
                        'data-codigo-interno' => $tipo->getCodigoInterno(),
                    ];
                },
            ])
            ->add('cantidad', NumberType::class, [
                'required' => true,
                'label' => 'Cantidad',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control concepto-cantidad',
                    'pattern' => '[0-9]+([,][0-9]+)?',
                ],
            ])
            ->add('valorUnitario', NumberType::class, [
                'required' => true,
                'label' => 'Valor unitario',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control concepto-valor',
                    'pattern' => '[0-9]+([,][0-9]+)?',
                ],
            ])
            ->add('importe', NumberType::class, [
                'required' => false,
                'label' => 'Importe',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control concepto-importe',
                    'readonly' => 'readonly',
                ],
            ])
            ->add('descripcion', TextType::class, [
                'required' => false,
                'label' => 'Descripción',
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ConceptoLiquidacion::class,
        ]);
    }
}
