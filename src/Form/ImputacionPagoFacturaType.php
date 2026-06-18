<?php

namespace App\Form;

use App\Entity\Factura;
use App\Entity\ImputacionPagoFactura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImputacionPagoFacturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('factura', EntityType::class, [
                'required' => false,
                'label' => 'Factura',
                'class' => Factura::class,
                'placeholder' => 'Seleccione una factura',
                'choice_label' => 'numeroFactura',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('monto', MoneyType::class, [
                'required' => false,
                'label' => 'Monto a imputar',
                'currency' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control monto-input',
                    'placeholder' => '0,00',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ImputacionPagoFactura::class,
        ]);
    }
}
