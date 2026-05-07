<?php

namespace App\Form;

use App\Entity\Factura;
use App\Entity\ModoPago;
use App\Form\FacturaDetalleType;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FacturaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroFactura', TextType::class, array(
                'required' => true,
                'label' => 'Número de Factura',
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese número de factura',
                    'tabindex' => '1'
                )
            ))
            ->add('fecha', DateType::class, array(
                'required' => true,
                'label' => 'Fecha',
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '2'),
                'data' => new \DateTime()
            ))
            ->add('detalle', FacturaDetalleType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false
            ])
            ->add('tipoMoneda', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'label' => 'Tipo de Moneda',
                'choices' => [
                    'Pesos Argentinos (ARS)' => 'ARS',
                    'Dólares Estadounidenses (USD)' => 'USD'
                ],
                'attr' => array(
                    'class' => 'form-control',
                    'tabindex' => '3'
                )
            ])
            ->add('tipoCambio', MoneyType::class, array(
                'required' => false,
                'label' => 'Tipo de Cambio',
                'currency' => '',
                'scale' => 2,
                'grouping' => false,
                'attr' => array(
                    'class' => 'form-control monto-input',
                    'style' => 'font-size: 1.1rem; font-weight: bold;',
                    'placeholder' => '0,00',
                    'tabindex' => '4'
                )
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Factura::class,
        ]);
    }
}