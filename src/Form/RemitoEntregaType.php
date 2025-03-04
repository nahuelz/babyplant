<?php

namespace App\Form;


use App\Entity\Entrega;
use App\Entity\Usuario;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
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

        $builder
            ->add('entrega', EntityType::class, array(
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
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('x')
                            ->orderBy('x.id', 'ASC');
                    },
                    'label_attr' => array('class' => 'control-label'),
                    'placeholder' => '-- Elija la entrega --',
                    'auto_initialize' => false)
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Entrega::class,
        ]);
    }

}
