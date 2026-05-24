<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Loads fixtures into the database',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => 'dantre340@gmail.com']);
        
        if ($existingUser) {
            $user = $existingUser;
            $output->writeln('ℹ User already exists: dantre340@gmail.com (LordCitas)');
        } else {
            // Create user
            $user = new User();
            $user->setEmail('dantre340@gmail.com');
            $user->setNickname('LordCitas');
            $user->setRoles(['ROLE_USER']);
            
            // Hash the password
            $hashedPassword = $this->passwordHasher->hashPassword($user, 'Daybarsoto');
            $user->setPassword($hashedPassword);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            $output->writeln('✓ User created: dantre340@gmail.com (LordCitas)');
        }

        // Create first tournament (user is creator) - check if it exists
        $tournament1 = $this->entityManager->getRepository(Tournament::class)->findOneBy(['name' => 'Modern Masters Championship']);
        
        if (!$tournament1) {
            $tournament1 = new Tournament();
            $tournament1->setName('Modern Masters Championship');
            $tournament1->setDescription('Un torneo emocionante de Modern con los mejores magos de la región.');
            $tournament1->setFormat('Modern');
            $tournament1->setState('ongoing');
            $tournament1->setMaxPlayers(8);
            $tournament1->setInviteCode('MMC2026A');
            $tournament1->setCreator($user);
            $tournament1->setCreatedAt(new \DateTimeImmutable());
            $tournament1->setStartDate(new \DateTime('2026-05-24 14:00:00'));
            $tournament1->setCurrentRound(2);

            $this->entityManager->persist($tournament1);
            $this->entityManager->flush();

            $output->writeln('✓ Tournament created: Modern Masters Championship (creator)');
        } else {
            $output->writeln('ℹ Tournament already exists: Modern Masters Championship');
        }

        // Create second tournament (user is participant)
        $tournament2 = $this->entityManager->getRepository(Tournament::class)->findOneBy(['name' => 'Legacy Grand Prix']);
        
        if (!$tournament2) {
            $tournament2 = new Tournament();
            $tournament2->setName('Legacy Grand Prix');
            $tournament2->setDescription('El mayor evento de Legacy del año. Compite contra los mejores jugadores internacionales.');
            $tournament2->setFormat('Legacy');
            $tournament2->setState('planning');
            $tournament2->setMaxPlayers(16);
            $tournament2->setInviteCode('LGP2026X');
            
            // Check if admin user exists
            $creatorUser = $this->userRepository->findOneBy(['email' => 'admin@manahoarder.com']);
            
            if (!$creatorUser) {
                $creatorUser = new User();
                $creatorUser->setEmail('admin@manahoarder.com');
                $creatorUser->setNickname('Admin');
                $creatorUser->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
                $hashedPassword2 = $this->passwordHasher->hashPassword($creatorUser, 'AdminPassword123');
                $creatorUser->setPassword($hashedPassword2);
                
                $this->entityManager->persist($creatorUser);
                $output->writeln('✓ Tournament creator user created: admin@manahoarder.com (Admin)');
            } else {
                $output->writeln('ℹ Tournament creator user already exists: admin@manahoarder.com');
            }
            
            $tournament2->setCreator($creatorUser);
            $tournament2->setCreatedAt(new \DateTimeImmutable());
            $tournament2->setStartDate(new \DateTime('2026-06-15 10:00:00'));

            $this->entityManager->persist($tournament2);
            $this->entityManager->flush();

            $output->writeln('✓ Tournament created: Legacy Grand Prix (participant target)');
        } else {
            $tournament2 = $this->entityManager->getRepository(Tournament::class)->findOneBy(['name' => 'Legacy Grand Prix']);
            $output->writeln('ℹ Tournament already exists: Legacy Grand Prix');
        }

        // Check if user is already a participant
        $existingParticipant = $this->entityManager->getRepository(TournamentParticipant::class)->findOneBy([
            'tournament' => $tournament2,
            'user' => $user,
        ]);
        
        if (!$existingParticipant) {
            // Add user as participant to tournament2
            $participant = new TournamentParticipant();
            $participant->setTournament($tournament2);
            $participant->setUser($user);
            $participant->setJoinedAt(new \DateTimeImmutable());
            $participant->setWins(0);
            $participant->setLosses(0);

            $this->entityManager->persist($participant);
            $this->entityManager->flush();

            $output->writeln('✓ User added as participant to Legacy Grand Prix');
        } else {
            $output->writeln('ℹ User is already a participant in Legacy Grand Prix');
        }

        $output->writeln('');
        $output->writeln('<info>✓ Fixtures processed successfully!</info>');
        $output->writeln('');
        $output->writeln('User credentials:');
        $output->writeln('  Email: dantre340@gmail.com');
        $output->writeln('  Nickname: LordCitas');
        $output->writeln('  Password: Daybarsoto');
        $output->writeln('');
        $output->writeln('Tournament 1 (as creator):');
        $output->writeln('  Name: Modern Masters Championship');
        $output->writeln('  Format: Modern | Max Players: 8 | State: ongoing');
        $output->writeln('');
        $output->writeln('Tournament 2 (as participant):');
        $output->writeln('  Name: Legacy Grand Prix');
        $output->writeln('  Format: Legacy | Max Players: 16 | State: planning');

        return Command::SUCCESS;
    }
}
