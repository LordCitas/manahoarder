<?php

namespace App\Controller;

use App\Entity\Decklist;
use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use App\Repository\DecklistRepository;
use App\Repository\TournamentRepository;
use App\Repository\TournamentParticipantRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        
        // Create participant record
        $participant = new TournamentParticipant();
        $participant->setTournament($tournament);
        $participant->setUser($user);
        $participant->setJoinedAt(new DateTimeImmutable());
        $participant->setWins(0);
        $participant->setLosses(0);
        
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
        
        // Get user's decklists for submission form
        $userDecklists = $this->decklistRepository->findBy(['user' => $user]);
        
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
}
