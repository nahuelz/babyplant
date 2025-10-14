<?php

namespace App\Form;

use App\Entity\PedidoProducto;
use App\Entity\Mesada;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SalidaCamaraType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mesadaUno', MesadaType::class)
            ->add('mesadaDos', MesadaType::class)
            ->add('observacionCamara', null, [
                'label' => 'Observación de Cámara',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingrese observaciones de la cámara...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PedidoProducto::class,
        ]);
    }
}
