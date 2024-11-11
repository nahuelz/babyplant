<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;

/**
 * @Route("/change-password")
 * @Security("is_granted('ROLE_USER') or is_granted('ROLE_INTERESADO_ET')")
 */
class ChangePasswordController extends AbstractController {

    /**
     * @Route("/", name="change_password")
     */
    public function changePasswordAction(Request $request, UserPasswordEncoderInterface $passwordEncoder) {

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);
        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {

            $currentPassword = $form->get('currentPassword')->getData();

            if ($passwordEncoder->isPasswordValid($user, $currentPassword)) {
                // Encode the plain password, and set it.
                $encodedPassword = $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                );

                $user->setPassword($encodedPassword);
                $this->doctrine->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', "Se modificó correctamente su contraseña");
                return $this->redirectToRoute('app_login');
            } else {
                $form->addError(new FormError('La contraseña ingresada es incorrecta'));
            }
        }

        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Cambiar contraseña'
        ]);
    }

}
