# Cotisations membres — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter un système de cotisations par saison (3 types, statut paiement, historique, renouvellement), export Excel, import CSV mis à jour, et restriction des tournois aux membres cotisants.

**Architecture:** Nouvelle entité `MemberSubscription` dans le contexte `Member` (DDD), référençant le membre par UUID string (pas de FK Doctrine). `MatchMemberHandler` lève des `DomainException` au lieu de retourner `false`. Le contrôleur admin existant est étendu. L'import CSV est refondu pour accepter des en-têtes français et gérer les créations+mises à jour.

**Tech Stack:** PHP 8.3, Symfony 7.4, Doctrine ORM 3, PhpSpreadsheet (`phpoffice/phpspreadsheet`), PHPUnit

---

## Fichiers concernés

| Fichier | Action |
|---|---|
| `src/Member/Domain/MembershipType.php` | Créer — enum |
| `src/Member/Domain/SubscriptionStatus.php` | Créer — enum |
| `src/Member/Domain/SeasonHelper.php` | Créer — service |
| `src/Member/Domain/MemberSubscription.php` | Créer — entité |
| `src/Member/Domain/MemberSubscriptionRepository.php` | Créer — interface |
| `src/Member/Application/Command/CreateMemberSubscriptionCommand.php` | Créer |
| `src/Member/Application/Command/CreateMemberSubscriptionHandler.php` | Créer |
| `src/Member/Application/Command/UpdateMemberSubscriptionCommand.php` | Créer |
| `src/Member/Application/Command/UpdateMemberSubscriptionHandler.php` | Créer |
| `src/Member/Application/Command/StartNewSeasonCommand.php` | Créer |
| `src/Member/Application/Command/StartNewSeasonHandler.php` | Créer |
| `src/Member/Application/Query/GetCurrentSubscriptionQuery.php` | Créer |
| `src/Member/Application/Query/GetCurrentSubscriptionHandler.php` | Créer |
| `src/Member/Application/Query/GetSubscriptionHistoryQuery.php` | Créer |
| `src/Member/Application/Query/GetSubscriptionHistoryHandler.php` | Créer |
| `src/Member/Application/Query/MatchMemberQuery.php` | Modifier — ajouter `requireTournamentAccess` |
| `src/Member/Application/Query/MatchMemberHandler.php` | Modifier — throw au lieu de return false, vérifier cotisation |
| `src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php` | Créer |
| `src/Member/Infrastructure/Http/Admin/MemberController.php` | Modifier — show, export, start season, list, new, edit |
| `src/Member/Infrastructure/Http/Admin/Form/MemberType.php` | Modifier — ajouter champs cotisation |
| `src/Registration/Application/Command/RegisterHandler.php` | Modifier — ne plus vérifier le retour de MatchMember |
| `templates/admin/member/list.html.twig` | Modifier — colonnes + boutons |
| `templates/admin/member/show.html.twig` | Créer |
| `templates/admin/member/new.html.twig` | Modifier — bloc cotisation |
| `templates/admin/member/edit.html.twig` | Modifier — bloc cotisation |
| `migrations/VersionXXXXXXXXXXXXXX.php` | Créer — table member_subscriptions |
| `tests/Member/Domain/SeasonHelperTest.php` | Créer |
| `tests/Member/Domain/MemberSubscriptionTest.php` | Créer |
| `tests/Member/Application/CreateMemberSubscriptionHandlerTest.php` | Créer |
| `tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php` | Créer |
| `tests/Member/Application/StartNewSeasonHandlerTest.php` | Créer |
| `tests/Member/Application/GetCurrentSubscriptionHandlerTest.php` | Créer |
| `tests/Member/Application/MatchMemberHandlerTest.php` | Créer |
| `tests/Registration/Application/RegisterHandlerTest.php` | Modifier — adapter le mock MatchMember |

---

### Task 1 : Enums + SeasonHelper

**Files:**
- Create: `src/Member/Domain/MembershipType.php`
- Create: `src/Member/Domain/SubscriptionStatus.php`
- Create: `src/Member/Domain/SeasonHelper.php`
- Create: `tests/Member/Domain/SeasonHelperTest.php`

- [ ] **Step 1 : Écrire le test SeasonHelper**

```php
<?php
namespace App\Tests\Member\Domain;

use App\Member\Domain\SeasonHelper;
use PHPUnit\Framework\TestCase;

final class SeasonHelperTest extends TestCase
{
    private SeasonHelper $h;
    protected function setUp(): void { $this->h = new SeasonHelper(); }

    public function test_current_season_before_september(): void
    {
        self::assertSame('2025-2026', $this->h->currentSeason(new \DateTimeImmutable('2026-08-31')));
    }

    public function test_current_season_from_september(): void
    {
        self::assertSame('2026-2027', $this->h->currentSeason(new \DateTimeImmutable('2026-09-01')));
    }

    public function test_next_season_before_september(): void
    {
        self::assertSame('2026-2027', $this->h->nextSeason(new \DateTimeImmutable('2026-05-01')));
    }

    public function test_next_season_from_september(): void
    {
        self::assertSame('2027-2028', $this->h->nextSeason(new \DateTimeImmutable('2026-09-01')));
    }

    public function test_previous_season(): void
    {
        self::assertSame('2024-2025', $this->h->previousSeason(new \DateTimeImmutable('2026-05-01')));
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
docker compose exec php php bin/phpunit tests/Member/Domain/SeasonHelperTest.php --testdox
```

Expected : FAIL — `SeasonHelper not found`

- [ ] **Step 3 : Créer les enums**

```php
<?php
// src/Member/Domain/MembershipType.php
namespace App\Member\Domain;

enum MembershipType: string
{
    case TERRAIN = 'TERRAIN';
    case TERRAIN_TOURNOIS = 'TERRAIN_TOURNOIS';
    case TERRAIN_TOURNOIS_COURS = 'TERRAIN_TOURNOIS_COURS';

    public function label(): string
    {
        return match($this) {
            self::TERRAIN => 'Terrains',
            self::TERRAIN_TOURNOIS => 'Terrains + Tournois',
            self::TERRAIN_TOURNOIS_COURS => 'Terrains + Tournois + Cours',
        };
    }

    public function hasTournamentAccess(): bool
    {
        return $this !== self::TERRAIN;
    }

    public static function fromLabel(string $label): self
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case;
            }
        }
        throw new \InvalidArgumentException("Type de cotisation invalide : $label");
    }
}
```

```php
<?php
// src/Member/Domain/SubscriptionStatus.php
namespace App\Member\Domain;

enum SubscriptionStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payé',
        };
    }

    public static function fromLabel(string $label): self
    {
        return match($label) {
            'Payé' => self::PAID,
            'En attente' => self::PENDING,
            default => throw new \InvalidArgumentException("Statut de paiement invalide : $label"),
        };
    }
}
```

- [ ] **Step 4 : Créer SeasonHelper**

```php
<?php
// src/Member/Domain/SeasonHelper.php
namespace App\Member\Domain;

final class SeasonHelper
{
    public function currentSeason(\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? "$year-" . ($year + 1) : ($year - 1) . "-$year";
    }

    public function nextSeason(\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? ($year + 1) . '-' . ($year + 2) : "$year-" . ($year + 1);
    }

    public function previousSeason(\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable();
        $year = (int)$now->format('Y');
        $month = (int)$now->format('n');
        return $month >= 9 ? ($year - 1) . "-$year" : ($year - 2) . '-' . ($year - 1);
    }
}
```

- [ ] **Step 5 : Lancer le test pour vérifier qu'il passe**

```bash
docker compose exec php php bin/phpunit tests/Member/Domain/SeasonHelperTest.php --testdox
```

Expected : 5 tests, 5 assertions — PASS

- [ ] **Step 6 : Commiter**

```bash
git add src/Member/Domain/MembershipType.php src/Member/Domain/SubscriptionStatus.php src/Member/Domain/SeasonHelper.php tests/Member/Domain/SeasonHelperTest.php
git commit -m "feat(member): enums MembershipType, SubscriptionStatus et SeasonHelper"
```

---

### Task 2 : Entité MemberSubscription + interface repository

**Files:**
- Create: `src/Member/Domain/MemberSubscription.php`
- Create: `src/Member/Domain/MemberSubscriptionRepository.php`
- Create: `tests/Member/Domain/MemberSubscriptionTest.php`

- [ ] **Step 1 : Écrire le test MemberSubscription**

```php
<?php
// tests/Member/Domain/MemberSubscriptionTest.php
namespace App\Tests\Member\Domain;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MemberSubscriptionTest extends TestCase
{
    public function test_creation_defaults_to_pending(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'member-uuid-1', '2025-2026', MembershipType::TERRAIN_TOURNOIS
        );
        self::assertSame(SubscriptionStatus::PENDING, $sub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame('2025-2026', $sub->season());
        self::assertSame('member-uuid-1', $sub->memberId());
        self::assertNotNull($sub->createdAt());
        self::assertNotNull($sub->updatedAt());
    }

    public function test_creation_with_explicit_status(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'm2', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID
        );
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }

    public function test_update_changes_type_and_status(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN
        );
        $sub->update(MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID);
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
docker compose exec php php bin/phpunit tests/Member/Domain/MemberSubscriptionTest.php --testdox
```

Expected : FAIL — `MemberSubscription not found`

- [ ] **Step 3 : Créer MemberSubscription**

```php
<?php
// src/Member/Domain/MemberSubscription.php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'member_subscriptions')]
#[ORM\UniqueConstraint(name: 'uq_member_season', columns: ['member_id', 'season'])]
class MemberSubscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $memberId;

    #[ORM\Column(type: 'string', length: 9)]
    private string $season;

    #[ORM\Column(type: 'string', length: 30, enumType: MembershipType::class)]
    private MembershipType $type;

    #[ORM\Column(type: 'string', length: 10, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        string $memberId,
        string $season,
        MembershipType $type,
        SubscriptionStatus $status,
    ) {
        $this->id = $id;
        $this->memberId = $memberId;
        $this->season = $season;
        $this->type = $type;
        $this->status = $status;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        Uuid $id,
        string $memberId,
        string $season,
        MembershipType $type,
        SubscriptionStatus $status = SubscriptionStatus::PENDING,
    ): self {
        return new self($id, $memberId, $season, $type, $status);
    }

    public function update(MembershipType $type, SubscriptionStatus $status): void
    {
        $this->type = $type;
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function id(): Uuid { return $this->id; }
    public function memberId(): string { return $this->memberId; }
    public function season(): string { return $this->season; }
    public function type(): MembershipType { return $this->type; }
    public function status(): SubscriptionStatus { return $this->status; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function updatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
```

- [ ] **Step 4 : Créer MemberSubscriptionRepository**

```php
<?php
// src/Member/Domain/MemberSubscriptionRepository.php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Uuid;

interface MemberSubscriptionRepository
{
    public function save(MemberSubscription $subscription): void;
    public function get(Uuid $id): ?MemberSubscription;
    public function findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription;
    /** @return MemberSubscription[] */
    public function findPaidBySeason(string $season): array;
    /** @return MemberSubscription[] triées par saison DESC */
    public function findByMember(string $memberId): array;
    /** @return MemberSubscription[] */
    public function findBySeason(string $season): array;
    public function hasAnySeason(string $season): bool;
}
```

- [ ] **Step 5 : Lancer le test pour vérifier qu'il passe**

```bash
docker compose exec php php bin/phpunit tests/Member/Domain/MemberSubscriptionTest.php --testdox
```

Expected : 3 tests, 7 assertions — PASS

- [ ] **Step 6 : Commiter**

```bash
git add src/Member/Domain/MemberSubscription.php src/Member/Domain/MemberSubscriptionRepository.php tests/Member/Domain/MemberSubscriptionTest.php
git commit -m "feat(member): entité MemberSubscription et interface repository"
```

---

### Task 3 : Migration + DoctrineMemberSubscriptionRepository

**Files:**
- Create: `migrations/VersionXXX.php` (nom généré par Doctrine)
- Create: `src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php`

- [ ] **Step 1 : Générer la migration**

```bash
docker compose exec php php bin/console doctrine:migrations:diff --no-interaction
```

Expected : `Generated new migration class to "migrations/VersionXXXXXXXXXXXXXX.php"` — le fichier contient un `CREATE TABLE member_subscriptions`.

- [ ] **Step 2 : Appliquer la migration**

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

Expected : `[OK] Successfully executed 1 migrations.`

- [ ] **Step 3 : Créer DoctrineMemberSubscriptionRepository**

```php
<?php
// src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php
namespace App\Member\Infrastructure\Doctrine;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMemberSubscriptionRepository implements MemberSubscriptionRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(MemberSubscription $s): void
    {
        $this->em->persist($s);
        $this->em->flush();
    }

    public function get(Uuid $id): ?MemberSubscription
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findOneBy(['id' => (string)$id]);
    }

    public function findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findOneBy(['memberId' => $memberId, 'season' => $season]);
    }

    public function findPaidBySeason(string $season): array
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findBy(['season' => $season, 'status' => SubscriptionStatus::PAID]);
    }

    public function findByMember(string $memberId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s')->from(MemberSubscription::class, 's')
            ->where('s.memberId = :memberId')
            ->setParameter('memberId', $memberId)
            ->orderBy('s.season', 'DESC')
            ->getQuery()->getResult();
    }

    public function findBySeason(string $season): array
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->findBy(['season' => $season]);
    }

    public function hasAnySeason(string $season): bool
    {
        return $this->em->getRepository(MemberSubscription::class)
            ->count(['season' => $season]) > 0;
    }
}
```

- [ ] **Step 4 : Vérifier que tous les tests passent**

```bash
docker compose exec php php bin/phpunit --testdox
```

Expected : tous les tests précédents passent toujours (31+ tests).

- [ ] **Step 5 : Commiter**

```bash
git add migrations/ src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php
git commit -m "feat(member): migration member_subscriptions + repository Doctrine"
```

---

### Task 4 : Commandes CreateMemberSubscription + UpdateMemberSubscription

**Files:**
- Create: `src/Member/Application/Command/CreateMemberSubscriptionCommand.php`
- Create: `src/Member/Application/Command/CreateMemberSubscriptionHandler.php`
- Create: `src/Member/Application/Command/UpdateMemberSubscriptionCommand.php`
- Create: `src/Member/Application/Command/UpdateMemberSubscriptionHandler.php`
- Create: `tests/Member/Application/CreateMemberSubscriptionHandlerTest.php`
- Create: `tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php`

- [ ] **Step 1 : Écrire les tests**

```php
<?php
// tests/Member/Application/CreateMemberSubscriptionHandlerTest.php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\CreateMemberSubscriptionCommand;
use App\Member\Application\Command\CreateMemberSubscriptionHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CreateMemberSubscriptionHandlerTest extends TestCase
{
    private function makeRepo(): MemberSubscriptionRepository
    {
        return new class implements MemberSubscriptionRepository {
            public array $store = [];
            public function save(MemberSubscription $s): void { $this->store[(string)$s->id()] = $s; }
            public function get(Uuid $id): ?MemberSubscription { return $this->store[(string)$id] ?? null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription {
                foreach ($this->store as $sub) {
                    if ($sub->memberId() === $m && $sub->season() === $s) return $sub;
                }
                return null;
            }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
    }

    public function test_creates_pending_subscription(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN_TOURNOIS));
        $sub = $repo->findByMemberAndSeason('m1', '2025-2026');
        self::assertNotNull($sub);
        self::assertSame(SubscriptionStatus::PENDING, $sub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
    }

    public function test_creates_with_explicit_paid_status(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID));
        self::assertSame(SubscriptionStatus::PAID, $repo->findByMemberAndSeason('m1', '2025-2026')->status());
    }

    public function test_throws_on_duplicate_season(): void
    {
        $repo = $this->makeRepo();
        $handler = new CreateMemberSubscriptionHandler($repo);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN));
        $this->expectException(\DomainException::class);
        ($handler)(new CreateMemberSubscriptionCommand('m1', '2025-2026', MembershipType::TERRAIN));
    }
}
```

```php
<?php
// tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\UpdateMemberSubscriptionCommand;
use App\Member\Application\Command\UpdateMemberSubscriptionHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class UpdateMemberSubscriptionHandlerTest extends TestCase
{
    public function test_updates_type_and_status(): void
    {
        $sub = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN);
        $repo = new class($sub) implements MemberSubscriptionRepository {
            public array $store;
            public function __construct(MemberSubscription $s) { $this->store[(string)$s->id()] = $s; }
            public function save(MemberSubscription $s): void { $this->store[(string)$s->id()] = $s; }
            public function get(Uuid $id): ?MemberSubscription { return $this->store[(string)$id] ?? null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return null; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
        $handler = new UpdateMemberSubscriptionHandler($repo);
        ($handler)(new UpdateMemberSubscriptionCommand((string)$sub->id(), MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID));
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $sub->type());
        self::assertSame(SubscriptionStatus::PAID, $sub->status());
    }

    public function test_throws_if_not_found(): void
    {
        $repo = new class implements MemberSubscriptionRepository {
            public function save(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return null; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
        $handler = new UpdateMemberSubscriptionHandler($repo);
        $this->expectException(\DomainException::class);
        ($handler)(new UpdateMemberSubscriptionCommand(Uuid::generate()->__toString(), MembershipType::TERRAIN, SubscriptionStatus::PENDING));
    }
}
```

- [ ] **Step 2 : Lancer les tests pour vérifier qu'ils échouent**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/CreateMemberSubscriptionHandlerTest.php tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php --testdox
```

Expected : FAIL — classes introuvables

- [ ] **Step 3 : Créer les commandes et handlers**

```php
<?php
// src/Member/Application/Command/CreateMemberSubscriptionCommand.php
namespace App\Member\Application\Command;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;

final class CreateMemberSubscriptionCommand
{
    public function __construct(
        public readonly string $memberId,
        public readonly string $season,
        public readonly MembershipType $type,
        public readonly SubscriptionStatus $status = SubscriptionStatus::PENDING,
    ) {}
}
```

```php
<?php
// src/Member/Application/Command/CreateMemberSubscriptionHandler.php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(CreateMemberSubscriptionCommand $c): void
    {
        if ($this->repo->findByMemberAndSeason($c->memberId, $c->season) !== null) {
            throw new \DomainException("Une souscription existe déjà pour ce membre pour la saison {$c->season}.");
        }
        $this->repo->save(MemberSubscription::create(
            Uuid::generate(), $c->memberId, $c->season, $c->type, $c->status,
        ));
    }
}
```

```php
<?php
// src/Member/Application/Command/UpdateMemberSubscriptionCommand.php
namespace App\Member\Application\Command;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;

final class UpdateMemberSubscriptionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly MembershipType $type,
        public readonly SubscriptionStatus $status,
    ) {}
}
```

```php
<?php
// src/Member/Application/Command/UpdateMemberSubscriptionHandler.php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscriptionRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberSubscriptionHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(UpdateMemberSubscriptionCommand $c): void
    {
        $sub = $this->repo->get(Uuid::fromString($c->id))
            ?? throw new \DomainException("Souscription introuvable : {$c->id}");
        $sub->update($c->type, $c->status);
        $this->repo->save($sub);
    }
}
```

- [ ] **Step 4 : Lancer les tests pour vérifier qu'ils passent**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/CreateMemberSubscriptionHandlerTest.php tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php --testdox
```

Expected : 5 tests, PASS

- [ ] **Step 5 : Commiter**

```bash
git add src/Member/Application/Command/CreateMemberSubscriptionCommand.php \
        src/Member/Application/Command/CreateMemberSubscriptionHandler.php \
        src/Member/Application/Command/UpdateMemberSubscriptionCommand.php \
        src/Member/Application/Command/UpdateMemberSubscriptionHandler.php \
        tests/Member/Application/CreateMemberSubscriptionHandlerTest.php \
        tests/Member/Application/UpdateMemberSubscriptionHandlerTest.php
git commit -m "feat(member): commandes create/update subscription"
```

---

### Task 5 : Commande StartNewSeason

**Files:**
- Create: `src/Member/Application/Command/StartNewSeasonCommand.php`
- Create: `src/Member/Application/Command/StartNewSeasonHandler.php`
- Create: `tests/Member/Application/StartNewSeasonHandlerTest.php`

- [ ] **Step 1 : Écrire le test**

```php
<?php
// tests/Member/Application/StartNewSeasonHandlerTest.php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\StartNewSeasonCommand;
use App\Member\Application\Command\StartNewSeasonHandler;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class StartNewSeasonHandlerTest extends TestCase
{
    private function makeRepo(): MemberSubscriptionRepository
    {
        return new class implements MemberSubscriptionRepository {
            public array $store = [];
            public function save(MemberSubscription $s): void { $this->store[(string)$s->id()] = $s; }
            public function get(Uuid $id): ?MemberSubscription { return $this->store[(string)$id] ?? null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription {
                foreach ($this->store as $sub) {
                    if ($sub->memberId() === $m && $sub->season() === $s) return $sub;
                }
                return null;
            }
            public function findPaidBySeason(string $s): array {
                return array_values(array_filter($this->store,
                    fn($sub) => $sub->season() === $s && $sub->status() === SubscriptionStatus::PAID));
            }
            public function findByMember(string $m): array {
                return array_values(array_filter($this->store, fn($s) => $s->memberId() === $m));
            }
            public function findBySeason(string $s): array {
                return array_values(array_filter($this->store, fn($sub) => $sub->season() === $s));
            }
            public function hasAnySeason(string $s): bool {
                foreach ($this->store as $sub) { if ($sub->season() === $s) return true; }
                return false;
            }
        };
    }

    public function test_creates_pending_for_paid_members_of_previous_season(): void
    {
        $repo = $this->makeRepo();
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID));
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm2', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PENDING));

        (new StartNewSeasonHandler($repo))(new StartNewSeasonCommand('2026-2027'));

        $newSub = $repo->findByMemberAndSeason('m1', '2026-2027');
        self::assertNotNull($newSub);
        self::assertSame(SubscriptionStatus::PENDING, $newSub->status());
        self::assertSame(MembershipType::TERRAIN_TOURNOIS, $newSub->type());
        self::assertNull($repo->findByMemberAndSeason('m2', '2026-2027'));
    }

    public function test_idempotent(): void
    {
        $repo = $this->makeRepo();
        $repo->save(MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN, SubscriptionStatus::PAID));
        $handler = new StartNewSeasonHandler($repo);
        ($handler)(new StartNewSeasonCommand('2026-2027'));
        ($handler)(new StartNewSeasonCommand('2026-2027'));
        self::assertCount(2, $repo->store);
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/StartNewSeasonHandlerTest.php --testdox
```

Expected : FAIL

- [ ] **Step 3 : Créer la commande et le handler**

```php
<?php
// src/Member/Application/Command/StartNewSeasonCommand.php
namespace App\Member\Application\Command;

final class StartNewSeasonCommand
{
    public function __construct(public readonly string $season) {}
}
```

```php
<?php
// src/Member/Application/Command/StartNewSeasonHandler.php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class StartNewSeasonHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(StartNewSeasonCommand $c): void
    {
        if ($this->repo->hasAnySeason($c->season)) {
            return;
        }
        $previousSeason = $this->previousSeason($c->season);
        foreach ($this->repo->findPaidBySeason($previousSeason) as $old) {
            $this->repo->save(MemberSubscription::create(
                Uuid::generate(), $old->memberId(), $c->season, $old->type(), SubscriptionStatus::PENDING,
            ));
        }
    }

    private function previousSeason(string $season): string
    {
        [$start] = explode('-', $season);
        return ($start - 1) . '-' . $start;
    }
}
```

- [ ] **Step 4 : Lancer le test pour vérifier qu'il passe**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/StartNewSeasonHandlerTest.php --testdox
```

Expected : 2 tests, PASS

- [ ] **Step 5 : Commiter**

```bash
git add src/Member/Application/Command/StartNewSeasonCommand.php \
        src/Member/Application/Command/StartNewSeasonHandler.php \
        tests/Member/Application/StartNewSeasonHandlerTest.php
git commit -m "feat(member): commande StartNewSeason"
```

---

### Task 6 : Queries GetCurrentSubscription + GetSubscriptionHistory

**Files:**
- Create: `src/Member/Application/Query/GetCurrentSubscriptionQuery.php`
- Create: `src/Member/Application/Query/GetCurrentSubscriptionHandler.php`
- Create: `src/Member/Application/Query/GetSubscriptionHistoryQuery.php`
- Create: `src/Member/Application/Query/GetSubscriptionHistoryHandler.php`
- Create: `tests/Member/Application/GetCurrentSubscriptionHandlerTest.php`

- [ ] **Step 1 : Écrire le test**

```php
<?php
// tests/Member/Application/GetCurrentSubscriptionHandlerTest.php
namespace App\Tests\Member\Application;

use App\Member\Application\Query\GetCurrentSubscriptionHandler;
use App\Member\Application\Query\GetCurrentSubscriptionQuery;
use App\Member\Application\Query\GetSubscriptionHistoryHandler;
use App\Member\Application\Query\GetSubscriptionHistoryQuery;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class GetCurrentSubscriptionHandlerTest extends TestCase
{
    private function makeRepo(array $subs): MemberSubscriptionRepository
    {
        return new class($subs) implements MemberSubscriptionRepository {
            public function __construct(private array $store) {}
            public function save(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription {
                foreach ($this->store as $sub) {
                    if ($sub->memberId() === $m && $sub->season() === $s) return $sub;
                }
                return null;
            }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array {
                return array_values(array_filter($this->store, fn($s) => $s->memberId() === $m));
            }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
    }

    public function test_returns_current_season_subscription(): void
    {
        $now = new \DateTimeImmutable('2026-05-01');
        $sub = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS);
        $repo = $this->makeRepo([$sub]);
        $handler = new GetCurrentSubscriptionHandler($repo, new SeasonHelper());
        $result = ($handler)(new GetCurrentSubscriptionQuery('m1'), $now);
        self::assertSame($sub, $result);
    }

    public function test_returns_null_when_no_subscription(): void
    {
        $now = new \DateTimeImmutable('2026-05-01');
        $repo = $this->makeRepo([]);
        $handler = new GetCurrentSubscriptionHandler($repo, new SeasonHelper());
        self::assertNull(($handler)(new GetCurrentSubscriptionQuery('m1'), $now));
    }

    public function test_history_returns_all_subscriptions(): void
    {
        $sub1 = MemberSubscription::create(Uuid::generate(), 'm1', '2024-2025', MembershipType::TERRAIN);
        $sub2 = MemberSubscription::create(Uuid::generate(), 'm1', '2025-2026', MembershipType::TERRAIN_TOURNOIS);
        $repo = $this->makeRepo([$sub1, $sub2]);
        $handler = new GetSubscriptionHistoryHandler($repo);
        $result = ($handler)(new GetSubscriptionHistoryQuery('m1'));
        self::assertCount(2, $result);
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/GetCurrentSubscriptionHandlerTest.php --testdox
```

Expected : FAIL

- [ ] **Step 3 : Créer les queries et handlers**

```php
<?php
// src/Member/Application/Query/GetCurrentSubscriptionQuery.php
namespace App\Member\Application\Query;

final class GetCurrentSubscriptionQuery
{
    public function __construct(public readonly string $memberId) {}
}
```

```php
<?php
// src/Member/Application/Query/GetCurrentSubscriptionHandler.php
namespace App\Member\Application\Query;

use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;

final class GetCurrentSubscriptionHandler
{
    public function __construct(
        private MemberSubscriptionRepository $repo,
        private SeasonHelper $seasonHelper,
    ) {}

    public function __invoke(GetCurrentSubscriptionQuery $q, \DateTimeImmutable $now = null): ?MemberSubscription
    {
        return $this->repo->findByMemberAndSeason(
            $q->memberId,
            $this->seasonHelper->currentSeason($now),
        );
    }
}
```

```php
<?php
// src/Member/Application/Query/GetSubscriptionHistoryQuery.php
namespace App\Member\Application\Query;

final class GetSubscriptionHistoryQuery
{
    public function __construct(public readonly string $memberId) {}
}
```

```php
<?php
// src/Member/Application/Query/GetSubscriptionHistoryHandler.php
namespace App\Member\Application\Query;

use App\Member\Domain\MemberSubscriptionRepository;

final class GetSubscriptionHistoryHandler
{
    public function __construct(private MemberSubscriptionRepository $repo) {}

    public function __invoke(GetSubscriptionHistoryQuery $q): array
    {
        return $this->repo->findByMember($q->memberId);
    }
}
```

- [ ] **Step 4 : Lancer le test pour vérifier qu'il passe**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/GetCurrentSubscriptionHandlerTest.php --testdox
```

Expected : 3 tests, PASS

- [ ] **Step 5 : Commiter**

```bash
git add src/Member/Application/Query/GetCurrentSubscriptionQuery.php \
        src/Member/Application/Query/GetCurrentSubscriptionHandler.php \
        src/Member/Application/Query/GetSubscriptionHistoryQuery.php \
        src/Member/Application/Query/GetSubscriptionHistoryHandler.php \
        tests/Member/Application/GetCurrentSubscriptionHandlerTest.php
git commit -m "feat(member): queries GetCurrentSubscription + GetSubscriptionHistory"
```

---

### Task 7 : MatchMember mis à jour + RegisterHandler adapté

**Files:**
- Modify: `src/Member/Application/Query/MatchMemberQuery.php`
- Modify: `src/Member/Application/Query/MatchMemberHandler.php`
- Modify: `src/Registration/Application/Command/RegisterHandler.php`
- Create: `tests/Member/Application/MatchMemberHandlerTest.php`
- Modify: `tests/Registration/Application/RegisterHandlerTest.php`

- [ ] **Step 1 : Écrire le test MatchMemberHandler**

```php
<?php
// tests/Member/Application/MatchMemberHandlerTest.php
namespace App\Tests\Member\Application;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Member\Domain\MemberSubscription;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\MembershipType;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MatchMemberHandlerTest extends TestCase
{
    private Member $member;
    private Uuid $memberId;

    protected function setUp(): void
    {
        $this->memberId = Uuid::generate();
        $this->member = Member::create($this->memberId, 'Dupont', 'Jean', PhoneNumber::fromString('0612345678'), null);
    }

    private function memberRepo(bool $found): MemberRepository
    {
        $member = $found ? $this->member : null;
        return new class($member) implements MemberRepository {
            public function __construct(private ?Member $m) {}
            public function save(Member $m): void {}
            public function remove(Member $m): void {}
            public function get(Uuid $id): ?Member { return null; }
            public function search(?string $q): array { return []; }
            public function findByLastNameAndPhone(string $l, PhoneNumber $p): ?Member { return $this->m; }
        };
    }

    private function subRepo(?MemberSubscription $sub): MemberSubscriptionRepository
    {
        return new class($sub) implements MemberSubscriptionRepository {
            public function __construct(private ?MemberSubscription $sub) {}
            public function save(MemberSubscription $s): void {}
            public function get(Uuid $id): ?MemberSubscription { return null; }
            public function findByMemberAndSeason(string $m, string $s): ?MemberSubscription { return $this->sub; }
            public function findPaidBySeason(string $s): array { return []; }
            public function findByMember(string $m): array { return []; }
            public function findBySeason(string $s): array { return []; }
            public function hasAnySeason(string $s): bool { return false; }
        };
    }

    private function paidSub(): MemberSubscription
    {
        return MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PAID
        );
    }

    public function test_throws_when_member_not_found(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(false), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/membres du club/');
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_no_subscription_for_current_season(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessageMatches('/accès aux tournois/');
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_is_pending(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN_TOURNOIS, SubscriptionStatus::PENDING
        );
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_throws_when_subscription_type_is_terrain_only(): void
    {
        $sub = MemberSubscription::create(
            Uuid::generate(), (string)$this->memberId, '2025-2026',
            MembershipType::TERRAIN, SubscriptionStatus::PAID
        );
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($sub), new SeasonHelper());
        $this->expectException(\DomainException::class);
        ($h)(new MatchMemberQuery('Dupont', '0612345678'));
    }

    public function test_returns_true_when_paid_with_tournament_access(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo($this->paidSub()), new SeasonHelper());
        self::assertTrue(($h)(new MatchMemberQuery('Dupont', '0612345678')));
    }

    public function test_returns_true_without_tournament_access_check_when_flag_false(): void
    {
        $h = new MatchMemberHandler($this->memberRepo(true), $this->subRepo(null), new SeasonHelper());
        self::assertTrue(($h)(new MatchMemberQuery('Dupont', '0612345678', false)));
    }
}
```

- [ ] **Step 2 : Lancer le test pour vérifier qu'il échoue**

```bash
docker compose exec php php bin/phpunit tests/Member/Application/MatchMemberHandlerTest.php --testdox
```

Expected : FAIL

- [ ] **Step 3 : Mettre à jour MatchMemberQuery**

Remplacer le contenu de `src/Member/Application/Query/MatchMemberQuery.php` :

```php
<?php
namespace App\Member\Application\Query;

final class MatchMemberQuery
{
    public function __construct(
        public readonly string $lastName,
        public readonly string $phone,
        public readonly bool $requireTournamentAccess = true,
    ) {}
}
```

- [ ] **Step 4 : Mettre à jour MatchMemberHandler**

Remplacer le contenu de `src/Member/Application/Query/MatchMemberHandler.php` :

```php
<?php
namespace App\Member\Application\Query;

use App\Member\Domain\MemberRepository;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;

class MatchMemberHandler
{
    public function __construct(
        private MemberRepository $repo,
        private MemberSubscriptionRepository $subscriptionRepo,
        private SeasonHelper $seasonHelper,
    ) {}

    public function __invoke(MatchMemberQuery $q): bool
    {
        $member = $this->repo->findByLastNameAndPhone($q->lastName, PhoneNumber::fromString($q->phone));
        if ($member === null) {
            throw new \DomainException('Ce tournoi est réservé aux membres du club.');
        }
        if (!$q->requireTournamentAccess) {
            return true;
        }
        $sub = $this->subscriptionRepo->findByMemberAndSeason(
            (string)$member->id(),
            $this->seasonHelper->currentSeason(),
        );
        if ($sub === null || !$sub->type()->hasTournamentAccess() || $sub->status() !== SubscriptionStatus::PAID) {
            throw new \DomainException('Ce tournoi est réservé aux membres avec accès aux tournois (cotisation Terrains + Tournois ou Terrains + Tournois + Cours).');
        }
        return true;
    }
}
```

- [ ] **Step 5 : Mettre à jour RegisterHandler**

Dans `src/Registration/Application/Command/RegisterHandler.php`, remplacer le bloc MEMBERS_ONLY :

```php
// Avant (lignes 26-29) :
if ($t->type() === TournamentType::MEMBERS_ONLY) {
    if (!($this->matchMember)(new MatchMemberQuery($c->lastName, $c->phone)))
        throw new \DomainException('Ce tournoi est réservé aux membres du club');
}

// Après :
if ($t->type() === TournamentType::MEMBERS_ONLY) {
    ($this->matchMember)(new MatchMemberQuery($c->lastName, $c->phone));
}
```

- [ ] **Step 6 : Mettre à jour RegisterHandlerTest**

Dans `tests/Registration/Application/RegisterHandlerTest.php`, mettre à jour le mock `$match` dans la méthode `fakes()` (lignes 56-61) :

```php
$match = new class extends MatchMemberHandler {
    public function __construct() {}
    public bool $ok = true;
    public function __invoke(MatchMemberQuery $q): bool {
        if (!$this->ok) {
            throw new \DomainException('Ce tournoi est réservé aux membres du club.');
        }
        return true;
    }
};
```

- [ ] **Step 7 : Lancer tous les tests**

```bash
docker compose exec php php bin/phpunit --testdox
```

Expected : tous passent (31+ tests)

- [ ] **Step 8 : Commiter**

```bash
git add src/Member/Application/Query/MatchMemberQuery.php \
        src/Member/Application/Query/MatchMemberHandler.php \
        src/Registration/Application/Command/RegisterHandler.php \
        tests/Member/Application/MatchMemberHandlerTest.php \
        tests/Registration/Application/RegisterHandlerTest.php
git commit -m "feat(member): MatchMember vérifie la cotisation tournois, RegisterHandler adapté"
```

---

### Task 8 : Export Excel (PhpSpreadsheet)

**Files:**
- Modify: `src/Member/Domain/MemberSubscriptionRepository.php` (déjà fait en Task 2)
- Modify: `src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php` (déjà fait en Task 3)
- Modify: `src/Member/Infrastructure/Http/Admin/MemberController.php` — ajouter action `export`

- [ ] **Step 1 : Installer phpspreadsheet**

```bash
docker compose exec php composer require phpoffice/phpspreadsheet --no-interaction
```

Expected : `phpoffice/phpspreadsheet` apparaît dans `composer.json`

- [ ] **Step 2 : Ajouter l'action export dans MemberController**

Ajouter cette action dans `src/Member/Infrastructure/Http/Admin/MemberController.php`, avant l'action `delete` :

```php
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
```

```php
#[Route('/export', name: 'admin_member_export', methods: ['GET'])]
public function export(
    MemberRepository $repo,
    MemberSubscriptionRepository $subRepo,
    SeasonHelper $seasonHelper,
): Response {
    $season = $seasonHelper->currentSeason();
    $members = $repo->search(null);
    $subsBySeason = [];
    foreach ($subRepo->findBySeason($season) as $sub) {
        $subsBySeason[$sub->memberId()] = $sub;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $headers = ['id', 'Nom', 'Prénom', 'Téléphone', 'Email', 'Date de naissance', 'Type de cotisation', 'Statut paiement', 'Saison'];
    $sheet->fromArray([$headers], null, 'A1');
    $sheet->getStyle('A1:I1')->getFont()->setBold(true);

    $row = 2;
    foreach ($members as $m) {
        $sub = $subsBySeason[(string)$m->id()] ?? null;
        $sheet->fromArray([[
            (string)$m->id(),
            $m->lastName(),
            $m->firstName(),
            (string)$m->phone(),
            $m->email() ? (string)$m->email() : '',
            $m->birthDate()?->format('d/m/Y') ?? '',
            $sub ? $sub->type()->label() : '—',
            $sub ? $sub->status()->label() : '—',
            $sub ? $sub->season() : '—',
        ]], null, 'A'.$row);
        $row++;
    }

    $response = new StreamedResponse(function () use ($spreadsheet): void {
        (new Xlsx($spreadsheet))->save('php://output');
    });
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment;filename="membres-'.$season.'.xlsx"');
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
}
```

- [ ] **Step 3 : Vérifier que tous les tests passent**

```bash
docker compose exec php php bin/phpunit --testdox
```

Expected : tous passent

- [ ] **Step 4 : Commiter**

```bash
git add composer.json composer.lock src/Member/Infrastructure/Http/Admin/MemberController.php
git commit -m "feat(member): export Excel membres saison courante"
```

---

### Task 9 : UI — MemberController (list, show, start-season, new, edit) + MemberType form + templates

**Files:**
- Modify: `src/Member/Infrastructure/Http/Admin/MemberController.php`
- Modify: `src/Member/Infrastructure/Http/Admin/Form/MemberType.php`
- Create: `templates/admin/member/show.html.twig`
- Modify: `templates/admin/member/list.html.twig`
- Modify: `templates/admin/member/new.html.twig`
- Modify: `templates/admin/member/edit.html.twig`

- [ ] **Step 1 : Mettre à jour MemberType — ajouter les champs cotisation**

Remplacer le contenu de `src/Member/Infrastructure/Http/Admin/Form/MemberType.php` :

```php
<?php
namespace App\Member\Infrastructure\Http\Admin\Form;

use App\Member\Domain\MembershipType;
use App\Member\Domain\SubscriptionStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add('lastName', TextType::class, ['label' => 'Nom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('firstName', TextType::class, ['label' => 'Prénom', 'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('phone', TextType::class, ['label' => 'Téléphone', 'constraints' => [new Assert\NotBlank()]])
          ->add('email', EmailType::class, ['label' => 'Email', 'required' => false])
          ->add('birthDate', TextType::class, [
              'label' => 'Date de naissance',
              'required' => false,
              'attr' => ['placeholder' => 'JJ/MM/AAAA'],
              'constraints' => [new Assert\Regex(['pattern' => '/^\d{2}\/\d{2}\/\d{4}$/', 'message' => 'Format attendu : JJ/MM/AAAA'])],
          ])
          ->add('membershipType', ChoiceType::class, [
              'label' => 'Type de cotisation',
              'required' => false,
              'choices' => array_combine(
                  array_map(fn($t) => $t->label(), MembershipType::cases()),
                  MembershipType::cases(),
              ),
              'placeholder' => '— Aucune cotisation —',
          ])
          ->add('subscriptionStatus', ChoiceType::class, [
              'label' => 'Statut paiement',
              'required' => false,
              'choices' => [
                  'En attente' => SubscriptionStatus::PENDING,
                  'Payé' => SubscriptionStatus::PAID,
              ],
              'placeholder' => '— Sélectionner —',
          ]);
    }
}
```

- [ ] **Step 2 : Mettre à jour l'action list dans MemberController**

Remplacer l'action `list` :

```php
#[Route('', name: 'admin_member_list', methods: ['GET'])]
public function list(
    Request $r,
    MemberRepository $repo,
    MemberSubscriptionRepository $subRepo,
    SeasonHelper $seasonHelper,
): Response {
    $q = $r->query->get('q', '');
    $members = $repo->search($q ?: null);
    $season = $seasonHelper->currentSeason();
    $subsBySeason = [];
    foreach ($subRepo->findBySeason($season) as $sub) {
        $subsBySeason[$sub->memberId()] = $sub;
    }
    $rows = array_map(fn($m) => [
        'member' => $m,
        'subscription' => $subsBySeason[(string)$m->id()] ?? null,
    ], $members);

    $pendingSeason = !$subRepo->hasAnySeason($season)
        ? $season
        : $seasonHelper->nextSeason();

    return $this->render('admin/member/list.html.twig', [
        'rows' => $rows,
        'q' => $q,
        'pendingSeason' => $pendingSeason,
        'showSeasonButton' => !$subRepo->hasAnySeason($pendingSeason),
        'currentSeason' => $season,
    ]);
}
```

- [ ] **Step 3 : Ajouter l'action show dans MemberController**

Ajouter cette action après l'action `list` :

```php
#[Route('/{id}', name: 'admin_member_show', methods: ['GET'])]
public function show(
    string $id,
    MemberRepository $repo,
    MemberSubscriptionRepository $subRepo,
): Response {
    $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
    return $this->render('admin/member/show.html.twig', [
        'member' => $m,
        'subscriptions' => $subRepo->findByMember((string)$m->id()),
    ]);
}
```

- [ ] **Step 4 : Ajouter l'action start-season dans MemberController**

Ajouter cette action (avec les imports nécessaires) :

```php
use App\Member\Application\Command\StartNewSeasonCommand;
use App\Member\Application\Command\StartNewSeasonHandler;
```

```php
#[Route('/start-season', name: 'admin_member_start_season', methods: ['POST'])]
public function startSeason(Request $r, StartNewSeasonHandler $h, SeasonHelper $seasonHelper): Response
{
    $season = $r->request->get('season', '');
    if ($this->isCsrfTokenValid('start-season', $r->request->get('_token'))) {
        ($h)(new StartNewSeasonCommand($season));
        $this->addFlash('success', "Saison $season démarrée. Tous les membres PAYÉS de la saison précédente sont passés en \"En attente\" pour la nouvelle saison.");
    }
    return $this->redirectToRoute('admin_member_list');
}
```

- [ ] **Step 5 : Mettre à jour l'action new dans MemberController**

Remplacer l'action `new` :

```php
#[Route('/new', name: 'admin_member_new', methods: ['GET', 'POST'])]
public function new(
    Request $r,
    CreateMemberHandler $h,
    CreateMemberSubscriptionHandler $subHandler,
    SeasonHelper $seasonHelper,
): Response {
    $form = $this->createForm(MemberType::class);
    $form->handleRequest($r);
    if ($form->isSubmitted() && $form->isValid()) {
        $d = $form->getData();
        $memberId = $h(new CreateMemberCommand($d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null, $d['birthDate'] ?? null));
        if ($d['membershipType'] !== null) {
            $subHandler(new CreateMemberSubscriptionCommand(
                (string)$memberId,
                $seasonHelper->currentSeason(),
                $d['membershipType'],
                $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING,
            ));
        }
        $this->addFlash('success', 'Membre créé');
        return $this->redirectToRoute('admin_member_list');
    }
    return $this->render('admin/member/new.html.twig', ['form' => $form]);
}
```

Ajouter les imports nécessaires en tête du fichier :

```php
use App\Member\Application\Command\CreateMemberSubscriptionCommand;
use App\Member\Application\Command\CreateMemberSubscriptionHandler;
use App\Member\Application\Command\UpdateMemberSubscriptionCommand;
use App\Member\Application\Command\UpdateMemberSubscriptionHandler;
use App\Member\Domain\MemberSubscriptionRepository;
use App\Member\Domain\SeasonHelper;
use App\Member\Domain\SubscriptionStatus;
```

- [ ] **Step 6 : Mettre à jour l'action edit dans MemberController**

Remplacer l'action `edit` :

```php
#[Route('/{id}/edit', name: 'admin_member_edit', methods: ['GET', 'POST'])]
public function edit(
    string $id,
    Request $r,
    MemberRepository $repo,
    UpdateMemberHandler $h,
    CreateMemberSubscriptionHandler $createSubHandler,
    UpdateMemberSubscriptionHandler $updateSubHandler,
    MemberSubscriptionRepository $subRepo,
    SeasonHelper $seasonHelper,
): Response {
    $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
    $currentSub = $subRepo->findByMemberAndSeason((string)$m->id(), $seasonHelper->currentSeason());
    $form = $this->createForm(MemberType::class, [
        'lastName' => $m->lastName(),
        'firstName' => $m->firstName(),
        'phone' => (string)$m->phone(),
        'email' => $m->email() ? (string)$m->email() : null,
        'birthDate' => $m->birthDate()?->format('d/m/Y'),
        'membershipType' => $currentSub?->type(),
        'subscriptionStatus' => $currentSub?->status(),
    ]);
    $form->handleRequest($r);
    if ($form->isSubmitted() && $form->isValid()) {
        $d = $form->getData();
        $h(new UpdateMemberCommand($id, $d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null, $d['birthDate'] ?? null));
        if ($d['membershipType'] !== null) {
            if ($currentSub !== null) {
                $updateSubHandler(new UpdateMemberSubscriptionCommand((string)$currentSub->id(), $d['membershipType'], $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING));
            } else {
                $createSubHandler(new CreateMemberSubscriptionCommand((string)$m->id(), $seasonHelper->currentSeason(), $d['membershipType'], $d['subscriptionStatus'] ?? SubscriptionStatus::PENDING));
            }
        }
        $this->addFlash('success', 'Membre mis à jour');
        return $this->redirectToRoute('admin_member_list');
    }
    return $this->render('admin/member/edit.html.twig', ['form' => $form, 'member' => $m]);
}
```

- [ ] **Step 7 : Créer le template show.html.twig**

```twig
{# templates/admin/member/show.html.twig #}
{% extends "base_admin.html.twig" %}
{% block title %}{{ member.firstName }} {{ member.lastName }}{% endblock %}
{% block body %}
  <h1>{{ member.firstName }} {{ member.lastName }}</h1>
  <table>
    <tr><th>Nom</th><td>{{ member.lastName }}</td></tr>
    <tr><th>Prénom</th><td>{{ member.firstName }}</td></tr>
    <tr><th>Téléphone</th><td>{{ member.phone }}</td></tr>
    <tr><th>Email</th><td>{{ member.email }}</td></tr>
    <tr><th>Date de naissance</th><td>{{ member.birthDate ? member.birthDate|date("d/m/Y") : '—' }}</td></tr>
  </table>

  <h2 style="margin-top:2rem">Historique des cotisations</h2>
  {% if subscriptions is empty %}
    <p>Aucune cotisation enregistrée.</p>
  {% else %}
    <table>
      <thead><tr><th>Saison</th><th>Type</th><th>Statut</th><th>Créé le</th></tr></thead>
      <tbody>
      {% for sub in subscriptions %}
        <tr>
          <td>{{ sub.season }}</td>
          <td>{{ sub.type.label }}</td>
          <td>
            <span style="background:{{ sub.status.value == 'PAID' ? '#198754' : '#fd7e14' }};color:#fff;padding:.2rem .5rem;border-radius:.25rem">
              {{ sub.status.label }}
            </span>
          </td>
          <td>{{ sub.createdAt|date("d/m/Y") }}</td>
        </tr>
      {% endfor %}
      </tbody>
    </table>
  {% endif %}

  <br>
  <a href="{{ path("admin_member_edit", {id: member.id}) }}" class="btn" style="background:#222273">Éditer</a>
  &nbsp;
  <a href="{{ path("admin_member_list") }}" class="btn">← Retour</a>
{% endblock %}
```

- [ ] **Step 8 : Mettre à jour list.html.twig**

Remplacer le contenu de `templates/admin/member/list.html.twig` :

```twig
{% extends "base_admin.html.twig" %}
{% block title %}Membres du club{% endblock %}
{% block body %}
  <h1>Membres du club — {{ currentSeason }}</h1>
  <h2>{{ rows|length }} Membres</h2>
  <form method="get" style="margin-bottom:1rem">
    <input name="q" value="{{ q }}" placeholder="Rechercher par nom, prénom, téléphone…" style="width:20rem;height:2rem">
    <button type="submit">Rechercher</button>
  </form>
  <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1rem">
    <a class="btn" href="{{ path("admin_member_new") }}">+ Nouveau membre</a>
    <a class="btn" href="{{ path("admin_member_import") }}" style="background:#1A2B6D">Importer CSV</a>
    <a class="btn" href="{{ path("admin_member_export") }}" style="background:#198754">Exporter Excel</a>
    {% if showSeasonButton %}
      <form method="post" action="{{ path("admin_member_start_season") }}" style="display:inline">
        <input type="hidden" name="_token" value="{{ csrf_token("start-season") }}">
        <input type="hidden" name="season" value="{{ pendingSeason }}">
        <button style="background:#E8721A" data-confirm="Démarrer la saison {{ pendingSeason }} ? Tous les membres PAYÉS de la saison précédente seront créés en « En attente » pour cette nouvelle saison.">
          Démarrer la saison {{ pendingSeason }}
        </button>
      </form>
    {% endif %}
  </div>
  <table>
    <thead>
      <tr>
        <th>Nom</th><th>Prénom</th><th>Téléphone</th><th>Email</th>
        <th>Type de cotisation</th><th>Statut paiement</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    {% for row in rows %}
      {% set m = row.member %}
      {% set sub = row.subscription %}
      <tr>
        <td>{{ m.lastName }}</td>
        <td>{{ m.firstName }}</td>
        <td>{{ m.phone }}</td>
        <td>{{ m.email }}</td>
        <td>{{ sub ? sub.type.label : '—' }}</td>
        <td>
          {% if sub %}
            <span style="background:{{ sub.status.value == 'PAID' ? '#198754' : '#fd7e14' }};color:#fff;padding:.15rem .4rem;border-radius:.25rem;font-size:.85rem">
              {{ sub.status.label }}
            </span>
          {% else %}
            —
          {% endif %}
        </td>
        <td style="white-space:nowrap">
          <a href="{{ path("admin_member_show", {id: m.id}) }}" class="btn">Voir</a>
          &nbsp;
          <a href="{{ path("admin_member_edit", {id: m.id}) }}" class="btn" style="background:#222273">Éditer</a>
          &nbsp;
          <form method="post" action="{{ path("admin_member_delete", {id: m.id}) }}" style="display:inline">
            <input type="hidden" name="_token" value="{{ csrf_token("del" ~ m.id) }}">
            <button style="background:#dc3545" data-confirm="Supprimer {{ m.firstName }} {{ m.lastName }} ?">Supprimer</button>
          </form>
        </td>
      </tr>
    {% else %}
      <tr><td colspan="7">Aucun membre trouvé.</td></tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```

- [ ] **Step 9 : Mettre à jour new.html.twig et edit.html.twig**

`templates/admin/member/new.html.twig` — inchangé (le formulaire affiche déjà tous les champs via `form_widget`) :

```twig
{% extends "base_admin.html.twig" %}
{% block title %}Nouveau membre{% endblock %}
{% block body %}
  <h1>Nouveau membre</h1>
  {{ form_start(form) }}
  {{ form_widget(form) }}
  <p><button type="submit">Créer</button></p>
  {{ form_end(form) }}
  <a href="{{ path("admin_member_list") }}" class="btn">← Retour</a>
{% endblock %}
```

`templates/admin/member/edit.html.twig` — inchangé également :

```twig
{% extends "base_admin.html.twig" %}
{% block title %}Éditer membre{% endblock %}
{% block body %}
  <h1>Éditer {{ member.firstName }} {{ member.lastName }}</h1>
  {{ form_start(form) }}
  {{ form_widget(form) }}
  <p><button type="submit">Enregistrer</button></p>
  {{ form_end(form) }}
  <br>
  <a href="{{ path("admin_member_list") }}" class="btn">← Retour</a>
{% endblock %}
```

- [ ] **Step 10 : Vérifier que tous les tests passent**

```bash
docker compose exec php php bin/phpunit --testdox
```

Expected : tous passent

- [ ] **Step 11 : Commiter**

```bash
git add src/Member/Infrastructure/Http/Admin/MemberController.php \
        src/Member/Infrastructure/Http/Admin/Form/MemberType.php \
        templates/admin/member/list.html.twig \
        templates/admin/member/show.html.twig \
        templates/admin/member/new.html.twig \
        templates/admin/member/edit.html.twig
git commit -m "feat(member): UI liste/show/new/edit avec cotisations + bouton saison + export"
```

---

### Task 10 : Refonte de l'import CSV

**Files:**
- Modify: `src/Member/Infrastructure/Http/Admin/MemberController.php` — réécrire l'action `import`

Le nouvel import accepte des en-têtes en français, gère les mises à jour (matching par `id` ou par `Nom`+`Téléphone`), et crée/met à jour la souscription saison courante si `Type de cotisation` est présent.

- [ ] **Step 1 : Réécrire l'action import dans MemberController**

Remplacer l'action `import` :

```php
#[Route('/import', name: 'admin_member_import', methods: ['GET', 'POST'])]
public function import(
    Request $r,
    MemberRepository $repo,
    CreateMemberHandler $createHandler,
    UpdateMemberHandler $updateHandler,
    CreateMemberSubscriptionHandler $createSubHandler,
    UpdateMemberSubscriptionHandler $updateSubHandler,
    MemberSubscriptionRepository $subRepo,
    SeasonHelper $seasonHelper,
): Response {
    if ($r->getMethod() === 'GET') {
        return $this->render('admin/member/import.html.twig');
    }

    $file = $r->files->get('csv');
    if (!$file) {
        $this->addFlash('error', 'Aucun fichier sélectionné.');
        return $this->redirectToRoute('admin_member_import');
    }

    $handle = fopen($file->getPathname(), 'r');
    $rawHeaders = fgetcsv($handle, 0, ';');
    if ($rawHeaders === false) {
        $this->addFlash('error', 'Fichier CSV vide.');
        fclose($handle);
        return $this->redirectToRoute('admin_member_import');
    }
    $headers = array_map('trim', $rawHeaders);

    $col = fn(string $name): int|false => array_search($name, $headers);
    $idxId     = $col('id');
    $idxNom    = $col('Nom');
    $idxPrenom = $col('Prénom');
    $idxTel    = $col('Téléphone');
    $idxEmail  = $col('Email');
    $idxBirth  = $col('Date de naissance');
    $idxType   = $col('Type de cotisation');
    $idxStatus = $col('Statut paiement');

    if ($idxNom === false || $idxPrenom === false || $idxTel === false) {
        $this->addFlash('error', 'Colonnes obligatoires manquantes : "Nom", "Prénom", "Téléphone".');
        fclose($handle);
        return $this->redirectToRoute('admin_member_import');
    }

    $get = fn(array $row, int|false $idx): string => $idx !== false ? trim($row[$idx] ?? '') : '';

    $imported = 0;
    $updated = 0;
    $errors = [];
    $line = 0;
    $season = $seasonHelper->currentSeason();

    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $line++;
        $csvId    = $get($row, $idxId);
        $nom      = $get($row, $idxNom);
        $prenom   = $get($row, $idxPrenom);
        $tel      = $get($row, $idxTel);
        $email    = $get($row, $idxEmail);
        $birth    = $get($row, $idxBirth);
        $typeStr  = $get($row, $idxType);
        $statStr  = $get($row, $idxStatus);

        // Résoudre le membre
        $member = null;
        try {
            if ($csvId !== '') {
                $member = $repo->get(Uuid::fromString($csvId));
            }
            if ($member === null && $nom !== '' && $tel !== '') {
                $member = $repo->findByLastNameAndPhone($nom, PhoneNumber::fromString($tel));
            }
        } catch (\Throwable $e) {
            $errors[] = "Ligne $line : identifiant ou téléphone invalide — " . $e->getMessage();
            continue;
        }

        // Résoudre le type de cotisation
        $membershipType = null;
        if ($typeStr !== '') {
            try {
                $membershipType = MembershipType::fromLabel($typeStr);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Ligne $line : " . $e->getMessage();
                continue;
            }
        }

        // Résoudre le statut de paiement
        $subscriptionStatus = SubscriptionStatus::PENDING;
        if ($statStr !== '') {
            try {
                $subscriptionStatus = SubscriptionStatus::fromLabel($statStr);
            } catch (\InvalidArgumentException $e) {
                $errors[] = "Ligne $line : " . $e->getMessage();
                continue;
            }
        }

        try {
            if ($member === null) {
                // Création
                if ($nom === '' || $prenom === '' || $tel === '') {
                    $errors[] = "Ligne $line : membre non trouvé et colonnes insuffisantes pour créer (Nom, Prénom, Téléphone requis).";
                    continue;
                }
                $memberId = $createHandler(new CreateMemberCommand($nom, $prenom, $tel, $email ?: null, $birth ?: null));
                if ($membershipType !== null) {
                    $createSubHandler(new CreateMemberSubscriptionCommand((string)$memberId, $season, $membershipType, $subscriptionStatus));
                }
                $imported++;
            } else {
                // Mise à jour
                $updateHandler(new UpdateMemberCommand(
                    (string)$member->id(),
                    $nom ?: $member->lastName(),
                    $prenom ?: $member->firstName(),
                    $tel ?: (string)$member->phone(),
                    $email !== '' ? $email : ($member->email() ? (string)$member->email() : null),
                    $birth !== '' ? $birth : $member->birthDate()?->format('d/m/Y'),
                ));
                if ($membershipType !== null) {
                    $currentSub = $subRepo->findByMemberAndSeason((string)$member->id(), $season);
                    if ($currentSub !== null) {
                        $updateSubHandler(new UpdateMemberSubscriptionCommand((string)$currentSub->id(), $membershipType, $subscriptionStatus));
                    } else {
                        $createSubHandler(new CreateMemberSubscriptionCommand((string)$member->id(), $season, $membershipType, $subscriptionStatus));
                    }
                }
                $updated++;
            }
        } catch (\Throwable $e) {
            $errors[] = "Ligne $line ($prenom $nom) : " . $e->getMessage();
        }
    }
    fclose($handle);

    if ($imported > 0) $this->addFlash('success', "$imported membre(s) créé(s).");
    if ($updated > 0)  $this->addFlash('success', "$updated membre(s) mis à jour.");
    foreach ($errors as $err) $this->addFlash('error', $err);

    return $this->redirectToRoute('admin_member_list');
}
```

Ajouter les imports manquants en tête du fichier contrôleur :

```php
use App\Member\Domain\MembershipType;
use App\Shared\Domain\ValueObject\PhoneNumber;
```

- [ ] **Step 2 : Vérifier que tous les tests passent**

```bash
docker compose exec php php bin/phpunit --testdox
```

Expected : tous passent

- [ ] **Step 3 : Commiter**

```bash
git add src/Member/Infrastructure/Http/Admin/MemberController.php
git commit -m "feat(member): refonte import CSV avec en-têtes français + mises à jour"
```
