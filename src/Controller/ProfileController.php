<?php

namespace App\Controller;

use App\Repository\ScryfallCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        $userCards = $user->getUserCards();
        
        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'userCards' => $userCards,
        ]);
    }

    #[Route('/profile/update-nickname', name: 'app_profile_update_nickname', methods: ['POST'])]
    public function updateNickname(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $nickname = $request->request->get('nickname');
        
        if (empty($nickname) || strlen($nickname) < 3) {
            $this->addFlash('error', 'Nickname must be at least 3 characters long');
            return $this->redirectToRoute('app_profile');
        }
        
        $user->setNickname($nickname);
        $entityManager->flush();
        
        $this->addFlash('success', 'Nickname updated successfully');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/update-password', name: 'app_profile_update_password', methods: ['POST'])]
    public function updatePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');
        
        // Verify current password
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_profile');
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'New password must be at least 6 characters long');
            return $this->redirectToRoute('app_profile');
        }
        
        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'New passwords do not match');
            return $this->redirectToRoute('app_profile');
        }
        
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->flush();
        
        $this->addFlash('success', 'Password updated successfully');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/set-card-art/{cardId}', name: 'app_profile_set_card_art', methods: ['POST'])]
    public function setCardArt(int $cardId, ScryfallCardRepository $cardRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        $card = $cardRepository->find($cardId);
        
        if (!$card || !$card->getArtCropUrl()) {
            return $this->json(['success' => false, 'message' => 'Card not found or has no art crop'], Response::HTTP_NOT_FOUND);
        }
        
        $user->setProfileArtUrl($card->getArtCropUrl());
        $user->setProfilePictureFilename(null);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Profile picture updated',
            'artUrl' => $card->getArtCropUrl(),
            'cardName' => $card->getName(),
        ]);
    }
}
