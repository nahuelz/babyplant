<?php

namespace App\Form;


use App\Entity\Entrega;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RemitoEntregaType extends AbstractType {

    private $entityManager;

    /**
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        // El select de entregas se llena dinámicamente por AJAX según el cliente.
        // No cargamos todas las Entregas (cada Entrega hidratada dispara la query
        // de la OneToOne inversa 'reserva', generando miles de queries).
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addEntregaField($event->getForm(), $event->getData() instanceof Entrega ? $event->getData() : null);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $id = $data['entrega'] ?? null;
            if (is_array($id)) {
                // El JS envía remito[entregas][N][entrega][entregasProductos][...],
                // por lo que 'entrega' llega como array y no como id escalar.
                $id = null;
            }
            $entrega = $id
                ? $this->entityManager->getRepository(Entrega::class)->find($id)
                : null;
            $this->addEntregaField($event->getForm(), $entrega);
        });
    }

    /**
     * Agrega el campo entrega limitando las choices a la opción seleccionada
     * (o ninguna en el alta). El resto de opciones las provee el JS por AJAX.
     */
    private function addEntregaField(FormInterface $form, ?Entrega $entrega): void
    {
        $form->add('entrega', EntityType::class, array(
            'label' => 'ENTREGAS DEL CLIENTE SIN REMITO',
            'class' => Entrega::class,
            'required' => false,
            'mapped' => false,
            'attr' => array(
                'placeholder' => '-- Elija la entrega --',
                'class' => 'form-control choice',
                'data-placeholder' => '-- Elija la entrega --',
                'tabindex' => '5'
            ),
            'choices' => $entrega ? [$entrega] : [],
            'label_attr' => array('class' => 'control-label'),
            'placeholder' => '-- Elija la entrega --',
            'auto_initialize' => false
        ));
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Entrega::class,
        ]);
    }

}
