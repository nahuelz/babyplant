<?php

namespace App\Form;

use App\Entity\Notificacion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificacionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('titulo', TextType::class, [
                'label' => 'Título',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('contenido', TextareaType::class, [
                'label' => 'Contenido',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ])
            ->add('fechaDesde', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => true,
                    'label' => 'Válida desde',
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'tabindex' => '5'
                    ))
            )
            ->add('fechaHasta', DateType::class, array(
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd/MM/yyyy',
                    'model_timezone' => 'America/Argentina/Buenos_Aires',
                    'required' => false,
                    'label' => 'Válida hasta',
                    'attr' => array(
                        'class' => 'form-control datepicker',
                        'tabindex' => '5'
                    ))
            )
            ->add('destinatarios', ChoiceType::class, array(
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
            );
    }

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Notificacion::class,
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