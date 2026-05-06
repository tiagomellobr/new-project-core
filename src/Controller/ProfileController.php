<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileChangePasswordFormType;
use App\Form\ProfileFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $profileForm = $this->createForm(ProfileFormType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $newEmail = $profileForm->get('email')->getData();

            if ($newEmail !== $user->getEmail()) {
                $existing = $userRepository->findOneBy(['email' => $newEmail]);
                if ($existing !== null && $existing->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Este e-mail já está em uso por outra conta.');

                    return $this->redirectToRoute('app_profile');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Dados atualizados com sucesso.');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $this->createForm(ProfileChangePasswordFormType::class),
        ]);
    }

    #[Route('/profile/password', name: 'app_profile_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        Security $security,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();

            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'A senha atual está incorreta.');

                return $this->redirectToRoute('app_profile');
            }

            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $em->flush();

            $security->logout(false);
            $this->addFlash('success', 'Senha alterada com sucesso. Faça login novamente.');

            return $this->redirectToRoute('app_login');
        }

        // Re-render profile page with password form errors
        /** @var User $freshUser */
        $freshUser = $this->getUser();
        $profileForm = $this->createForm(ProfileFormType::class, $freshUser);

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $profileForm,
            'passwordForm' => $form,
        ]);
    }
}
