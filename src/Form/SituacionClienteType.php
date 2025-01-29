<?php

namespace App\Form;

use App\Entity\ModoPago;
use App\Entity\Movimiento;
use App\Entity\PedidoProducto;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SituacionClienteType extends AbstractType
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
                        'cols' => '5',
                        'rows' => '1',
                        'class' => 'form-control',
                        'tabindex' => '7')
                )
            )
            ->add(
                'pedidoProducto',
                EntityType::class,
                array(
                    'class' => PedidoProducto::class,
                    'required' => false,
                    'label' => 'Pedido Producto',
                    'placeholder' => '-- Elija el pedido producto --',
                    'attr' => array(
                        'class' => 'form-control choice',
                        'data-placeholder' => '-- Elija --',
                        'tabindex' => '5'
                    ),
                    'query_builder' => function (EntityRepository $er) use ($options) {

                        $idCliente = $options['idCliente'];

                        $queryBuilder = $er->createQueryBuilder('pp');

                        $queryBuilder
                            ->leftJoin('App:Pedido', 'p', Join::WITH, 'pp.pedido = p')
                            ->leftJoin('App:Usuario', 'u', Join::WITH, 'p.cliente = u ')
                            ->where('u.id = :idCliente')
                            ->andWhere('pp.estado = 1 or pp.estado = 2 or pp.estado = 3 or pp.estado = 4 or pp.estado = 5')
                            ->orderBy('p.id', 'DESC')
                            ->setParameter('idCliente', $idCliente);
                        return $queryBuilder;
                    }
                )
            )
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
