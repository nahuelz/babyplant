<?php

namespace App\Form;

use App\Entity\Constants\ConstanteEstadoDevolucion;
use App\Entity\Devolucion;
use App\Entity\Reventa;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReventaType extends AbstractType {

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('devolucion', EntityType::class, array(
                'class' => Devolucion::class,
                'required' => true,
                'label' => 'Devolución a revender',
                'placeholder' => '-- Elija la devolución --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija la devolución --',
                    'tabindex' => '5'
                ),
                'choices' => $this->getDevolucionesDisponibles(),
                'choice_label' => function (Devolucion $devolucion) {
                    return $devolucion . ' - ' . $devolucion->getEntregaProducto()->getPedidoProducto()->getNombreCompleto()
                        . ' - CLIENTE: ' . $devolucion->getCliente()
                        . ' - DISPONIBLES: ' . $devolucion->getCantidadDisponible();
                },
                'choice_attr' => function (Devolucion $devolucion) {
                    return [
                        'data-disponible' => $devolucion->getCantidadDisponible(),
                        'data-precio' => $devolucion->getPrecioUnitario(),
                        'data-cliente' => (string) $devolucion->getCliente(),
                        'data-producto' => $devolucion->getEntregaProducto()->getPedidoProducto()->getNombreCompleto(),
                        'data-pedido' => $devolucion->getPedido()->getId(),
                    ];
                },
                'label_attr' => array('class' => 'control-label')
            ))
            ->add('cliente', EntityType::class, array(
                'class' => Usuario::class,
                'required' => true,
                'label' => 'Cliente comprador',
                'placeholder' => '-- Elija el cliente --',
                'attr' => array(
                    'class' => 'form-control choice',
                    'data-placeholder' => '-- Elija el cliente --',
                    'tabindex' => '5'
                ),
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('x')
                        ->where('x.tipoUsuario = 1')
                        ->andWhere('x.habilitado = 1')
                        ->orderBy('x.apellido', 'ASC');
                },
            ))
            ->add('cantidadBandejas', NumberType::class, array(
                'required' => true,
                'label' => 'Cantidad de Bandejas a Revender',
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
            ))
            ->add('precioUnitario', NumberType::class, array(
                'required' => false,
                'label' => 'Precio Unitario',
                'scale' => 2,
                'html5' => true,
                'attr' => array(
                    'placeholder' => 'Escriba el precio por bandeja (ej: 150,00)',
                    'min' => 0,
                    'step' => 0.01,
                    'class' => 'form-control',
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]+([,][0-9]+)?'
                )
            ))
            ->add('fechaReventa', DateType::class, array(
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy',
                'model_timezone' => 'America/Argentina/Buenos_Aires',
                'required' => true,
                'label' => 'Fecha de Reventa',
                'data' => new \DateTime(),
                'attr' => array(
                    'placeholder' => 'Fecha de Reventa',
                    'class' => 'form-control datepicker',
                    'tabindex' => '5'
                )
            ))
            ->add('observacion', TextareaType::class, array(
                'required' => false,
                'label' => 'Observación',
                'attr' => array(
                    'placeholder' => 'Escriba una observación',
                    'class' => 'form-control',
                    'rows' => 3,
                    'tabindex' => '5'
                )
            ))
        ;
    }

    /**
     * Devoluciones no descartadas con bandejas disponibles para revender.
     */
    private function getDevolucionesDisponibles(): array
    {
        $devoluciones = $this->entityManager->getRepository(Devolucion::class)
            ->createQueryBuilder('d')
            ->leftJoin('d.estado', 'e')
            ->where('e.codigoInterno != :descartada')
            ->setParameter('descartada', ConstanteEstadoDevolucion::DESCARTADA)
            ->orderBy('d.id', 'DESC')
            ->getQuery()
            ->getResult();

        return array_filter($devoluciones, function (Devolucion $devolucion) {
            return $devolucion->getCantidadDisponible() > 0;
        });
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Reventa::class,
        ]);
    }
}
