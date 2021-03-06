<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Form\UserType;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("{_locale}/register", name="user_registration")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        //-- Build user create form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        //-- Handle submit request
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            //-- Encoding password
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            //-- Save new user in Db
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            //-- Autologin currently created user
            $token = new UsernamePasswordToken(
                $user,
                $user->getPassword(),
                'main',
                $user->getRoles()
            );
            $this->get('security.token_storage')->setToken($token);


            $user=$this->get('security.token_storage')->getToken()->getUser();
            //Create Profile for newly registered user
            $profile = new Profile;
            $now=new\DateTime('now');
            $profile->setUser($user);
            $profile->setUpdatedAt($now);
            $profile->setCreateDate($now);

            $profile->setActive('Y');
            $entityManager->persist($profile);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render(
            'registration/register.html.twig',
            array('form' => $form->createView())
        );
    }
}