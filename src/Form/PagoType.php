<?php

namespace App\Form;

use App\Entity\ModoPago;
use App\Entity\Pago;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PagoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'modoPago',
                EntityType::class,
                array(
                    'class' => ModoPago::class,
                    'required' => true,
                    'label' => 'Modo de pago',
                    'placeholder' => '-- Elija el modo de pago --',
                    'attr' => array(
                        'placeholder' => 'Escriba el modo de pago aquí.',
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->where('x.habilitado = 1')
                            ->orderBy('x.nombre', 'ASC');
                    },
                )
            )
            ->add('monto', NumberType::class, array(
                    'required' => true,
                    'label' => 'Monto',
                    'attr' => array(
                        'min' => 10,
                        'class' => 'form-control',
                        'placeholder' => 'Monto',
                        'step' => '0.01',
                        'tabindex' => '6'
                    )
                )
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pago::class,
        ]);
    }
}
