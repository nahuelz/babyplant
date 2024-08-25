<?php

namespace App\Form;

use App\Entity\Grupo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class GrupoType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('nombre', TextType::class, array(
                    'required' => true,
                    'label' => 'Nombre',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('descripcion', TextType::class, array(
                    'required' => false,
                    'label' => 'Descripción',
                    'attr' => array(
                        'class' => 'form-control',
                        'tabindex' => '5'))
            )
            ->add('roles', ChoiceType::class, array(
                    'choices' => $this->flatArray($options['roles']),
                    'choice_value' => function ($key) {
                        if (is_array($key)) {
                            return false;
                        }
                        return $key;
                    },
                    'choice_label' => function ($key, $value) {
                        return $value;
                    },
                    'required' => true,
                    'multiple' => true,
                    'label_attr' => array('class' => 'control-label'),
                    'attr' => array(
                        'class' => 'form-control choice',
                        'placeholder' => 'Seleccione los roles aqu&iacute;.'))
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Grupo::class,
            'roles' => null
        ]);
    }

    /**
     * Retorna un array filtrado, el cual contiene todos los roles definidos
     *
     * @param array $data
     * @return array
     */
    private function flatArray(array $data) {

        $result = array();

        foreach ($data as $key => $value) {
            if (substr($key, 0, 4) === 'ROLE') {
                $result[$key] = $key;
            }
            if (is_array($value)) {
                $tmpresult = $this->flatArray($value);
                if (count($tmpresult) > 0) {
                    $result = array_merge($result, $tmpresult);
                }
            } else {
                $result[$value] = $value;
            }
        }
        return array_unique($result);
    }

}
