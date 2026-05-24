# ManaHoarder Fixtures Documentation

## Overview
This directory contains fixture files for populating the ManaHoarder database with test data.

## Current Fixtures

### 1. LoadFixturesCommand (Recommended)
**Location:** `src/Command/LoadFixturesCommand.php`

A Symfony Console command that loads fixtures into the database. This command is idempotent - it checks for existing data before creating new entries.

**Usage:**
```bash
php bin/console app:load-fixtures
```

**Features:**
- Idempotent: Safe to run multiple times
- Checks for duplicates before inserting
- Provides clear feedback on what was created/updated
- Handles all dependencies automatically

### 2. AppFixtures (Standard Doctrine Fixtures)
**Location:** `src/DataFixtures/AppFixtures.php`

Standard Doctrine Fixtures class (requires `doctrine/fixtures-bundle` package).

**Usage (if bundle is installed):**
```bash
php bin/console doctrine:fixtures:load
```

## Loaded Data

### User: LordCitas
- **Email:** dantre340@gmail.com
- **Nickname:** LordCitas
- **Password:** Daybarsoto
- **Roles:** ROLE_USER
- **Purpose:** Main test user

### User: Admin
- **Email:** admin@manahoarder.com
- **Nickname:** Admin
- **Password:** AdminPassword123
- **Roles:** ROLE_USER, ROLE_ADMIN
- **Purpose:** Administrator user for tournament creation

### Tournament 1: Modern Masters Championship
- **Creator:** LordCitas
- **Format:** Modern
- **State:** ongoing
- **Max Players:** 8
- **Invite Code:** MMC2026A
- **Start Date:** 2026-05-24 14:00:00
- **Current Round:** 2
- **Description:** Un torneo emocionante de Modern con los mejores magos de la región.

### Tournament 2: Legacy Grand Prix
- **Creator:** Admin
- **Format:** Legacy
- **State:** planning
- **Max Players:** 16
- **Invite Code:** LGP2026X
- **Start Date:** 2026-06-15 10:00:00
- **Description:** El mayor evento de Legacy del año. Compite contra los mejores jugadores internacionales.

### Tournament Participation
- **LordCitas participates in:** Legacy Grand Prix
  - Wins: 0
  - Losses: 0
  - Joined At: 2026-05-24

## Notes

1. **Idempotency:** The LoadFixturesCommand will not create duplicate entries. It's safe to run multiple times.
2. **Password Hashing:** All passwords are automatically hashed using Symfony's PasswordHasher.
3. **References:** The AppFixtures class uses Doctrine references for better maintainability.
4. **Database Integrity:** All fixtures respect foreign key constraints and relationships.

## Testing the Fixtures

After loading fixtures, you can verify the data:

### Using Symfony Console
```bash
# Check users
php bin/console doctrine:query:sql "SELECT id, email, nickname FROM users"

# Check tournaments
php bin/console doctrine:query:sql "SELECT id, name, format, state FROM tournament"

# Check participants
php bin/console doctrine:query:sql "SELECT id, tournament_id, user_id FROM tournament_participant"
```

### Using SQLite CLI
```bash
sqlite3 var/data.db
sqlite> SELECT * FROM users WHERE email = 'dantre340@gmail.com';
sqlite> SELECT * FROM tournament WHERE name LIKE '%Masters%';
```

## Troubleshooting

### Issue: "duplicate key value violates unique constraint"
**Solution:** The data already exists in the database. Use the LoadFixturesCommand which checks for duplicates automatically.

### Issue: "SQLSTATE[HY000]: General error: 1 no such table"
**Solution:** Run database migrations first:
```bash
php bin/console doctrine:migrations:migrate
```

### Issue: Password not hashing correctly
**Solution:** Ensure the UserPasswordHasherInterface is properly injected. The LoadFixturesCommand handles this automatically.

## Adding More Fixtures

To add more fixtures, either:

1. **Add to LoadFixturesCommand:** For simple command-line based fixtures
2. **Add to AppFixtures:** For standard Doctrine Fixtures (requires bundle installation)
3. **Create a new Command:** For specialized data loading scenarios

Example:
```php
// In LoadFixturesCommand or new command
$newTournament = new Tournament();
$newTournament->setName('Pioneer Pro Tour');
$newTournament->setFormat('Pioneer');
$newTournament->setState('planning');
// ... set other properties
$this->entityManager->persist($newTournament);
$this->entityManager->flush();
```
