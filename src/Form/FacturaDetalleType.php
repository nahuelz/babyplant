<?php

namespace App\Form;

use App\Entity\FacturaDetalle;
use App\Entity\TipoConcepto;
use App\Entity\TipoGrupo;
use App\Entity\TipoSubConcepto;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturaDetalleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tipoGrupo', EntityType::class, [
                'label' => 'Tipo Grupo',
                'class' => TipoGrupo::class,
                'required' => true,
                'attr' => [
                    'placeholder' => '-- Elija el tipo grupo --',
                    'class' => 'form-control choice tipogrupo-select',
                    'data-placeholder' => '-- Elija el tipo grupo --',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('tg')
                        ->where('tg.habilitado = 1')
                        ->orderBy('tg.nombre', 'ASC');
                },
                'label_attr' => ['class' => 'control-label required'],
                'placeholder' => '-- Elija el tipo grupo --',
            ])
            ->add('concepto', EntityType::class, [
                'label' => 'Concepto',
                'class' => TipoConcepto::class,
                'required' => true,
                'attr' => [
                    'placeholder' => '-- Elija el concepto --',
                    'class' => 'form-control choice concepto-select',
                    'data-placeholder' => '-- Elija el concepto --',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('x')
                        ->where('x.habilitado = 1')
                        ->andWhere('x.tipo IN (:tipos)')
                        ->setParameter('tipos', [TipoConcepto::TIPO_FACTURA, TipoConcepto::TIPO_AMBOS])
                        ->orderBy('x.nombre', 'ASC');
                },
                'label_attr' => ['class' => 'control-label required'],
                'placeholder' => '-- Elija el concepto --',
            ])
            ->add('subConcepto', EntityType::class, [
                'label' => 'Sub Concepto',
                'class' => TipoSubConcepto::class,
                'required' => false,
                'attr' => [
                    'placeholder' => '-- Elija el sub concepto --',
                    'class' => 'form-control choice subconcepto-select',
                    'data-placeholder' => '-- Elija el sub concepto --',
                ],
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('x')
                        ->innerJoin('x.tipoConcepto', 'tc')
                        ->where('x.habilitado = 1')
                        ->andWhere('tc.tipo IN (:tipos)')
                        ->setParameter('tipos', [TipoConcepto::TIPO_FACTURA, TipoConcepto::TIPO_AMBOS])
                        ->orderBy('x.nombre', 'ASC');
                },
                'label_attr' => ['class' => 'control-label'],
                'placeholder' => '-- Elija el sub concepto --',
            ])
            ->add('cantidad', IntegerType::class, [
                'required' => true,
                'label' => 'Cantidad',
                'label_attr' => ['class' => 'required'],
                'attr' => [
                    'class' => 'form-control detalle-cantidad',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                    'placeholder' => '0',
                    'min' => 1,
                ]
            ])
            ->add('precioUnitario', MoneyType::class, [
                'required' => true,
                'label' => 'Precio unitario',
                'label_attr' => ['class' => 'required'],
                'currency' => '',
                'scale' => 2,
                'grouping' => false,
                'attr' => [
                    'class' => 'form-control monto-input detalle-precio-unitario',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                    'placeholder' => '0,00',
                ]
            ])
            ->add('descripcion', TextType::class, [
                'required' => false,
                'label' => 'Descripción',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese una descripción opcional',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FacturaDetalle::class,
            'allow_extra_fields' => true,
        ]);
    }
}
