<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserConnexionType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(UserRepository $userRepository, SessionInterface $session): Response
    {
            return $this->render('user/index.html.twig', [
                'users' => $userRepository->findAll(),
            ]);

    }

    /**
     * @Route("/deconnexion", name="user_deconnexion", methods={"GET"})
     */
    public function deco(SessionInterface $session): Response
    {
        $session->remove("login");
        return $this->redirectToRoute('user_connexion', [], Response::HTTP_SEE_OTHER);

    }


    /**
     * @Route("/register", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connexion/register.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/connexion", name="user_connexion", methods={"GET","POST"})
     */
    public function connexion(Request $request, SessionInterface $session): Response
    {
        if($session->has("login")) {
            return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
        }
        $user = new User();
        $form = $this->createForm(UserConnexionType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $find_user = $entityManager->getRepository(User::class)->findOneBy(array('Nickname' => $user->getNickname()));
            if(!isset($find_user)) {
                $error = new FormError("Utilisateur non trouvé.");
                $form->addError($error);
                return $this->renderForm('connexion/connexion.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            if($find_user->getPassword() !== $user->getPassword()) {
                $error = new FormError("Mot de passe incorrect.");
                $form->addError($error);
                return $this->renderForm('connexion/connexion.html.twig', [
                    'user' => $user,
                    'form' => $form,
                ]);
            }

            $session->set("login", $find_user->getId());
            return $this->redirectToRoute('index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('connexion/connexion.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{Nickname}", name="user_show", methods={"GET"})
     */
    public function show(User $user, SessionInterface $session): Response
    {
        if($session->has("login")) {
            if($session->get('login') === $user->getId()){
                $current_user = true;
            } else {
                $current_user = false;
            }

            return $this->render('user/show.html.twig', [
                'user' => $user, 'posts' => $user->getPosts(), 'current' => $current_user
            ]);
        } else {

            return $this->redirectToRoute('user_connexion', [], Response::HTTP_SEE_OTHER);
        }

    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_show', [ "Nickname" => $user->getNickname() ], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"POST"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index', [], Response::HTTP_SEE_OTHER);
    }
}
