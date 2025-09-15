<?php

namespace App\Form;

use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\Pedido;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class MovimientoType extends AbstractType
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
                        'class' => 'form-control',
                        'placeholder' => 'Monto',
                        'tabindex' => '6'
                    )
                )
            )
            ->add('descripcion', TextareaType::class, array(
                    'required' => false,
                    'label' => 'Descripcion',
                    'attr' => array(
                        'cols' => '10',
                        'rows' => '5',
                        'class' => 'form-control',
                        'tabindex' => '7')
                )
            )
            ->add(
                'pedido',
                EntityType::class,
                array(
                    'class' => Pedido::class,
                    'required' => false,
                    'label' => 'Pedido',
                    'placeholder' => '-- Elija el pedido --',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) use ($options) {

                        $idCliente = $options['idCliente'];

                        $queryBuilder = $er->createQueryBuilder('p');

                        $queryBuilder
                            ->leftJoin('App:PedidoProducto', 'pp', Join::WITH, 'pp.pedido = p')
                            ->leftJoin('App:Usuario', 'u', Join::WITH, 'p.cliente = u ')
                            ->where('u.id = :idCliente')
                            ->andWhere('pp.estado IN (1,2,3,4,5,6,7,8,9)')
                            ->andWhere('pp.fechaBaja IS NULL')
                            ->orderBy('p.id', 'DESC')
                            ->setParameter('idCliente', $idCliente)
                            ->setMaxResults(15); // 👈 limitar a 10 resultados;
                        return $queryBuilder;
                    }
                )
            )
            ->add('submit', SubmitType::class, [
                'label' => 'Guardar movimiento',
                'attr' => [
                    'class' => 'btn btn-light-primary font-weight-bold submit-button',
                    'tabindex' => '8',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Movimiento::class,
            'idCliente' => null,
        ]);
    }
}
