<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormError;
use App\Form\ChangePasswordType;

#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    #[Route('/profile', name: 'app_user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, TokenStorageInterface $tokenStorage): Response
    {
        $currentUser = $this->getUser();
        $id = $currentUser->getId();

        // Use a separate instance of user for the edit form
        $entityManager->detach($currentUser);
        $user = $userRepository->find($id);

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $entityManager->persist($user);
            $entityManager->flush();

            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            $this->addFlash('success', 'Your account has been updated.');

            return $this->redirectToRoute('app_user_profile');
        }
        return $this->render('user/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/profile/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token')))
        {
            $entityManager->remove($user);
            $entityManager->flush();

            $tokenStorage->setToken(null);
            $request->getSession()->invalidate();

            $this->addFlash('success', 'Your account has been deleted.');
        }
        
        return $this->redirectToRoute('app_coin_index');
    }

    #[Route('/profile/change-password', name: 'app_user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $currentPassword = $form->get('current_password')->getData();
            $newPassword = $form->get('new_password')->getData();
            
            if (!$userPasswordHasher->isPasswordValid($user, $currentPassword)) 
            {
                $form->get('current_password')->addError(new FormError('The current password is incorrect.'));
                return $this->render('user/change_password.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }

            if ($newPassword === $currentPassword) 
            {
                $form->get('current_password')->addError(new FormError('The new password cannot be the same as the current password.'));
                return $this->render('user/change_password.html.twig', [
                    'form' => $form,
                    'user' => $user,
                ]);
            }
            
            $user->setPassword($userPasswordHasher->hashPassword($user, $newPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            $this->addFlash('success', 'Your password has been changed.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
