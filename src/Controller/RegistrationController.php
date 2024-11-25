<?php

namespace App\Controller;

use App\Entity\Cuota;
use App\Entity\RazonSocial;
use App\Entity\Usuario;
use App\Entity\Constants\ConstanteTipoUsuario;
use App\Form\RazonSocialType;
use App\Form\RegistrationFormType;
use App\Repository\UsuarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class RegistrationController extends AbstractController {


    /**
     *
     */
    public function __construct() {
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, $isAjaxCall = false): Response {

        $user = new Usuario();

        $form = $this->createForm(RegistrationFormType::class, $user, array(
            'action' => $this->generateUrl('app_register'),
            'method' => 'POST'
        ));

        $form->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );

        $razonSocial = new RazonSocial();

        $formRazonSocial = $this->createForm(RazonSocialType::class, $razonSocial, array(
            'action' => $this->generateUrl('app_razonsocial_create_ajax'),
            'method' => 'POST'
        ));

        $formRazonSocial->add('submit', SubmitType::class, array(
                'label' => 'Agregar',
                'attr' => array('class' => 'btn btn-light-primary font-weight-bold submit-button'))
        );


        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $entityManager = $this->getDoctrine()->getManager();
            $existeUsuario = $this->validarExisteUsuario($user, $entityManager);
            if ($existeUsuario) {
                $this->get('session')->getFlashBag()->add('error', $existeUsuario);
                if (!$isAjaxCall) {
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ]);
                }else {
                    return $this->redirectToRoute('pedido_new');
                }
            }
            // encode the plain password
            $user->setHabilitado(true);
            if ($user->getTipoUsuario()->getCodigoInterno() == ConstanteTipoUsuario::CLIENTE) {
                $user->setHabilitado(false);
                $user->setUsername($user->getEmail());
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $user->getEmail()
                    )
                );
            } else {
                $user->setPassword(
                    $passwordEncoder->encodePassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
            }

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email
            if ($this->getUser()) {
                $this->get('session')->getFlashBag()->add('success', 'Usuario registrado con exito.');
                if(!$isAjaxCall) {
                    return $this->redirectToRoute('usuario_index');
                }else{
                    return $this->redirectToRoute('pedido_new');
                }
            }
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'razonSocialForm' => $formRazonSocial->createView()
        ]);
    }

    private function validarExisteUsuario($user,$entityManager){
        $entityManager = $this->getDoctrine()->getManager();
        $msg = false;
        if ($user->getTipoUsuario()->getCodigoInterno() == ConstanteTipoUsuario::CLIENTE) {
            $existeCuit = $entityManager->getRepository(Usuario::class)->findBy(array('cuit' => $user->getCuit()));
            if ($existeCuit) {
                $msg = 'Ya existe un usuario registrado con este cuit.';
            }
        }
        $existeMail = $entityManager->getRepository(Usuario::class)->findBy(array('email' => $user->getEmail()));
        if ($existeMail) {
            $msg = 'Ya existe un usuario registrado con este mail.';
        }
        return ($msg);
    }


    /**
     * @Route("/registerAjax", name="app_register_ajax")
     */
    public function registerAjax(Request $request, UserPasswordEncoderInterface $passwordEncoder, $isAjaxCall = false): Response {
        return $this->register($request, $passwordEncoder, true);
    }
}
