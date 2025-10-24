<?php

namespace App\Form;

use App\Entity\TipoMesada;
use App\Entity\Mesada;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MesadaType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('tipoMesada', EntityType::class, array(
                    'label' => 'Mesada',
                    'mapped' => true,
                    'class' => TipoMesada::class,
                    'required' => false,
                    'attr' => array(
                        'placeholder' => '-- Elija la mesada --',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija la mesada --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.numero', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la mesada --',
                    'auto_initialize' => true)
            )
            ->add('cantidadBandejas', NumberType::class, array(
                    'required' => true,
                    'label' => 'Cantidad de Bandejas',
                    'scale' => 1,
                    'html5' => true,
                    'attr' => array(
                        'placeholder' => 'Escriba la cantidad de bandejas (ej: 0,5)',
                        'min' => 0.5,
                        'step' => 0.1,
                        'class' => 'form-control',
                        'inputmode' => 'decimal',
                        'pattern' => '[0-9]+([,][0-9]+)?'
                    )
                )
            )

        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Mesada::class,
        ]);
    }

}
