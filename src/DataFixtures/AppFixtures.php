<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Tournament;
use App\Entity\TournamentParticipant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create LordCitas user
        $lordCitas = new User();
        $lordCitas->setEmail('dantre340@gmail.com');
        $lordCitas->setNickname('LordCitas');
        $lordCitas->setRoles(['ROLE_USER']);
        $lordCitas->setPassword($this->passwordHasher->hashPassword($lordCitas, 'Daybarsoto'));
        $manager->persist($lordCitas);
        $this->setReference('user-lordcitas', $lordCitas);

        // Create Admin user
        $admin = new User();
        $admin->setEmail('admin@manahoarder.com');
        $admin->setNickname('Admin');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'AdminPassword123'));
        $manager->persist($admin);
        $this->setReference('user-admin', $admin);

        // Create Modern Masters Championship Tournament (LordCitas as creator)
        $modernMasters = new Tournament();
        $modernMasters->setName('Modern Masters Championship');
        $modernMasters->setDescription('Un torneo emocionante de Modern con los mejores magos de la región.');
        $modernMasters->setFormat('Modern');
        $modernMasters->setState('ongoing');
        $modernMasters->setMaxPlayers(8);
        $modernMasters->setInviteCode('MMC2026A');
        $modernMasters->setCreator($lordCitas);
        $modernMasters->setCreatedAt(new \DateTimeImmutable('2026-05-24 10:00:00'));
        $modernMasters->setStartDate(new \DateTime('2026-05-24 14:00:00'));
        $modernMasters->setCurrentRound(2);
        $manager->persist($modernMasters);
        $this->setReference('tournament-modern', $modernMasters);

        // Create Legacy Grand Prix Tournament (Admin as creator, LordCitas as participant)
        $legacyGrandPrix = new Tournament();
        $legacyGrandPrix->setName('Legacy Grand Prix');
        $legacyGrandPrix->setDescription('El mayor evento de Legacy del año. Compite contra los mejores jugadores internacionales.');
        $legacyGrandPrix->setFormat('Legacy');
        $legacyGrandPrix->setState('planning');
        $legacyGrandPrix->setMaxPlayers(16);
        $legacyGrandPrix->setInviteCode('LGP2026X');
        $legacyGrandPrix->setCreator($admin);
        $legacyGrandPrix->setCreatedAt(new \DateTimeImmutable('2026-05-24 10:00:00'));
        $legacyGrandPrix->setStartDate(new \DateTime('2026-06-15 10:00:00'));
        $manager->persist($legacyGrandPrix);
        $this->setReference('tournament-legacy', $legacyGrandPrix);

        $manager->flush();

        // Add LordCitas as participant to Legacy Grand Prix
        $participant = new TournamentParticipant();
        $participant->setTournament($legacyGrandPrix);
        $participant->setUser($lordCitas);
        $participant->setJoinedAt(new \DateTimeImmutable());
        $participant->setWins(0);
        $participant->setLosses(0);
        $manager->persist($participant);

        $manager->flush();
    }
}
