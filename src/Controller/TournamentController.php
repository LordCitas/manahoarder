<?php

namespace App\Controller;

use App\Entity\Decklist;
use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use App\Repository\DecklistRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentParticipantRepository;
use App\Service\ScryfallService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class TournamentController extends AbstractController
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private TournamentParticipantRepository $participantRepository,
        private DecklistRepository $decklistRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/tournaments', name: 'app_tournaments')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Get tournaments created by this user
        $myTournaments = $this->tournamentRepository->findBy(['creator' => $user]);
        
        // Get tournaments where user is a participant
        $participants = $this->participantRepository->findBy(['user' => $user]);
        $joinedTournaments = array_map(fn($p) => $p->getTournament(), $participants);
        
        return $this->render('tournament/index.html.twig', [
            'myTournaments' => $myTournaments,
            'joinedTournaments' => $joinedTournaments,
        ]);
    }

    #[Route('/tournaments/create', name: 'app_tournament_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            // Generate random 8-character code
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
            
            $tournament = new Tournament();
            $tournament->setName($request->request->get('name'));
            $tournament->setDescription($request->request->get('description'));
            $tournament->setFormat($request->request->get('format'));
            $tournament->setMaxPlayers((int) $request->request->get('maxPlayers', 16));
            $tournament->setInviteCode($code);
            $tournament->setState('draft');
            $tournament->setCreator($this->getUser());
            $tournament->setCreatedAt(new DateTimeImmutable());
            
            $this->entityManager->persist($tournament);
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf('Tournament created! Share code: <strong>%s</strong>', $code));
            return $this->redirectToRoute('app_tournaments');
        }
        
        return $this->render('tournament/create.html.twig');
    }

    #[Route('/tournaments/join-by-code', name: 'app_tournament_join_by_code', methods: ['POST'])]
    public function joinByCode(Request $request): Response
    {
        $code = trim($request->request->get('code'));
        $decklistId = (int) $request->request->get('decklist_id');
        $user = $this->getUser();
        
        $tournament = $this->tournamentRepository->findOneBy(['inviteCode' => $code]);
        if (!$tournament) {
            $this->addFlash('error', 'Tournament not found with that code.');
            return $this->redirectToRoute('app_tournaments');
        }
        
        // Check if user is already a participant
        $existingParticipant = $this->participantRepository->findOneBy([
            'tournament' => $tournament,
            'user' => $user,
        ]);
        if ($existingParticipant) {
            $this->addFlash('warning', 'You are already in this tournament.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
        }
        
        // Verify decklist belongs to user and matches format
        $decklist = null;
        if ($decklistId > 0) {
            $decklist = $this->decklistRepository->find($decklistId);
            if (!$decklist || $decklist->getUser()->getId() !== $user->getId()) {
                $this->addFlash('error', 'Invalid decklist selection.');
                return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
            }
            
            // Verify decklist format matches tournament format
            if ($decklist->getFormat() !== $tournament->getFormat()) {
                $this->addFlash('error', sprintf(
                    'Decklist format (%s) does not match tournament format (%s).',
                    $decklist->getFormat(),
                    $tournament->getFormat()
                ));
                return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
            }
        }
        
        // Create participant record
        $participant = new TournamentParticipant();
        $participant->setTournament($tournament);
        $participant->setUser($user);
        $participant->setJoinedAt(new DateTimeImmutable());
        $participant->setWins(0);
        $participant->setLosses(0);
        if ($decklist) {
            $participant->setDecklist($decklist);
        }
        
        $this->entityManager->persist($participant);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Successfully joined tournament!');
        return $this->redirectToRoute('app_tournament_view', ['id' => $tournament->getId()]);
    }

    #[Route('/tournaments/{id}', name: 'app_tournament_view')]
    public function view(int $id): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $user = $this->getUser();
        $isCreator = $tournament->getCreator()->getId() === $user->getId();
        $userParticipant = $this->participantRepository->findOneBy([
            'tournament' => $tournament,
            'user' => $user,
        ]);
        
        // Get standings
        $participants = $this->participantRepository->findBy(['tournament' => $tournament]);
        usort($participants, fn($a, $b) => $b->getWins() - $a->getWins());
        
        // Get user's decklists matching tournament format for join form
        $userDecklists = $this->decklistRepository->findBy(['user' => $user, 'format' => $tournament->getFormat()]);
        
        return $this->render('tournament/view.html.twig', [
            'tournament' => $tournament,
            'isCreator' => $isCreator,
            'userParticipant' => $userParticipant,
            'participants' => $participants,
            'userDecklists' => $userDecklists,
        ]);
    }

    #[Route('/tournaments/{id}/submit-decklist', name: 'app_tournament_submit_decklist', methods: ['POST'])]
    public function submitDecklist(int $id, Request $request): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $user = $this->getUser();
        $decklistId = (int) $request->request->get('decklist_id');
        
        // Verify decklist belongs to user
        $decklist = $this->decklistRepository->find($decklistId);
        if (!$decklist || $decklist->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Invalid decklist selection.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        // Verify decklist format matches tournament format
        if ($decklist->getFormat() !== $tournament->getFormat()) {
            $this->addFlash('error', sprintf(
                'Decklist format (%s) does not match tournament format (%s).',
                $decklist->getFormat(),
                $tournament->getFormat()
            ));
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        // Find or create participant
        $participant = $this->participantRepository->findOneBy([
            'tournament' => $tournament,
            'user' => $user,
        ]);
        
        if (!$participant) {
            $participant = new TournamentParticipant();
            $participant->setTournament($tournament);
            $participant->setUser($user);
            $participant->setJoinedAt(new DateTimeImmutable());
            $participant->setWins(0);
            $participant->setLosses(0);
        }
        
        $participant->setDecklist($decklist);
        $this->entityManager->persist($participant);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Decklist submitted successfully!');
        return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
    }

    #[Route('/tournaments/{id}/standings', name: 'app_tournament_standings')]
    public function standings(int $id): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $participants = $this->participantRepository->findBy(['tournament' => $tournament]);
        usort($participants, fn($a, $b) => $b->getWins() - $a->getWins());
        
        return $this->render('tournament/standings.html.twig', [
            'tournament' => $tournament,
            'participants' => $participants,
        ]);
    }

    #[Route('/tournaments/{id}/start', name: 'app_tournament_start', methods: ['POST'])]
    public function start(int $id): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $user = $this->getUser();
        if ($tournament->getCreator()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Only the tournament creator can start it.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        $tournament->setState('in_progress');
        $tournament->setStartDate(new \DateTime());
        $tournament->setCurrentRound(1);
        
        $this->entityManager->persist($tournament);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Tournament started!');
        return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
    }

    #[Route('/tournaments/{id}/remove-participant/{participantId}', name: 'app_tournament_remove_participant', methods: ['POST'])]
    public function removeParticipant(int $id, int $participantId): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $user = $this->getUser();
        if ($tournament->getCreator()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Only the tournament creator can remove participants.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        $participant = $this->participantRepository->find($participantId);
        if (!$participant || $participant->getTournament()->getId() !== $id) {
            $this->addFlash('error', 'Participant not found in this tournament.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        $participantName = $participant->getUser()->getNickname();
        $this->entityManager->remove($participant);
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf('Removed %s from tournament.', $participantName));
        return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
    }

    #[Route('/tournaments/{id}/close', name: 'app_tournament_close', methods: ['POST'])]
    public function closeTournament(int $id): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament) {
            throw $this->createNotFoundException('Tournament not found');
        }
        
        $user = $this->getUser();
        if ($tournament->getCreator()->getId() !== $user->getId()) {
            $this->addFlash('error', 'Only the tournament creator can close it.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        if ($tournament->getState() === 'completed') {
            $this->addFlash('warning', 'Tournament is already closed.');
            return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
        }
        
        $tournament->setState('completed');
        $this->entityManager->persist($tournament);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Tournament closed.');
        return $this->redirectToRoute('app_tournament_view', ['id' => $id]);
    }

    #[Route('/tournament/{id}/search-art', name: 'app_tournament_search_art', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function searchArt(int $id, Request $request, ScryfallService $scryfallService): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament || $tournament->getCreator()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([
                'data' => [],
                'message' => 'Search query must be at least 2 characters',
            ]);
        }

        $results = $scryfallService->searchCards($query);
        
        // Filter to only cards with art crop URLs
        $filteredCards = [];
        foreach ($results['data'] as $card) {
            if (isset($card['image_uris']['art_crop'])) {
                $filteredCards[] = [
                    'id' => $card['id'] ?? '',
                    'name' => $card['name'] ?? '',
                    'artCropUrl' => $card['image_uris']['art_crop'],
                ];
            }
        }
        
        return $this->json([
            'data' => $filteredCards,
            'total' => count($filteredCards),
        ]);
    }

    #[Route('/tournament/{id}/update-art', name: 'app_tournament_update_art', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function updateArt(int $id, Request $request): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament || $tournament->getCreator()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);
        $artUrl = $data['artUrl'] ?? null;
        $cardName = $data['cardName'] ?? '';

        if (!$artUrl || !filter_var($artUrl, FILTER_VALIDATE_URL)) {
            return $this->json(['success' => false, 'message' => 'Invalid art URL'], Response::HTTP_BAD_REQUEST);
        }

        $tournament->setArtUrl($artUrl);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tournament art updated',
            'artUrl' => $artUrl,
            'cardName' => $cardName,
        ]);
    }

    #[Route('/tournament/{id}/clear-art', name: 'app_tournament_clear_art', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function clearArt(int $id): JsonResponse
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament || $tournament->getCreator()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'Not authorized'], Response::HTTP_FORBIDDEN);
        }

        $tournament->setArtUrl(null);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Tournament banner removed',
        ]);
    }

    #[Route('/tournament/{id}/delete', name: 'app_tournament_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): Response
    {
        $tournament = $this->tournamentRepository->find($id);
        if (!$tournament || $tournament->getCreator()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Not authorized to delete this tournament');
            return $this->redirectToRoute('app_tournaments');
        }

        $this->entityManager->remove($tournament);
        $this->entityManager->flush();

        $this->addFlash('success', 'Tournament deleted successfully');
        return $this->redirectToRoute('app_tournaments');
    }
}
