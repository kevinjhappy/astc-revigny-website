# ASTC Revigny — Init Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bootstrap a Symfony 7 / PHP 8.3 / MySQL 8 website for ASTC Revigny tennis club, with a DDD bounded-context architecture, a public one-page site (tournament registration) and an authenticated back office (tournaments, members, registrations CRUD).

**Architecture:** Four DDD bounded contexts (`Tournament`, `Member`, `Registration`, `Security`) plus a `Shared` kernel for common Value Objects. Contexts communicate through Symfony's `EventDispatcher` and reference each other by UUID, never by hydrated Doctrine objects. Light CQRS: `Application/` layer holds Commands/Queries + Handlers; `Infrastructure/` holds Doctrine repositories, HTTP controllers, and Twig templates.

**Tech Stack:** PHP 8.3, Symfony 7, Doctrine ORM, MySQL 8, Twig, Symfony Forms + Validator + Security, GSAP, Swiper.js, AOS, Docker Compose (php-fpm, nginx, mysql, mailpit).

---

## File Structure

### Docker / infra
- `docker-compose.yml` — 4 services
- `docker/php/Dockerfile` — PHP 8.3-fpm + extensions
- `docker/php/php.ini` — tuning
- `docker/nginx/default.conf` — reverse proxy to php-fpm
- `.env` / `.env.local` — Symfony env
- `Makefile` — shortcut commands

### Symfony config
- `composer.json`, `symfony.lock`
- `config/packages/doctrine.yaml`, `security.yaml`, `twig.yaml`, `framework.yaml`, `validator.yaml`
- `config/routes.yaml` (imports attribute routes)
- `config/services.yaml`

### Shared kernel
- `src/Shared/Domain/ValueObject/Uuid.php`
- `src/Shared/Domain/ValueObject/PhoneNumber.php`
- `src/Shared/Domain/ValueObject/Email.php`
- `src/Shared/Infrastructure/Doctrine/Type/UuidType.php`
- `src/Shared/Infrastructure/Doctrine/Type/PhoneNumberType.php`
- `src/Shared/Infrastructure/Doctrine/Type/EmailType.php`
- `tests/Shared/Domain/ValueObject/{UuidTest,PhoneNumberTest,EmailTest}.php`

### Member context
- `src/Member/Domain/Member.php`
- `src/Member/Domain/MemberRepository.php` (interface)
- `src/Member/Application/Command/{CreateMember,UpdateMember,DeleteMember}Command.php`
- `src/Member/Application/Command/{CreateMember,UpdateMember,DeleteMember}Handler.php`
- `src/Member/Application/Query/{FindMember,SearchMembers,MatchMember}Query.php` + Handlers
- `src/Member/Infrastructure/Doctrine/DoctrineMemberRepository.php`
- `src/Member/Infrastructure/Doctrine/Mapping/Member.orm.xml` (or attributes)
- `src/Member/Infrastructure/Http/Admin/MemberController.php`
- `src/Member/Infrastructure/Http/Admin/Form/MemberType.php`
- `templates/admin/member/{list,new,edit}.html.twig`
- `tests/Member/...`

### Tournament context
- `src/Tournament/Domain/Tournament.php`
- `src/Tournament/Domain/TournamentStatus.php` (enum)
- `src/Tournament/Domain/TournamentType.php` (enum)
- `src/Tournament/Domain/TournamentRepository.php`
- `src/Tournament/Domain/Event/TournamentPublished.php`
- `src/Tournament/Application/Command/{CreateTournament,UpdateTournament,PublishTournament,CloseTournament}Command.php` + Handlers
- `src/Tournament/Application/Query/{ListPublishedTournaments,FindTournament,ListAllTournaments}Query.php` + Handlers
- `src/Tournament/Infrastructure/Doctrine/DoctrineTournamentRepository.php`
- `src/Tournament/Infrastructure/Http/Admin/TournamentController.php`
- `src/Tournament/Infrastructure/Http/Admin/Form/TournamentType.php`
- `templates/admin/tournament/{list,new,edit,detail}.html.twig`
- `tests/Tournament/...`

### Registration context
- `src/Registration/Domain/Registration.php`
- `src/Registration/Domain/RegistrationStatus.php` (enum)
- `src/Registration/Domain/RegistrationRepository.php`
- `src/Registration/Domain/Event/RegistrationConfirmed.php`, `RegistrationCancelled.php`
- `src/Registration/Domain/Service/WaitingListPromoter.php`
- `src/Registration/Application/Command/{Register,ConfirmRegistration,CancelRegistration,DeleteRegistration}Command.php` + Handlers
- `src/Registration/Application/Query/{ListRegistrations,CountConfirmed}Query.php` + Handlers
- `src/Registration/Infrastructure/Doctrine/DoctrineRegistrationRepository.php`
- `src/Registration/Infrastructure/Http/Public/RegistrationApiController.php`
- `src/Registration/Infrastructure/Http/Admin/RegistrationController.php`
- `templates/admin/registration/list.html.twig`
- `tests/Registration/...`

### Security context
- `src/Security/Domain/AdminUser.php`
- `src/Security/Domain/AdminUserRepository.php`
- `src/Security/Infrastructure/Doctrine/DoctrineAdminUserRepository.php`
- `src/Security/Infrastructure/Http/LoginController.php`
- `src/Security/Infrastructure/Console/CreateAdminCommand.php`
- `templates/admin/security/login.html.twig`
- `tests/Security/...`

### Public frontend
- `src/Public/Infrastructure/Http/HomeController.php`
- `templates/public/home.html.twig`
- `templates/public/_partials/{nav,hero,club,tournaments,gallery,contact,footer,modal_registration}.html.twig`
- `assets/styles/app.css`
- `assets/app.js`
- `assets/js/{hero-parallax,cards-hover,gallery-swiper,registration-modal}.js`
- `public/images/banniere.jpg` (already present)

### Base templates
- `templates/base_public.html.twig`
- `templates/base_admin.html.twig`

---

## Testing approach

Every domain rule (VOs, Registration lifecycle, waiting-list promotion, member-match) is driven with PHPUnit unit tests. Application handlers are tested with in-memory repositories. Controllers are tested with Symfony's `WebTestCase`. TDD: write test → run it red → implement → run it green → commit.

---

## Phase 0 — Docker + Symfony bootstrap

### Task 0.1 — Create Docker Compose stack

- [ ] **0.1.1** Create `docker/php/Dockerfile` with:

```dockerfile
FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    git unzip icu-dev libzip-dev oniguruma-dev \
    mysql-client autoconf g++ make linux-headers

RUN docker-php-ext-install intl pdo pdo_mysql opcache zip \
 && pecl install apcu && docker-php-ext-enable apcu

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/app
```

- [ ] **0.1.2** Create `docker/php/php.ini`:

```ini
memory_limit = 512M
upload_max_filesize = 20M
post_max_size = 20M
date.timezone = Europe/Paris
```

- [ ] **0.1.3** Create `docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/app/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ { return 404; }

    error_log /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}
```

- [ ] **0.1.4** Create `docker-compose.yml`:

```yaml
services:
  php:
    build: ./docker/php
    volumes:
      - ./:/var/www/app
      - ./docker/php/php.ini:/usr/local/etc/php/conf.d/zz-custom.ini
    depends_on:
      - mysql
      - mailpit
    environment:
      DATABASE_URL: "mysql://astc:astc@mysql:3306/astc?serverVersion=8.0"
      MAILER_DSN: "smtp://mailpit:1025"

  nginx:
    image: nginx:1.27-alpine
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/app:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on:
      - php

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: astc
      MYSQL_USER: astc
      MYSQL_PASSWORD: astc
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"

  mailpit:
    image: axllent/mailpit:latest
    ports:
      - "8025:8025"
      - "1025:1025"

volumes:
  mysql_data:
```

- [ ] **0.1.5** Create `Makefile`:

```makefile
.PHONY: up down sh console test

up:      ; docker compose up -d
down:    ; docker compose down
sh:      ; docker compose exec php sh
console: ; docker compose exec php php bin/console $(c)
test:    ; docker compose exec php vendor/bin/phpunit
install: ; docker compose exec php composer install
```

- [ ] **0.1.6** Run `docker compose build` — expect successful build of the `php` image.
- [ ] **0.1.7** Run `docker compose up -d` then `docker compose ps` — expect all four services `running`/`healthy`.
- [ ] **0.1.8** Commit: `chore: add docker compose stack (php, nginx, mysql, mailpit)`.

### Task 0.2 — Install Symfony skeleton

- [ ] **0.2.1** Inside the `php` container, create Symfony in a temp dir then move files. Run:
```
docker compose exec php sh -c "composer create-project symfony/skeleton /tmp/skel '7.*' && cp -a /tmp/skel/. /var/www/app/ && rm -rf /tmp/skel"
```
- [ ] **0.2.2** Install required bundles:
```
docker compose exec php composer require \
  symfony/orm-pack symfony/maker-bundle --dev \
  symfony/security-bundle symfony/form symfony/validator \
  symfony/twig-bundle symfony/asset symfony/mailer \
  twig/extra-bundle symfony/uid ramsey/uuid giggsey/libphonenumber-for-php \
  symfony/webpack-encore-bundle
docker compose exec php composer require --dev \
  phpunit/phpunit symfony/phpunit-bridge symfony/browser-kit symfony/css-selector \
  dama/doctrine-test-bundle
```
- [ ] **0.2.3** Verify Symfony boots: `curl -s -o /dev/null -w "%{http_code}" http://localhost:8080` — expect `200` (welcome page).
- [ ] **0.2.4** Set `.env.local`:
```
APP_ENV=dev
APP_SECRET=changeme-local
DATABASE_URL="mysql://astc:astc@mysql:3306/astc?serverVersion=8.0"
MAILER_DSN=smtp://mailpit:1025
```
- [ ] **0.2.5** Create DB: `make console c="doctrine:database:create --if-not-exists"` — expect `Created database "astc"` or already-exists.
- [ ] **0.2.6** Commit: `chore: install symfony 7 skeleton + core bundles`.

### Task 0.3 — Configure DDD namespace layout

- [ ] **0.3.1** Edit `composer.json` autoload to add bounded contexts. Replace the `autoload` block:
```json
"autoload": {
    "psr-4": {
        "App\\": "src/",
        "App\\Tournament\\": "src/Tournament/",
        "App\\Member\\": "src/Member/",
        "App\\Registration\\": "src/Registration/",
        "App\\Security\\": "src/Security/",
        "App\\Shared\\": "src/Shared/",
        "App\\Public\\": "src/Public/"
    }
}
```
- [ ] **0.3.2** Create the directory skeleton:
```
docker compose exec php sh -c 'mkdir -p src/{Tournament,Member,Registration,Security,Shared,Public}/{Domain,Application,Infrastructure}'
```
- [ ] **0.3.3** Run `docker compose exec php composer dump-autoload` — expect no errors.
- [ ] **0.3.4** Edit `config/services.yaml` so each context's services auto-register:
```yaml
services:
  _defaults:
    autowire: true
    autoconfigure: true

  App\:
    resource: '../src/'
    exclude: '../src/**/Domain/{Event,ValueObject}'
```
- [ ] **0.3.5** Run `make console c="cache:clear"` — expect success.
- [ ] **0.3.6** Commit: `chore: set up DDD bounded-context namespaces`.

---

## Phase 1 — Shared kernel (Value Objects)

### Task 1.1 — Uuid VO (TDD)

- [ ] **1.1.1** Create `tests/Shared/Domain/ValueObject/UuidTest.php`:
```php
<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class UuidTest extends TestCase
{
    public function test_generates_valid_v4(): void
    {
        $u = Uuid::generate();
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', (string)$u);
    }

    public function test_from_string_round_trip(): void
    {
        $s = '11111111-1111-4111-8111-111111111111';
        self::assertSame($s, (string)Uuid::fromString($s));
    }

    public function test_rejects_invalid_string(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Uuid::fromString('not-a-uuid');
    }

    public function test_equality(): void
    {
        $s = '11111111-1111-4111-8111-111111111111';
        self::assertTrue(Uuid::fromString($s)->equals(Uuid::fromString($s)));
    }
}
```
- [ ] **1.1.2** Run `make test` — expect failure (class missing).
- [ ] **1.1.3** Create `src/Shared/Domain/ValueObject/Uuid.php`:
```php
<?php
namespace App\Shared\Domain\ValueObject;

use Ramsey\Uuid\Uuid as RamseyUuid;

final class Uuid implements \Stringable
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self(RamseyUuid::uuid4()->toString());
    }

    public static function fromString(string $value): self
    {
        if (!RamseyUuid::isValid($value)) {
            throw new \InvalidArgumentException("Invalid UUID: $value");
        }
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```
- [ ] **1.1.4** Run `make test` — expect green for UuidTest.
- [ ] **1.1.5** Commit: `feat(shared): add Uuid value object`.

### Task 1.2 — Email VO (TDD)

- [ ] **1.2.1** Create `tests/Shared/Domain/ValueObject/EmailTest.php`:
```php
<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function test_accepts_valid_email(): void
    {
        self::assertSame('a@b.fr', (string)Email::fromString('a@b.fr'));
    }

    public function test_normalizes_to_lowercase(): void
    {
        self::assertSame('a@b.fr', (string)Email::fromString('A@B.FR'));
    }

    public function test_rejects_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Email::fromString('not-an-email');
    }
}
```
- [ ] **1.2.2** Run tests — expect red.
- [ ] **1.2.3** Create `src/Shared/Domain/ValueObject/Email.php`:
```php
<?php
namespace App\Shared\Domain\ValueObject;

final class Email implements \Stringable
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        $v = strtolower(trim($value));
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email: $value");
        }
        return new self($v);
    }

    public function __toString(): string { return $this->value; }
}
```
- [ ] **1.2.4** Run tests — expect green.
- [ ] **1.2.5** Commit: `feat(shared): add Email value object`.

### Task 1.3 — PhoneNumber VO (TDD)

- [ ] **1.3.1** Create `tests/Shared/Domain/ValueObject/PhoneNumberTest.php`:
```php
<?php
namespace App\Tests\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function test_parses_french_local(): void
    {
        self::assertSame('+33612345678', (string)PhoneNumber::fromString('0612345678'));
    }

    public function test_parses_international(): void
    {
        self::assertSame('+33612345678', (string)PhoneNumber::fromString('+33 6 12 34 56 78'));
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PhoneNumber::fromString('abc');
    }
}
```
- [ ] **1.3.2** Run tests — expect red.
- [ ] **1.3.3** Create `src/Shared/Domain/ValueObject/PhoneNumber.php`:
```php
<?php
namespace App\Shared\Domain\ValueObject;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final class PhoneNumber implements \Stringable
{
    private function __construct(private readonly string $e164) {}

    public static function fromString(string $value): self
    {
        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($value, 'FR');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Invalid phone: $value", 0, $e);
        }
        if (!$util->isValidNumber($parsed)) {
            throw new \InvalidArgumentException("Invalid phone: $value");
        }
        return new self($util->format($parsed, PhoneNumberFormat::E164));
    }

    public function equals(self $other): bool { return $this->e164 === $other->e164; }
    public function __toString(): string { return $this->e164; }
}
```
- [ ] **1.3.4** Run tests — expect green.
- [ ] **1.3.5** Commit: `feat(shared): add PhoneNumber value object`.

### Task 1.4 — Doctrine custom types for VOs

- [ ] **1.4.1** Create `src/Shared/Infrastructure/Doctrine/Type/UuidType.php`:
```php
<?php
namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class UuidType extends Type
{
    public const NAME = 'uuid';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 36, 'fixed' => true]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Uuid
    {
        return $value === null ? null : Uuid::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : (string)$value;
    }

    public function getName(): string { return self::NAME; }
}
```
- [ ] **1.4.2** Create `src/Shared/Infrastructure/Doctrine/Type/EmailType.php`:
```php
<?php
namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class EmailType extends Type
{
    public const NAME = 'email';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 180]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        return $value === null ? null : Email::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : (string)$value;
    }

    public function getName(): string { return self::NAME; }
}
```
- [ ] **1.4.3** Create `src/Shared/Infrastructure/Doctrine/Type/PhoneNumberType.php`:
```php
<?php
namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\ValueObject\PhoneNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

final class PhoneNumberType extends Type
{
    public const NAME = 'phone_number';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 20]);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?PhoneNumber
    {
        return $value === null ? null : PhoneNumber::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value === null ? null : (string)$value;
    }

    public function getName(): string { return self::NAME; }
}
```
- [ ] **1.4.4** Register the types in `config/packages/doctrine.yaml` under `doctrine.dbal`:
```yaml
doctrine:
  dbal:
    url: '%env(resolve:DATABASE_URL)%'
    types:
      uuid: App\Shared\Infrastructure\Doctrine\Type\UuidType
      email: App\Shared\Infrastructure\Doctrine\Type\EmailType
      phone_number: App\Shared\Infrastructure\Doctrine\Type\PhoneNumberType
  orm:
    auto_generate_proxy_classes: true
    naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
    auto_mapping: true
    mappings:
      App:
        type: attribute
        is_bundle: false
        dir: '%kernel.project_dir%/src'
        prefix: 'App\'
        alias: App
```
- [ ] **1.4.5** Run `make console c="doctrine:schema:validate --skip-sync"` — expect no type errors.
- [ ] **1.4.6** Commit: `feat(shared): register Doctrine types for Uuid/Email/PhoneNumber`.

---

## Phase 2 — Member context

### Task 2.1 — Member entity (TDD)

- [ ] **2.1.1** Create `tests/Member/Domain/MemberTest.php`:
```php
<?php
namespace App\Tests\Member\Domain;

use App\Member\Domain\Member;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class MemberTest extends TestCase
{
    public function test_creation_with_required_fields(): void
    {
        $m = Member::create(Uuid::generate(), 'Dupont', 'Jean', PhoneNumber::fromString('0612345678'), null);
        self::assertSame('Dupont', $m->lastName());
        self::assertSame('Jean', $m->firstName());
        self::assertNull($m->email());
    }

    public function test_creation_with_email(): void
    {
        $m = Member::create(Uuid::generate(), 'D', 'J',
            PhoneNumber::fromString('0612345678'), Email::fromString('a@b.fr'));
        self::assertSame('a@b.fr', (string)$m->email());
    }

    public function test_update(): void
    {
        $m = Member::create(Uuid::generate(), 'A', 'B', PhoneNumber::fromString('0612345678'), null);
        $m->update('New', 'Name', PhoneNumber::fromString('0798765432'), Email::fromString('x@y.fr'));
        self::assertSame('New', $m->lastName());
        self::assertSame('+33798765432', (string)$m->phone());
    }
}
```
- [ ] **2.1.2** Run test — expect red.
- [ ] **2.1.3** Create `src/Member/Domain/Member.php`:
```php
<?php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'members')]
class Member
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'phone_number')]
    private PhoneNumber $phone;

    #[ORM\Column(type: 'email', nullable: true)]
    private ?Email $email;

    private function __construct(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email)
    {
        $this->id = $id;
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
    }

    public static function create(Uuid $id, string $lastName, string $firstName, PhoneNumber $phone, ?Email $email): self
    {
        return new self($id, $lastName, $firstName, $phone, $email);
    }

    public function update(string $lastName, string $firstName, PhoneNumber $phone, ?Email $email): void
    {
        $this->lastName = $lastName;
        $this->firstName = $firstName;
        $this->phone = $phone;
        $this->email = $email;
    }

    public function id(): Uuid { return $this->id; }
    public function lastName(): string { return $this->lastName; }
    public function firstName(): string { return $this->firstName; }
    public function phone(): PhoneNumber { return $this->phone; }
    public function email(): ?Email { return $this->email; }
}
```
- [ ] **2.1.4** Run tests — expect green.
- [ ] **2.1.5** Commit: `feat(member): add Member domain entity`.

### Task 2.2 — MemberRepository interface + Doctrine impl

- [ ] **2.2.1** Create `src/Member/Domain/MemberRepository.php`:
```php
<?php
namespace App\Member\Domain;

use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

interface MemberRepository
{
    public function save(Member $member): void;
    public function remove(Member $member): void;
    public function get(Uuid $id): ?Member;
    /** @return Member[] */
    public function search(?string $query): array;
    public function findByLastNameAndPhone(string $lastName, PhoneNumber $phone): ?Member;
}
```
- [ ] **2.2.2** Create `src/Member/Infrastructure/Doctrine/DoctrineMemberRepository.php`:
```php
<?php
namespace App\Member\Infrastructure\Doctrine;

use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineMemberRepository implements MemberRepository
{
    public function __construct(private EntityManagerInterface $em) {}

    public function save(Member $m): void { $this->em->persist($m); $this->em->flush(); }
    public function remove(Member $m): void { $this->em->remove($m); $this->em->flush(); }

    public function get(Uuid $id): ?Member
    {
        return $this->em->getRepository(Member::class)->findOneBy(['id' => (string)$id]);
    }

    public function search(?string $q): array
    {
        $qb = $this->em->createQueryBuilder()->select('m')->from(Member::class, 'm')->orderBy('m.lastName', 'ASC');
        if ($q) {
            $qb->where('m.lastName LIKE :q OR m.firstName LIKE :q OR m.phone LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        return $qb->getQuery()->getResult();
    }

    public function findByLastNameAndPhone(string $lastName, PhoneNumber $phone): ?Member
    {
        return $this->em->getRepository(Member::class)->findOneBy([
            'lastName' => $lastName,
            'phone' => (string)$phone,
        ]);
    }
}
```
- [ ] **2.2.3** Generate migration: `make console c="doctrine:migrations:diff"` — expect migration file created under `migrations/`.
- [ ] **2.2.4** Run migration: `make console c="doctrine:migrations:migrate --no-interaction"` — expect `members` table created.
- [ ] **2.2.5** Commit: `feat(member): add MemberRepository + Doctrine implementation`.

### Task 2.3 — Member Application layer (Commands/Queries)

- [ ] **2.3.1** Create `src/Member/Application/Command/CreateMemberCommand.php`:
```php
<?php
namespace App\Member\Application\Command;

final class CreateMemberCommand
{
    public function __construct(
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}
```
- [ ] **2.3.2** Create `src/Member/Application/Command/CreateMemberHandler.php`:
```php
<?php
namespace App\Member\Application\Command;

use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(CreateMemberCommand $c): Uuid
    {
        $id = Uuid::generate();
        $m = Member::create(
            $id, $c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone),
            $c->email ? Email::fromString($c->email) : null,
        );
        $this->repo->save($m);
        return $id;
    }
}
```
- [ ] **2.3.3** Create analogous `UpdateMemberCommand`/Handler (takes `string $id`, same fields; looks up member, calls `update()`, saves) and `DeleteMemberCommand`/Handler (takes `string $id`, removes).

`src/Member/Application/Command/UpdateMemberCommand.php`:
```php
<?php
namespace App\Member\Application\Command;

final class UpdateMemberCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}
```
`src/Member/Application/Command/UpdateMemberHandler.php`:
```php
<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(UpdateMemberCommand $c): void
    {
        $m = $this->repo->get(Uuid::fromString($c->id))
            ?? throw new \DomainException('Member not found');
        $m->update($c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone),
            $c->email ? Email::fromString($c->email) : null);
        $this->repo->save($m);
    }
}
```
`src/Member/Application/Command/DeleteMemberCommand.php` + `DeleteMemberHandler.php`:
```php
<?php
namespace App\Member\Application\Command;
final class DeleteMemberCommand { public function __construct(public readonly string $id) {} }
```
```php
<?php
namespace App\Member\Application\Command;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteMemberHandler
{
    public function __construct(private MemberRepository $repo) {}
    public function __invoke(DeleteMemberCommand $c): void
    {
        $m = $this->repo->get(Uuid::fromString($c->id));
        if ($m) $this->repo->remove($m);
    }
}
```

- [ ] **2.3.4** Create Query `MatchMemberQuery` + Handler (used by Registration for MEMBERS_ONLY check).

`src/Member/Application/Query/MatchMemberQuery.php`:
```php
<?php
namespace App\Member\Application\Query;

final class MatchMemberQuery
{
    public function __construct(public readonly string $lastName, public readonly string $phone) {}
}
```
`src/Member/Application/Query/MatchMemberHandler.php`:
```php
<?php
namespace App\Member\Application\Query;

use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;

final class MatchMemberHandler
{
    public function __construct(private MemberRepository $repo) {}

    public function __invoke(MatchMemberQuery $q): bool
    {
        return $this->repo->findByLastNameAndPhone($q->lastName, PhoneNumber::fromString($q->phone)) !== null;
    }
}
```
- [ ] **2.3.5** Write `tests/Member/Application/CreateMemberHandlerTest.php` using an in-memory `MemberRepository` fake:
```php
<?php
namespace App\Tests\Member\Application;

use App\Member\Application\Command\CreateMemberCommand;
use App\Member\Application\Command\CreateMemberHandler;
use App\Member\Domain\Member;
use App\Member\Domain\MemberRepository;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CreateMemberHandlerTest extends TestCase
{
    public function test_creates_and_persists(): void
    {
        $repo = new class implements MemberRepository {
            public array $store = [];
            public function save(Member $m): void { $this->store[(string)$m->id()] = $m; }
            public function remove(Member $m): void { unset($this->store[(string)$m->id()]); }
            public function get(Uuid $id): ?Member { return $this->store[(string)$id] ?? null; }
            public function search(?string $q): array { return array_values($this->store); }
            public function findByLastNameAndPhone(string $l, PhoneNumber $p): ?Member { return null; }
        };
        $id = (new CreateMemberHandler($repo))(new CreateMemberCommand('Dupont','Jean','0612345678',null));
        self::assertCount(1, $repo->store);
        self::assertSame('Dupont', $repo->get($id)->lastName());
    }
}
```
- [ ] **2.3.6** Run tests — expect green.
- [ ] **2.3.7** Commit: `feat(member): add application commands + queries`.

### Task 2.4 — Admin Member controller (CRUD) + Twig

- [ ] **2.4.1** Create `templates/base_admin.html.twig`:
```twig
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>{% block title %}Admin ASTC{% endblock %}</title>
  <link rel="stylesheet" href="{{ asset('build/admin.css') }}">
</head>
<body class="admin">
  <header class="admin-nav">
    <a href="{{ path('admin_dashboard') }}">Dashboard</a>
    <a href="{{ path('admin_tournament_list') }}">Tournois</a>
    <a href="{{ path('admin_member_list') }}">Membres</a>
    <a href="{{ path('admin_registration_list') }}">Inscriptions</a>
    <a href="{{ path('app_logout') }}">Déconnexion</a>
  </header>
  <main class="admin-main">{% block body %}{% endblock %}</main>
</body>
</html>
```
- [ ] **2.4.2** Create `src/Member/Infrastructure/Http/Admin/Form/MemberType.php`:
```php
<?php
namespace App\Member\Infrastructure\Http\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add('lastName', TextType::class, ['constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('firstName', TextType::class, ['constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)]])
          ->add('phone', TextType::class, ['constraints' => [new Assert\NotBlank()]])
          ->add('email', EmailType::class, ['required' => false]);
    }
}
```
- [ ] **2.4.3** Create `src/Member/Infrastructure/Http/Admin/MemberController.php`:
```php
<?php
namespace App\Member\Infrastructure\Http\Admin;

use App\Member\Application\Command\{CreateMemberCommand, CreateMemberHandler, DeleteMemberCommand, DeleteMemberHandler, UpdateMemberCommand, UpdateMemberHandler};
use App\Member\Domain\MemberRepository;
use App\Member\Infrastructure\Http\Admin\Form\MemberType;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/members')]
#[IsGranted('ROLE_ADMIN')]
final class MemberController extends AbstractController
{
    #[Route('', name: 'admin_member_list', methods: ['GET'])]
    public function list(Request $r, MemberRepository $repo): Response
    {
        return $this->render('admin/member/list.html.twig', [
            'members' => $repo->search($r->query->get('q')),
            'q' => $r->query->get('q', ''),
        ]);
    }

    #[Route('/new', name: 'admin_member_new', methods: ['GET','POST'])]
    public function new(Request $r, CreateMemberHandler $h): Response
    {
        $form = $this->createForm(MemberType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new CreateMemberCommand($d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null));
            $this->addFlash('success', 'Membre créé');
            return $this->redirectToRoute('admin_member_list');
        }
        return $this->render('admin/member/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/edit', name: 'admin_member_edit', methods: ['GET','POST'])]
    public function edit(string $id, Request $r, MemberRepository $repo, UpdateMemberHandler $h): Response
    {
        $m = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(MemberType::class, [
            'lastName' => $m->lastName(), 'firstName' => $m->firstName(),
            'phone' => (string)$m->phone(), 'email' => $m->email() ? (string)$m->email() : null,
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdateMemberCommand($id, $d['lastName'], $d['firstName'], $d['phone'], $d['email'] ?? null));
            $this->addFlash('success', 'Membre mis à jour');
            return $this->redirectToRoute('admin_member_list');
        }
        return $this->render('admin/member/edit.html.twig', ['form' => $form, 'member' => $m]);
    }

    #[Route('/{id}', name: 'admin_member_delete', methods: ['POST'])]
    public function delete(string $id, Request $r, DeleteMemberHandler $h): Response
    {
        if ($this->isCsrfTokenValid('del'.$id, $r->request->get('_token'))) {
            $h(new DeleteMemberCommand($id));
            $this->addFlash('success', 'Membre supprimé');
        }
        return $this->redirectToRoute('admin_member_list');
    }
}
```
- [ ] **2.4.4** Create `templates/admin/member/list.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block title %}Membres{% endblock %}
{% block body %}
  <h1>Membres</h1>
  <form method="get"><input name="q" value="{{ q }}" placeholder="Rechercher…"> <button>OK</button></form>
  <a class="btn" href="{{ path('admin_member_new') }}">+ Nouveau</a>
  <table>
    <thead><tr><th>Nom</th><th>Prénom</th><th>Téléphone</th><th>Email</th><th></th></tr></thead>
    <tbody>
    {% for m in members %}
      <tr>
        <td>{{ m.lastName }}</td><td>{{ m.firstName }}</td>
        <td>{{ m.phone }}</td><td>{{ m.email }}</td>
        <td>
          <a href="{{ path('admin_member_edit', {id: m.id}) }}">Éditer</a>
          <form method="post" action="{{ path('admin_member_delete', {id: m.id}) }}" style="display:inline" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="_token" value="{{ csrf_token('del' ~ m.id) }}">
            <button>Supprimer</button>
          </form>
        </td>
      </tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```
- [ ] **2.4.5** Create `templates/admin/member/new.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>Nouveau membre</h1>
  {{ form_start(form) }}{{ form_widget(form) }}<button>Créer</button>{{ form_end(form) }}
{% endblock %}
```
- [ ] **2.4.6** Create `templates/admin/member/edit.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>Éditer {{ member.firstName }} {{ member.lastName }}</h1>
  {{ form_start(form) }}{{ form_widget(form) }}<button>Enregistrer</button>{{ form_end(form) }}
{% endblock %}
```
- [ ] **2.4.7** (Temporary) Comment out `#[IsGranted('ROLE_ADMIN')]` so the feature can be tested before Phase 5 wires security — OR implement after Phase 5. **Decision:** implement after Phase 5. Skip smoke test here; rely on functional test in Phase 8.
- [ ] **2.4.8** Commit: `feat(member): add admin CRUD controller + templates`.

---

## Phase 3 — Tournament context

### Task 3.1 — Tournament entity + enums (TDD)

- [ ] **3.1.1** Create `src/Tournament/Domain/TournamentType.php`:
```php
<?php
namespace App\Tournament\Domain;

enum TournamentType: string
{
    case OPEN = 'OPEN';
    case MEMBERS_ONLY = 'MEMBERS_ONLY';
}
```
- [ ] **3.1.2** Create `src/Tournament/Domain/TournamentStatus.php`:
```php
<?php
namespace App\Tournament\Domain;

enum TournamentStatus: string
{
    case DRAFT = 'DRAFT';
    case PUBLISHED = 'PUBLISHED';
    case CLOSED = 'CLOSED';
}
```
- [ ] **3.1.3** Create `tests/Tournament/Domain/TournamentTest.php`:
```php
<?php
namespace App\Tests\Tournament\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentStatus;
use App\Tournament\Domain\TournamentType;
use PHPUnit\Framework\TestCase;

final class TournamentTest extends TestCase
{
    private function make(): Tournament
    {
        return Tournament::create(Uuid::generate(), 'Open 2026',
            new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-03'),
            TournamentType::OPEN, 32, null);
    }

    public function test_starts_draft(): void
    {
        self::assertSame(TournamentStatus::DRAFT, $this->make()->status());
    }

    public function test_publish(): void
    {
        $t = $this->make(); $t->publish();
        self::assertSame(TournamentStatus::PUBLISHED, $t->status());
    }

    public function test_cannot_publish_if_end_before_start(): void
    {
        $this->expectException(\DomainException::class);
        Tournament::create(Uuid::generate(), 'x',
            new \DateTimeImmutable('2026-06-03'), new \DateTimeImmutable('2026-06-01'),
            TournamentType::OPEN, 10, null);
    }

    public function test_close_from_published(): void
    {
        $t = $this->make(); $t->publish(); $t->close();
        self::assertSame(TournamentStatus::CLOSED, $t->status());
    }

    public function test_cannot_close_draft(): void
    {
        $this->expectException(\DomainException::class);
        $this->make()->close();
    }

    public function test_max_participants_must_be_positive(): void
    {
        $this->expectException(\DomainException::class);
        Tournament::create(Uuid::generate(), 'x',
            new \DateTimeImmutable('2026-06-01'), new \DateTimeImmutable('2026-06-03'),
            TournamentType::OPEN, 0, null);
    }
}
```
- [ ] **3.1.4** Run tests — expect red.
- [ ] **3.1.5** Create `src/Tournament/Domain/Tournament.php`:
```php
<?php
namespace App\Tournament\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tournaments')]
class Tournament
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'string', length: 150)]
    private string $name;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startDate;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endDate;
    #[ORM\Column(type: 'string', enumType: TournamentType::class)]
    private TournamentType $type;
    #[ORM\Column(type: 'integer')]
    private int $maxParticipants;
    #[ORM\Column(type: 'string', enumType: TournamentStatus::class)]
    private TournamentStatus $status;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    private function __construct(Uuid $id, string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc)
    {
        if ($e < $s) throw new \DomainException('endDate before startDate');
        if ($max <= 0) throw new \DomainException('maxParticipants must be > 0');
        $this->id = $id; $this->name = $name;
        $this->startDate = $s; $this->endDate = $e;
        $this->type = $type; $this->maxParticipants = $max;
        $this->status = TournamentStatus::DRAFT; $this->description = $desc;
    }

    public static function create(Uuid $id, string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc): self
    {
        return new self($id, $name, $s, $e, $type, $max, $desc);
    }

    public function update(string $name, \DateTimeImmutable $s, \DateTimeImmutable $e,
        TournamentType $type, int $max, ?string $desc): void
    {
        if ($e < $s) throw new \DomainException('endDate before startDate');
        if ($max <= 0) throw new \DomainException('maxParticipants must be > 0');
        $this->name = $name; $this->startDate = $s; $this->endDate = $e;
        $this->type = $type; $this->maxParticipants = $max; $this->description = $desc;
    }

    public function publish(): void
    {
        if ($this->status !== TournamentStatus::DRAFT) throw new \DomainException('only DRAFT can be published');
        $this->status = TournamentStatus::PUBLISHED;
    }

    public function close(): void
    {
        if ($this->status !== TournamentStatus::PUBLISHED) throw new \DomainException('only PUBLISHED can be closed');
        $this->status = TournamentStatus::CLOSED;
    }

    public function id(): Uuid { return $this->id; }
    public function name(): string { return $this->name; }
    public function startDate(): \DateTimeImmutable { return $this->startDate; }
    public function endDate(): \DateTimeImmutable { return $this->endDate; }
    public function type(): TournamentType { return $this->type; }
    public function maxParticipants(): int { return $this->maxParticipants; }
    public function status(): TournamentStatus { return $this->status; }
    public function description(): ?string { return $this->description; }
}
```
- [ ] **3.1.6** Run tests — expect green.
- [ ] **3.1.7** Commit: `feat(tournament): add Tournament domain entity with lifecycle`.

### Task 3.2 — Tournament repository + migration

- [ ] **3.2.1** Create `src/Tournament/Domain/TournamentRepository.php`:
```php
<?php
namespace App\Tournament\Domain;

use App\Shared\Domain\ValueObject\Uuid;

interface TournamentRepository
{
    public function save(Tournament $t): void;
    public function get(Uuid $id): ?Tournament;
    /** @return Tournament[] */
    public function all(): array;
    /** @return Tournament[] */
    public function published(): array;
}
```
- [ ] **3.2.2** Create `src/Tournament/Infrastructure/Doctrine/DoctrineTournamentRepository.php`:
```php
<?php
namespace App\Tournament\Infrastructure\Doctrine;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTournamentRepository implements TournamentRepository
{
    public function __construct(private EntityManagerInterface $em) {}
    public function save(Tournament $t): void { $this->em->persist($t); $this->em->flush(); }
    public function get(Uuid $id): ?Tournament
    {
        return $this->em->getRepository(Tournament::class)->findOneBy(['id' => (string)$id]);
    }
    public function all(): array
    {
        return $this->em->createQueryBuilder()->select('t')->from(Tournament::class, 't')
            ->orderBy('t.startDate', 'DESC')->getQuery()->getResult();
    }
    public function published(): array
    {
        return $this->em->getRepository(Tournament::class)
            ->findBy(['status' => TournamentStatus::PUBLISHED->value], ['startDate' => 'ASC']);
    }
}
```
- [ ] **3.2.3** Run `make console c="doctrine:migrations:diff"` then `make console c="doctrine:migrations:migrate --no-interaction"` — expect `tournaments` table.
- [ ] **3.2.4** Commit: `feat(tournament): add repository + migration`.

### Task 3.3 — Tournament Application Commands/Queries

- [ ] **3.3.1** Create `src/Tournament/Application/Command/CreateTournamentCommand.php`:
```php
<?php
namespace App\Tournament\Application\Command;
final class CreateTournamentCommand
{
    public function __construct(
        public readonly string $name,
        public readonly \DateTimeImmutable $startDate,
        public readonly \DateTimeImmutable $endDate,
        public readonly string $type,
        public readonly int $maxParticipants,
        public readonly ?string $description,
    ) {}
}
```
- [ ] **3.3.2** Create `CreateTournamentHandler.php`:
```php
<?php
namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;

final class CreateTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(CreateTournamentCommand $c): Uuid
    {
        $id = Uuid::generate();
        $t = Tournament::create($id, $c->name, $c->startDate, $c->endDate,
            TournamentType::from($c->type), $c->maxParticipants, $c->description);
        $this->repo->save($t);
        return $id;
    }
}
```
- [ ] **3.3.3** Create `UpdateTournamentCommand` + Handler (fields = Create fields + id; calls `$t->update(...)`).
```php
<?php
namespace App\Tournament\Application\Command;
final class UpdateTournamentCommand
{
    public function __construct(
        public readonly string $id, public readonly string $name,
        public readonly \DateTimeImmutable $startDate, public readonly \DateTimeImmutable $endDate,
        public readonly string $type, public readonly int $maxParticipants,
        public readonly ?string $description,
    ) {}
}
```
```php
<?php
namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;

final class UpdateTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(UpdateTournamentCommand $c): void
    {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->update($c->name, $c->startDate, $c->endDate, TournamentType::from($c->type),
            $c->maxParticipants, $c->description);
        $this->repo->save($t);
    }
}
```
- [ ] **3.3.4** Create `PublishTournamentCommand`, `CloseTournamentCommand` + Handlers:
```php
<?php
namespace App\Tournament\Application\Command;
final class PublishTournamentCommand { public function __construct(public readonly string $id) {} }
final class CloseTournamentCommand { public function __construct(public readonly string $id) {} }
```
```php
<?php
namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;

final class PublishTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(PublishTournamentCommand $c): void
    {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->publish(); $this->repo->save($t);
    }
}
```
```php
<?php
namespace App\Tournament\Application\Command;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;

final class CloseTournamentHandler
{
    public function __construct(private TournamentRepository $repo) {}
    public function __invoke(CloseTournamentCommand $c): void
    {
        $t = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $t->close(); $this->repo->save($t);
    }
}
```
- [ ] **3.3.5** Commit: `feat(tournament): add application commands (create/update/publish/close)`.

### Task 3.4 — Admin Tournament controller + templates

- [ ] **3.4.1** Create `src/Tournament/Infrastructure/Http/Admin/Form/TournamentType.php`:
```php
<?php
namespace App\Tournament\Infrastructure\Http\Admin\Form;

use App\Tournament\Domain\TournamentType as DomainType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, DateTimeType, IntegerType, TextType, TextareaType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class TournamentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $opts): void
    {
        $b->add('name', TextType::class, ['constraints' => [new Assert\NotBlank()]])
          ->add('startDate', DateTimeType::class, ['widget' => 'single_text'])
          ->add('endDate', DateTimeType::class, ['widget' => 'single_text'])
          ->add('type', ChoiceType::class, [
              'choices' => ['Ouvert' => DomainType::OPEN->value, 'Membres uniquement' => DomainType::MEMBERS_ONLY->value],
          ])
          ->add('maxParticipants', IntegerType::class, ['constraints' => [new Assert\Positive()]])
          ->add('description', TextareaType::class, ['required' => false]);
    }
}
```
- [ ] **3.4.2** Create `src/Tournament/Infrastructure/Http/Admin/TournamentController.php`:
```php
<?php
namespace App\Tournament\Infrastructure\Http\Admin;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Application\Command\{CloseTournamentCommand, CloseTournamentHandler, CreateTournamentCommand, CreateTournamentHandler, PublishTournamentCommand, PublishTournamentHandler, UpdateTournamentCommand, UpdateTournamentHandler};
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Infrastructure\Http\Admin\Form\TournamentType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournaments')]
#[IsGranted('ROLE_ADMIN')]
final class TournamentController extends AbstractController
{
    #[Route('', name: 'admin_tournament_list', methods: ['GET'])]
    public function list(TournamentRepository $repo): Response
    {
        return $this->render('admin/tournament/list.html.twig', ['tournaments' => $repo->all()]);
    }

    #[Route('/new', name: 'admin_tournament_new', methods: ['GET','POST'])]
    public function new(Request $r, CreateTournamentHandler $h): Response
    {
        $form = $this->createForm(TournamentType::class);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new CreateTournamentCommand($d['name'], $d['startDate'], $d['endDate'],
                $d['type'], (int)$d['maxParticipants'], $d['description'] ?? null));
            return $this->redirectToRoute('admin_tournament_list');
        }
        return $this->render('admin/tournament/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}', name: 'admin_tournament_detail', methods: ['GET'])]
    public function detail(string $id, TournamentRepository $repo, RegistrationRepository $regRepo): Response
    {
        $t = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        return $this->render('admin/tournament/detail.html.twig', [
            'tournament' => $t,
            'registrations' => $regRepo->byTournament(Uuid::fromString($id)),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_tournament_edit', methods: ['GET','POST'])]
    public function edit(string $id, Request $r, TournamentRepository $repo, UpdateTournamentHandler $h): Response
    {
        $t = $repo->get(Uuid::fromString($id)) ?? throw $this->createNotFoundException();
        $form = $this->createForm(TournamentType::class, [
            'name' => $t->name(), 'startDate' => $t->startDate(), 'endDate' => $t->endDate(),
            'type' => $t->type()->value, 'maxParticipants' => $t->maxParticipants(),
            'description' => $t->description(),
        ]);
        $form->handleRequest($r);
        if ($form->isSubmitted() && $form->isValid()) {
            $d = $form->getData();
            $h(new UpdateTournamentCommand($id, $d['name'], $d['startDate'], $d['endDate'],
                $d['type'], (int)$d['maxParticipants'], $d['description'] ?? null));
            return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
        }
        return $this->render('admin/tournament/edit.html.twig', ['form' => $form, 'tournament' => $t]);
    }

    #[Route('/{id}/publish', name: 'admin_tournament_publish', methods: ['POST'])]
    public function publish(string $id, Request $r, PublishTournamentHandler $h): Response
    {
        if ($this->isCsrfTokenValid('pub'.$id, $r->request->get('_token'))) $h(new PublishTournamentCommand($id));
        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }

    #[Route('/{id}/close', name: 'admin_tournament_close', methods: ['POST'])]
    public function close(string $id, Request $r, CloseTournamentHandler $h): Response
    {
        if ($this->isCsrfTokenValid('cls'.$id, $r->request->get('_token'))) $h(new CloseTournamentCommand($id));
        return $this->redirectToRoute('admin_tournament_detail', ['id' => $id]);
    }
}
```
- [ ] **3.4.3** Create `templates/admin/tournament/list.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>Tournois</h1>
  <a class="btn" href="{{ path('admin_tournament_new') }}">+ Nouveau</a>
  <table>
    <thead><tr><th>Nom</th><th>Début</th><th>Type</th><th>Statut</th><th>Max</th><th></th></tr></thead>
    <tbody>
    {% for t in tournaments %}
      <tr>
        <td>{{ t.name }}</td>
        <td>{{ t.startDate|date('d/m/Y') }}</td>
        <td>{{ t.type.value }}</td>
        <td>{{ t.status.value }}</td>
        <td>{{ t.maxParticipants }}</td>
        <td><a href="{{ path('admin_tournament_detail', {id: t.id}) }}">Voir</a></td>
      </tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```
- [ ] **3.4.4** Create `templates/admin/tournament/new.html.twig` and `edit.html.twig` (same shape as the member forms), and `detail.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>{{ tournament.name }}</h1>
  <p>{{ tournament.startDate|date('d/m/Y') }} → {{ tournament.endDate|date('d/m/Y') }}</p>
  <p>Type : {{ tournament.type.value }} — Statut : {{ tournament.status.value }} — Max : {{ tournament.maxParticipants }}</p>
  <p>{{ tournament.description }}</p>
  <a href="{{ path('admin_tournament_edit', {id: tournament.id}) }}">Éditer</a>

  {% if tournament.status.value == 'DRAFT' %}
    <form method="post" action="{{ path('admin_tournament_publish', {id: tournament.id}) }}">
      <input type="hidden" name="_token" value="{{ csrf_token('pub' ~ tournament.id) }}">
      <button>Publier</button>
    </form>
  {% elseif tournament.status.value == 'PUBLISHED' %}
    <form method="post" action="{{ path('admin_tournament_close', {id: tournament.id}) }}">
      <input type="hidden" name="_token" value="{{ csrf_token('cls' ~ tournament.id) }}">
      <button>Clôturer</button>
    </form>
  {% endif %}

  <h2>Inscriptions</h2>
  <table>
    <thead><tr><th>Nom</th><th>Prénom</th><th>Tel</th><th>Email</th><th>Statut</th><th>Date</th></tr></thead>
    <tbody>
    {% for r in registrations %}
      <tr>
        <td>{{ r.lastName }}</td><td>{{ r.firstName }}</td>
        <td>{{ r.phone }}</td><td>{{ r.email }}</td>
        <td>{{ r.status.value }}</td><td>{{ r.registeredAt|date('d/m/Y H:i') }}</td>
      </tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```
`new.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}<h1>Nouveau tournoi</h1>{{ form_start(form) }}{{ form_widget(form) }}<button>Créer</button>{{ form_end(form) }}{% endblock %}
```
`edit.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}<h1>Éditer {{ tournament.name }}</h1>{{ form_start(form) }}{{ form_widget(form) }}<button>Enregistrer</button>{{ form_end(form) }}{% endblock %}
```
- [ ] **3.4.5** Commit: `feat(tournament): add admin CRUD controller + templates`.

---

## Phase 4 — Registration context

### Task 4.1 — Registration entity + enum (TDD)

- [ ] **4.1.1** Create `src/Registration/Domain/RegistrationStatus.php`:
```php
<?php
namespace App\Registration\Domain;

enum RegistrationStatus: string
{
    case PENDING = 'PENDING';
    case CONFIRMED = 'CONFIRMED';
    case CANCELLED = 'CANCELLED';
    case WAITING_LIST = 'WAITING_LIST';
}
```
- [ ] **4.1.2** Create `tests/Registration/Domain/RegistrationTest.php`:
```php
<?php
namespace App\Tests\Registration\Domain;

use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class RegistrationTest extends TestCase
{
    private function make(RegistrationStatus $s = RegistrationStatus::PENDING): Registration
    {
        return Registration::create(Uuid::generate(), Uuid::generate(),
            'A', 'B', PhoneNumber::fromString('0612345678'), null, $s);
    }

    public function test_confirm_from_pending(): void
    {
        $r = $this->make(); $r->confirm();
        self::assertSame(RegistrationStatus::CONFIRMED, $r->status());
    }

    public function test_cannot_confirm_cancelled(): void
    {
        $r = $this->make(); $r->cancel();
        $this->expectException(\DomainException::class);
        $r->confirm();
    }

    public function test_promote_from_waiting_list(): void
    {
        $r = $this->make(RegistrationStatus::WAITING_LIST);
        $r->promoteToPending();
        self::assertSame(RegistrationStatus::PENDING, $r->status());
    }

    public function test_cannot_promote_non_waiting(): void
    {
        $this->expectException(\DomainException::class);
        $this->make()->promoteToPending();
    }
}
```
- [ ] **4.1.3** Run tests — expect red.
- [ ] **4.1.4** Create `src/Registration/Domain/Registration.php`:
```php
<?php
namespace App\Registration\Domain;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'registrations')]
class Registration
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'uuid')]
    private Uuid $tournamentId;
    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;
    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;
    #[ORM\Column(type: 'phone_number')]
    private PhoneNumber $phone;
    #[ORM\Column(type: 'email', nullable: true)]
    private ?Email $email;
    #[ORM\Column(type: 'string', enumType: RegistrationStatus::class)]
    private RegistrationStatus $status;
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $registeredAt;

    private function __construct(Uuid $id, Uuid $tournamentId, string $lastName, string $firstName,
        PhoneNumber $phone, ?Email $email, RegistrationStatus $status)
    {
        $this->id = $id; $this->tournamentId = $tournamentId;
        $this->lastName = $lastName; $this->firstName = $firstName;
        $this->phone = $phone; $this->email = $email;
        $this->status = $status; $this->registeredAt = new \DateTimeImmutable();
    }

    public static function create(Uuid $id, Uuid $tournamentId, string $lastName, string $firstName,
        PhoneNumber $phone, ?Email $email, RegistrationStatus $status): self
    {
        return new self($id, $tournamentId, $lastName, $firstName, $phone, $email, $status);
    }

    public function confirm(): void
    {
        if ($this->status === RegistrationStatus::CANCELLED) throw new \DomainException('cannot confirm cancelled');
        if ($this->status === RegistrationStatus::WAITING_LIST) throw new \DomainException('promote first');
        $this->status = RegistrationStatus::CONFIRMED;
    }

    public function cancel(): void
    {
        $this->status = RegistrationStatus::CANCELLED;
    }

    public function promoteToPending(): void
    {
        if ($this->status !== RegistrationStatus::WAITING_LIST) throw new \DomainException('only WAITING_LIST can be promoted');
        $this->status = RegistrationStatus::PENDING;
    }

    public function id(): Uuid { return $this->id; }
    public function tournamentId(): Uuid { return $this->tournamentId; }
    public function lastName(): string { return $this->lastName; }
    public function firstName(): string { return $this->firstName; }
    public function phone(): PhoneNumber { return $this->phone; }
    public function email(): ?Email { return $this->email; }
    public function status(): RegistrationStatus { return $this->status; }
    public function registeredAt(): \DateTimeImmutable { return $this->registeredAt; }
}
```
- [ ] **4.1.5** Run tests — expect green.
- [ ] **4.1.6** Commit: `feat(registration): add Registration domain entity`.

### Task 4.2 — Repository + migration

- [ ] **4.2.1** Create `src/Registration/Domain/RegistrationRepository.php`:
```php
<?php
namespace App\Registration\Domain;

use App\Shared\Domain\ValueObject\Uuid;

interface RegistrationRepository
{
    public function save(Registration $r): void;
    public function remove(Registration $r): void;
    public function get(Uuid $id): ?Registration;
    /** @return Registration[] */
    public function byTournament(Uuid $tournamentId): array;
    public function countConfirmed(Uuid $tournamentId): int;
    public function firstWaitingList(Uuid $tournamentId): ?Registration;
    /** @return Registration[] */
    public function all(?string $tournamentId, ?string $status): array;
}
```
- [ ] **4.2.2** Create `src/Registration/Infrastructure/Doctrine/DoctrineRegistrationRepository.php`:
```php
<?php
namespace App\Registration\Infrastructure\Doctrine;

use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRegistrationRepository implements RegistrationRepository
{
    public function __construct(private EntityManagerInterface $em) {}
    public function save(Registration $r): void { $this->em->persist($r); $this->em->flush(); }
    public function remove(Registration $r): void { $this->em->remove($r); $this->em->flush(); }
    public function get(Uuid $id): ?Registration
    {
        return $this->em->getRepository(Registration::class)->findOneBy(['id' => (string)$id]);
    }
    public function byTournament(Uuid $tournamentId): array
    {
        return $this->em->getRepository(Registration::class)
            ->findBy(['tournamentId' => (string)$tournamentId], ['registeredAt' => 'ASC']);
    }
    public function countConfirmed(Uuid $tournamentId): int
    {
        return (int)$this->em->createQueryBuilder()->select('COUNT(r.id)')
            ->from(Registration::class, 'r')
            ->where('r.tournamentId = :tid AND r.status = :st')
            ->setParameter('tid', (string)$tournamentId)
            ->setParameter('st', RegistrationStatus::CONFIRMED->value)
            ->getQuery()->getSingleScalarResult();
    }
    public function firstWaitingList(Uuid $tournamentId): ?Registration
    {
        return $this->em->getRepository(Registration::class)->findOneBy(
            ['tournamentId' => (string)$tournamentId, 'status' => RegistrationStatus::WAITING_LIST->value],
            ['registeredAt' => 'ASC']
        );
    }
    public function all(?string $tournamentId, ?string $status): array
    {
        $qb = $this->em->createQueryBuilder()->select('r')->from(Registration::class, 'r')
            ->orderBy('r.registeredAt', 'DESC');
        if ($tournamentId) $qb->andWhere('r.tournamentId = :tid')->setParameter('tid', $tournamentId);
        if ($status) $qb->andWhere('r.status = :st')->setParameter('st', $status);
        return $qb->getQuery()->getResult();
    }
}
```
- [ ] **4.2.3** Generate + run migration — expect `registrations` table.
- [ ] **4.2.4** Commit: `feat(registration): add repository + migration`.

### Task 4.3 — Register command (with waiting-list logic, MEMBERS_ONLY check)

- [ ] **4.3.1** Create `tests/Registration/Application/RegisterHandlerTest.php` using in-memory fakes:
```php
<?php
namespace App\Tests\Registration\Application;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Registration\Application\Command\RegisterCommand;
use App\Registration\Application\Command\RegisterHandler;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use App\Tournament\Domain\TournamentType;
use PHPUnit\Framework\TestCase;

final class RegisterHandlerTest extends TestCase
{
    private function openTournament(int $max = 2): Tournament
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, $max, null);
        $t->publish();
        return $t;
    }

    private function fakes(Tournament $t, array $regs = []): array
    {
        $tRepo = new class($t) implements TournamentRepository {
            public function __construct(private Tournament $t) {}
            public function save(Tournament $x): void {}
            public function get(Uuid $id): ?Tournament { return $this->t; }
            public function all(): array { return [$this->t]; }
            public function published(): array { return [$this->t]; }
        };
        $rRepo = new class($regs) implements RegistrationRepository {
            public array $store;
            public function __construct(array $init) { $this->store = $init; }
            public function save(Registration $r): void { $this->store[(string)$r->id()] = $r; }
            public function remove(Registration $r): void { unset($this->store[(string)$r->id()]); }
            public function get(Uuid $id): ?Registration { return $this->store[(string)$id] ?? null; }
            public function byTournament(Uuid $id): array { return array_values($this->store); }
            public function countConfirmed(Uuid $id): int {
                return count(array_filter($this->store, fn($r)=>$r->status()===RegistrationStatus::CONFIRMED));
            }
            public function firstWaitingList(Uuid $id): ?Registration {
                foreach ($this->store as $r) if ($r->status() === RegistrationStatus::WAITING_LIST) return $r;
                return null;
            }
            public function all(?string $t, ?string $s): array { return array_values($this->store); }
        };
        $match = new class extends MatchMemberHandler {
            public function __construct() {}
            public bool $ok = true;
            public function __invoke(MatchMemberQuery $q): bool { return $this->ok; }
        };
        return [$tRepo, $rRepo, $match];
    }

    public function test_creates_pending_if_space(): void
    {
        $t = $this->openTournament(2);
        [$tr, $rr, $m] = $this->fakes($t);
        $h = new RegisterHandler($tr, $rr, $m);
        $result = $h(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
        self::assertSame('PENDING', $result->status);
        self::assertCount(1, $rr->store);
    }

    public function test_goes_to_waiting_list_when_full(): void
    {
        $t = $this->openTournament(1);
        // pre-seed one CONFIRMED
        $confirmed = Registration::create(Uuid::generate(), $t->id(), 'X','Y',
            \App\Shared\Domain\ValueObject\PhoneNumber::fromString('0611111111'), null,
            RegistrationStatus::CONFIRMED);
        [$tr, $rr, $m] = $this->fakes($t, [(string)$confirmed->id() => $confirmed]);
        $h = new RegisterHandler($tr, $rr, $m);
        $result = $h(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
        self::assertSame('WAITING_LIST', $result->status);
    }

    public function test_rejects_members_only_when_not_member(): void
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::MEMBERS_ONLY, 10, null);
        $t->publish();
        [$tr, $rr, $m] = $this->fakes($t);
        $m->ok = false;
        $h = new RegisterHandler($tr, $rr, $m);
        $this->expectException(\DomainException::class);
        $h(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
    }

    public function test_rejects_if_tournament_not_published(): void
    {
        $t = Tournament::create(Uuid::generate(), 'T',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, 10, null); // DRAFT
        [$tr, $rr, $m] = $this->fakes($t);
        $h = new RegisterHandler($tr, $rr, $m);
        $this->expectException(\DomainException::class);
        $h(new RegisterCommand((string)$t->id(), 'A','B','0612345678',null));
    }
}
```
- [ ] **4.3.2** Run tests — expect red.
- [ ] **4.3.3** Create `src/Registration/Application/Command/RegisterCommand.php`:
```php
<?php
namespace App\Registration\Application\Command;

final class RegisterCommand
{
    public function __construct(
        public readonly string $tournamentId,
        public readonly string $lastName,
        public readonly string $firstName,
        public readonly string $phone,
        public readonly ?string $email,
    ) {}
}

final class RegisterResult
{
    public function __construct(public readonly string $id, public readonly string $status) {}
}
```

Actually split into two files. Create `src/Registration/Application/Command/RegisterResult.php`:
```php
<?php
namespace App\Registration\Application\Command;
final class RegisterResult
{
    public function __construct(public readonly string $id, public readonly string $status) {}
}
```
And fix `RegisterCommand.php` to contain only `RegisterCommand`.

- [ ] **4.3.4** Create `src/Registration/Application/Command/RegisterHandler.php`:
```php
<?php
namespace App\Registration\Application\Command;

use App\Member\Application\Query\MatchMemberHandler;
use App\Member\Application\Query\MatchMemberQuery;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentStatus;
use App\Tournament\Domain\TournamentType;

class RegisterHandler
{
    public function __construct(
        private TournamentRepository $tournaments,
        private RegistrationRepository $registrations,
        private MatchMemberHandler $matchMember,
    ) {}

    public function __invoke(RegisterCommand $c): RegisterResult
    {
        $t = $this->tournaments->get(Uuid::fromString($c->tournamentId))
            ?? throw new \DomainException('Tournament not found');

        if ($t->status() !== TournamentStatus::PUBLISHED) {
            throw new \DomainException('Tournament is not open for registration');
        }

        if ($t->type() === TournamentType::MEMBERS_ONLY) {
            $isMember = ($this->matchMember)(new MatchMemberQuery($c->lastName, $c->phone));
            if (!$isMember) {
                throw new \DomainException('Ce tournoi est réservé aux membres du club');
            }
        }

        $confirmedCount = $this->registrations->countConfirmed($t->id());
        $status = $confirmedCount >= $t->maxParticipants()
            ? RegistrationStatus::WAITING_LIST
            : RegistrationStatus::PENDING;

        $id = Uuid::generate();
        $reg = Registration::create($id, $t->id(),
            $c->lastName, $c->firstName,
            PhoneNumber::fromString($c->phone),
            $c->email ? Email::fromString($c->email) : null,
            $status);

        $this->registrations->save($reg);
        return new RegisterResult((string)$id, $status->value);
    }
}
```
- [ ] **4.3.5** Run tests — expect green.
- [ ] **4.3.6** Commit: `feat(registration): add RegisterHandler with waiting-list + members-only logic`.

### Task 4.4 — Confirm / Cancel / Delete handlers + waiting-list promotion

- [ ] **4.4.1** Create `tests/Registration/Application/CancelRegistrationHandlerTest.php`:
```php
<?php
namespace App\Tests\Registration\Application;

use App\Registration\Application\Command\CancelRegistrationCommand;
use App\Registration\Application\Command\CancelRegistrationHandler;
use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class CancelRegistrationHandlerTest extends TestCase
{
    public function test_cancelling_confirmed_promotes_first_waiting_list(): void
    {
        $tId = Uuid::generate();
        $confirmed = Registration::create(Uuid::generate(), $tId, 'C','f',
            PhoneNumber::fromString('0611111111'), null, RegistrationStatus::CONFIRMED);
        $waiting = Registration::create(Uuid::generate(), $tId, 'W','w',
            PhoneNumber::fromString('0622222222'), null, RegistrationStatus::WAITING_LIST);

        $repo = new class([$confirmed, $waiting]) implements RegistrationRepository {
            public array $store = [];
            public function __construct(array $init) {
                foreach ($init as $r) $this->store[(string)$r->id()] = $r;
            }
            public function save(Registration $r): void { $this->store[(string)$r->id()] = $r; }
            public function remove(Registration $r): void { unset($this->store[(string)$r->id()]); }
            public function get(Uuid $id): ?Registration { return $this->store[(string)$id] ?? null; }
            public function byTournament(Uuid $id): array { return array_values($this->store); }
            public function countConfirmed(Uuid $id): int {
                return count(array_filter($this->store, fn($r)=>$r->status()===RegistrationStatus::CONFIRMED));
            }
            public function firstWaitingList(Uuid $id): ?Registration {
                foreach ($this->store as $r) if ($r->status() === RegistrationStatus::WAITING_LIST) return $r;
                return null;
            }
            public function all(?string $t, ?string $s): array { return array_values($this->store); }
        };

        (new CancelRegistrationHandler($repo))(new CancelRegistrationCommand((string)$confirmed->id()));

        self::assertSame(RegistrationStatus::CANCELLED, $repo->get($confirmed->id())->status());
        self::assertSame(RegistrationStatus::PENDING, $repo->get($waiting->id())->status());
    }
}
```
- [ ] **4.4.2** Run tests — expect red.
- [ ] **4.4.3** Create `src/Registration/Application/Command/ConfirmRegistrationCommand.php`:
```php
<?php
namespace App\Registration\Application\Command;
final class ConfirmRegistrationCommand { public function __construct(public readonly string $id) {} }
```
`ConfirmRegistrationHandler.php`:
```php
<?php
namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class ConfirmRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}
    public function __invoke(ConfirmRegistrationCommand $c): void
    {
        $r = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $r->confirm();
        $this->repo->save($r);
    }
}
```
- [ ] **4.4.4** Create `CancelRegistrationCommand.php` and Handler (with waiting-list promotion):
```php
<?php
namespace App\Registration\Application\Command;
final class CancelRegistrationCommand { public function __construct(public readonly string $id) {} }
```
```php
<?php
namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}

    public function __invoke(CancelRegistrationCommand $c): void
    {
        $r = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $wasConfirmed = $r->status() === RegistrationStatus::CONFIRMED;
        $r->cancel();
        $this->repo->save($r);

        if ($wasConfirmed) {
            $next = $this->repo->firstWaitingList($r->tournamentId());
            if ($next) {
                $next->promoteToPending();
                $this->repo->save($next);
            }
        }
    }
}
```
- [ ] **4.4.5** Create `DeleteRegistrationCommand.php` + Handler:
```php
<?php
namespace App\Registration\Application\Command;
final class DeleteRegistrationCommand { public function __construct(public readonly string $id) {} }
```
```php
<?php
namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}
    public function __invoke(DeleteRegistrationCommand $c): void
    {
        $r = $this->repo->get(Uuid::fromString($c->id));
        if ($r) $this->repo->remove($r);
    }
}
```
- [ ] **4.4.6** Run tests — expect green.
- [ ] **4.4.7** Commit: `feat(registration): add confirm/cancel/delete with waiting-list promotion`.

### Task 4.5 — Public API controller (JSON)

- [ ] **4.5.1** Create `src/Registration/Infrastructure/Http/Public/RegistrationApiController.php`:
```php
<?php
namespace App\Registration\Infrastructure\Http\Public;

use App\Registration\Application\Command\RegisterCommand;
use App\Registration\Application\Command\RegisterHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationApiController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $r, RegisterHandler $h, ValidatorInterface $v): JsonResponse
    {
        $payload = json_decode($r->getContent(), true) ?? [];

        $violations = $v->validate($payload, new Assert\Collection([
            'tournamentId' => [new Assert\NotBlank(), new Assert\Uuid()],
            'lastName'     => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'firstName'    => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'phone'        => [new Assert\NotBlank()],
            'email'        => [new Assert\Optional([new Assert\Email()])],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $vi) $errors[trim($vi->getPropertyPath(), '[]')] = $vi->getMessage();
            return new JsonResponse(['ok' => false, 'errors' => $errors], 422);
        }

        try {
            $result = $h(new RegisterCommand(
                $payload['tournamentId'], $payload['lastName'], $payload['firstName'],
                $payload['phone'], $payload['email'] ?? null));
        } catch (\DomainException $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], 400);
        }

        $msg = match ($result->status) {
            'PENDING'      => 'Inscription enregistrée. Vous recevrez une confirmation.',
            'WAITING_LIST' => 'Le tournoi est complet — vous êtes sur liste d\'attente.',
            default        => 'Inscription enregistrée.',
        };
        return new JsonResponse(['ok' => true, 'status' => $result->status, 'message' => $msg]);
    }
}
```
- [ ] **4.5.2** Commit: `feat(registration): add public registration API endpoint`.

### Task 4.6 — Admin Registration controller

- [ ] **4.6.1** Create `src/Registration/Infrastructure/Http/Admin/RegistrationController.php`:
```php
<?php
namespace App\Registration\Infrastructure\Http\Admin;

use App\Registration\Application\Command\{CancelRegistrationCommand, CancelRegistrationHandler, ConfirmRegistrationCommand, ConfirmRegistrationHandler, DeleteRegistrationCommand, DeleteRegistrationHandler};
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/registrations')]
#[IsGranted('ROLE_ADMIN')]
final class RegistrationController extends AbstractController
{
    #[Route('', name: 'admin_registration_list', methods: ['GET'])]
    public function list(Request $r, RegistrationRepository $repo, TournamentRepository $trepo): Response
    {
        $tournamentId = $r->query->get('tournament');
        $status = $r->query->get('status');
        return $this->render('admin/registration/list.html.twig', [
            'registrations' => $repo->all($tournamentId, $status),
            'tournaments' => $trepo->all(),
            'selectedTournament' => $tournamentId,
            'selectedStatus' => $status,
        ]);
    }

    #[Route('/{id}/confirm', name: 'admin_registration_confirm', methods: ['POST'])]
    public function confirm(string $id, Request $r, ConfirmRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('cnf'.$id, $r->request->get('_token'))) $h(new ConfirmRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }

    #[Route('/{id}/cancel', name: 'admin_registration_cancel', methods: ['POST'])]
    public function cancel(string $id, Request $r, CancelRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('can'.$id, $r->request->get('_token'))) $h(new CancelRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }

    #[Route('/{id}', name: 'admin_registration_delete', methods: ['POST'])]
    public function delete(string $id, Request $r, DeleteRegistrationHandler $h): Response
    {
        if ($this->isCsrfTokenValid('del'.$id, $r->request->get('_token'))) $h(new DeleteRegistrationCommand($id));
        return $this->redirectToRoute('admin_registration_list');
    }
}
```
- [ ] **4.6.2** Create `templates/admin/registration/list.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>Inscriptions</h1>
  <form method="get">
    <select name="tournament">
      <option value="">Tous tournois</option>
      {% for t in tournaments %}
        <option value="{{ t.id }}" {{ selectedTournament == t.id|string ? 'selected' }}>{{ t.name }}</option>
      {% endfor %}
    </select>
    <select name="status">
      <option value="">Tous statuts</option>
      {% for s in ['PENDING','CONFIRMED','CANCELLED','WAITING_LIST'] %}
        <option value="{{ s }}" {{ selectedStatus == s ? 'selected' }}>{{ s }}</option>
      {% endfor %}
    </select>
    <button>Filtrer</button>
  </form>
  <table>
    <thead><tr><th>Date</th><th>Nom</th><th>Prénom</th><th>Tel</th><th>Email</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    {% for r in registrations %}
      <tr>
        <td>{{ r.registeredAt|date('d/m/Y H:i') }}</td>
        <td>{{ r.lastName }}</td><td>{{ r.firstName }}</td>
        <td>{{ r.phone }}</td><td>{{ r.email }}</td>
        <td>{{ r.status.value }}</td>
        <td>
          {% if r.status.value == 'PENDING' %}
            <form method="post" action="{{ path('admin_registration_confirm', {id: r.id}) }}" style="display:inline">
              <input type="hidden" name="_token" value="{{ csrf_token('cnf' ~ r.id) }}">
              <button>Confirmer</button>
            </form>
          {% endif %}
          {% if r.status.value in ['PENDING','CONFIRMED','WAITING_LIST'] %}
            <form method="post" action="{{ path('admin_registration_cancel', {id: r.id}) }}" style="display:inline">
              <input type="hidden" name="_token" value="{{ csrf_token('can' ~ r.id) }}">
              <button>Annuler</button>
            </form>
          {% endif %}
          <form method="post" action="{{ path('admin_registration_delete', {id: r.id}) }}" style="display:inline" onsubmit="return confirm('Supprimer ?')">
            <input type="hidden" name="_token" value="{{ csrf_token('del' ~ r.id) }}">
            <button>Supprimer</button>
          </form>
        </td>
      </tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```
- [ ] **4.6.3** Commit: `feat(registration): add admin registration list + actions`.

---

## Phase 5 — Security context (AdminUser + auth)

### Task 5.1 — AdminUser entity

- [ ] **5.1.1** Create `src/Security/Domain/AdminUser.php`:
```php
<?php
namespace App\Security\Domain;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'admin_users')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id] #[ORM\Column(type: 'uuid')]
    private Uuid $id;
    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;
    #[ORM\Column(type: 'string')]
    private string $password;
    #[ORM\Column(type: 'json')]
    private array $roles;

    public function __construct(Uuid $id, string $email, string $password, array $roles = ['ROLE_ADMIN'])
    {
        $this->id = $id; $this->email = $email; $this->password = $password; $this->roles = $roles;
    }

    public function id(): Uuid { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $p): void { $this->password = $p; }
    public function getRoles(): array { return array_unique([...$this->roles, 'ROLE_USER']); }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
}
```
- [ ] **5.1.2** Create `src/Security/Domain/AdminUserRepository.php`:
```php
<?php
namespace App\Security\Domain;

interface AdminUserRepository
{
    public function save(AdminUser $u): void;
    public function findByEmail(string $email): ?AdminUser;
}
```
- [ ] **5.1.3** Create `src/Security/Infrastructure/Doctrine/DoctrineAdminUserRepository.php`:
```php
<?php
namespace App\Security\Infrastructure\Doctrine;

use App\Security\Domain\AdminUser;
use App\Security\Domain\AdminUserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;

final class DoctrineAdminUserRepository extends ServiceEntityRepository implements AdminUserRepository, UserLoaderInterface
{
    public function __construct(ManagerRegistry $r) { parent::__construct($r, AdminUser::class); }

    public function save(AdminUser $u): void
    {
        $this->getEntityManager()->persist($u);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?AdminUser
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function loadUserByIdentifier(string $identifier): ?AdminUser
    {
        return $this->findByEmail($identifier);
    }
}
```
- [ ] **5.1.4** Generate + run migration — expect `admin_users` table.
- [ ] **5.1.5** Commit: `feat(security): add AdminUser entity + repository`.

### Task 5.2 — Configure Symfony Security

- [ ] **5.2.1** Edit `config/packages/security.yaml`:
```yaml
security:
  password_hashers:
    App\Security\Domain\AdminUser: 'auto'

  providers:
    admin_provider:
      entity:
        class: App\Security\Domain\AdminUser
        property: email

  firewalls:
    dev:
      pattern: ^/(_(profiler|wdt)|css|images|js)/
      security: false

    main:
      lazy: true
      provider: admin_provider
      form_login:
        login_path: app_login
        check_path: app_login
        enable_csrf: true
        default_target_path: admin_dashboard
      logout:
        path: app_logout
        target: app_login

  access_control:
    - { path: ^/admin/login, roles: PUBLIC_ACCESS }
    - { path: ^/admin, roles: ROLE_ADMIN }
```
- [ ] **5.2.2** Create `src/Security/Infrastructure/Http/LoginController.php`:
```php
<?php
namespace App\Security\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class LoginController extends AbstractController
{
    #[Route('/admin/login', name: 'app_login')]
    public function login(AuthenticationUtils $u): Response
    {
        return $this->render('admin/security/login.html.twig', [
            'last_username' => $u->getLastUsername(),
            'error' => $u->getLastAuthenticationError(),
        ]);
    }

    #[Route('/admin/logout', name: 'app_logout')]
    public function logout(): void { throw new \LogicException('handled by firewall'); }
}
```
- [ ] **5.2.3** Create `templates/admin/security/login.html.twig`:
```twig
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Connexion</title>
<link rel="stylesheet" href="{{ asset('build/admin.css') }}"></head>
<body class="admin login">
  <form method="post" class="login-form">
    <h1>Connexion admin</h1>
    {% if error %}<div class="error">{{ error.messageKey|trans(error.messageData, 'security') }}</div>{% endif %}
    <label>Email<input type="email" name="_username" value="{{ last_username }}" required></label>
    <label>Mot de passe<input type="password" name="_password" required></label>
    <input type="hidden" name="_csrf_token" value="{{ csrf_token('authenticate') }}">
    <button>Se connecter</button>
  </form>
</body>
</html>
```
- [ ] **5.2.4** Commit: `feat(security): add login form + firewall config`.

### Task 5.3 — `app:create-admin` CLI command

- [ ] **5.3.1** Create `src/Security/Infrastructure/Console/CreateAdminCommand.php`:
```php
<?php
namespace App\Security\Infrastructure\Console;

use App\Security\Domain\AdminUser;
use App\Security\Domain\AdminUserRepository;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private AdminUserRepository $repo,
        private UserPasswordHasherInterface $hasher,
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED)
             ->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $in, OutputInterface $out): int
    {
        $email = $in->getArgument('email');
        if ($this->repo->findByEmail($email)) {
            $out->writeln("<error>Admin $email already exists</error>");
            return Command::FAILURE;
        }
        $u = new AdminUser(Uuid::generate(), $email, 'placeholder');
        $u->setPassword($this->hasher->hashPassword($u, $in->getArgument('password')));
        $this->repo->save($u);
        $out->writeln("<info>Admin $email created</info>");
        return Command::SUCCESS;
    }
}
```
- [ ] **5.3.2** Test: `make console c="app:create-admin admin@astc.local secret123"` — expect success.
- [ ] **5.3.3** Smoke test: `curl -sI http://localhost:8080/admin/dashboard` — expect `302` to `/admin/login`.
- [ ] **5.3.4** Commit: `feat(security): add app:create-admin CLI command`.

### Task 5.4 — Dashboard controller

- [ ] **5.4.1** Create `src/Public/Infrastructure/Http/DashboardController.php` (lives in Public because it's the admin landing — alternative, put in Security/Infrastructure/Http). Place in `src/Security/Infrastructure/Http/DashboardController.php`:
```php
<?php
namespace App\Security\Infrastructure\Http;

use App\Member\Domain\MemberRepository;
use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_root', methods: ['GET'])]
    public function root(): Response { return $this->redirectToRoute('admin_dashboard'); }

    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(TournamentRepository $t, MemberRepository $m, RegistrationRepository $r): Response
    {
        $activeTournaments = count($t->published());
        $recent = array_slice($r->all(null, null), 0, 10);
        return $this->render('admin/dashboard.html.twig', [
            'activeTournaments' => $activeTournaments,
            'totalMembers' => count($m->search(null)),
            'totalRegistrations' => count($r->all(null, null)),
            'recentRegistrations' => $recent,
        ]);
    }
}
```
- [ ] **5.4.2** Create `templates/admin/dashboard.html.twig`:
```twig
{% extends 'base_admin.html.twig' %}
{% block body %}
  <h1>Dashboard</h1>
  <div class="stats">
    <div>Tournois actifs : {{ activeTournaments }}</div>
    <div>Membres : {{ totalMembers }}</div>
    <div>Inscriptions : {{ totalRegistrations }}</div>
  </div>
  <h2>Inscriptions récentes</h2>
  <table>
    <thead><tr><th>Date</th><th>Nom</th><th>Prénom</th><th>Statut</th></tr></thead>
    <tbody>
    {% for r in recentRegistrations %}
      <tr><td>{{ r.registeredAt|date('d/m/Y H:i') }}</td>
          <td>{{ r.lastName }}</td><td>{{ r.firstName }}</td>
          <td>{{ r.status.value }}</td></tr>
    {% endfor %}
    </tbody>
  </table>
{% endblock %}
```
- [ ] **5.4.3** Smoke test: log in via `/admin/login` with `admin@astc.local` / `secret123`, land on dashboard. Manual.
- [ ] **5.4.4** Commit: `feat(security): add admin dashboard`.

---

## Phase 6 — Public one-page frontend

### Task 6.1 — Webpack Encore + asset pipeline

- [ ] **6.1.1** Install node deps:
```
docker compose exec php sh -c "apk add --no-cache nodejs npm && npm install"
docker compose exec php npm install --save-dev @symfony/webpack-encore webpack webpack-cli
docker compose exec php npm install gsap swiper aos
```
- [ ] **6.1.2** Create `webpack.config.js`:
```js
const Encore = require('@symfony/webpack-encore');
if (!Encore.isRuntimeEnvironmentConfigured()) Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');

Encore
  .setOutputPath('public/build/')
  .setPublicPath('/build')
  .addEntry('app', './assets/app.js')
  .addStyleEntry('admin', './assets/styles/admin.css')
  .splitEntryChunks()
  .enableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction());

module.exports = Encore.getWebpackConfig();
```
- [ ] **6.1.3** Build: `docker compose exec php npx encore dev` — expect `public/build/` populated.
- [ ] **6.1.4** Commit: `chore: add webpack encore + gsap/swiper/aos`.

### Task 6.2 — Public homepage controller + base template

- [ ] **6.2.1** Create `src/Public/Infrastructure/Http/HomeController.php`:
```php
<?php
namespace App\Public\Infrastructure\Http;

use App\Registration\Domain\RegistrationRepository;
use App\Tournament\Domain\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'public_home', methods: ['GET'])]
    public function home(TournamentRepository $tRepo, RegistrationRepository $rRepo): Response
    {
        $tournaments = $tRepo->published();
        $rows = [];
        foreach ($tournaments as $t) {
            $rows[] = [
                'id' => (string)$t->id(),
                'name' => $t->name(),
                'startDate' => $t->startDate(),
                'endDate' => $t->endDate(),
                'type' => $t->type()->value,
                'description' => $t->description(),
                'max' => $t->maxParticipants(),
                'confirmed' => $rRepo->countConfirmed($t->id()),
            ];
        }
        return $this->render('public/home.html.twig', ['tournaments' => $rows]);
    }
}
```
- [ ] **6.2.2** Create `templates/base_public.html.twig`:
```twig
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{% block title %}ASTC Revigny — Tennis Club{% endblock %}</title>
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="https://unpkg.com/swiper@11/swiper-bundle.min.css">
  <link rel="stylesheet" href="{{ asset('build/app.css') }}">
</head>
<body>
  {% block body %}{% endblock %}
  <script src="{{ asset('build/runtime.js') }}"></script>
  <script src="{{ asset('build/app.js') }}"></script>
</body>
</html>
```
- [ ] **6.2.3** Commit: `feat(public): add home controller + base template`.

### Task 6.3 — CSS + section partials

- [ ] **6.3.1** Create `assets/styles/app.css`:
```css
:root {
  --primary: #1A2B6D;
  --accent: #E8721A;
  --bg: #FFFFFF;
  --bg-alt: #F7F8FC;
  --text: #222;
}
* { box-sizing: border-box; }
body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; color: var(--text); background: var(--bg); }
h1, h2, h3 { font-family: Georgia, serif; color: var(--primary); }
a { color: var(--accent); text-decoration: none; }

/* Nav */
.nav { position: sticky; top:0; z-index:100; display:flex; justify-content:space-between; align-items:center;
  padding: .75rem 2rem; background: rgba(255,255,255,.95); backdrop-filter: blur(6px);
  border-bottom: 1px solid #eee; }
.nav .logo { font-family: Georgia, serif; color: var(--primary); font-weight:700; }
.nav ul { display:flex; gap:1.5rem; list-style:none; margin:0; padding:0; }
.nav a { color: var(--primary); }
.btn { display:inline-block; background: var(--accent); color:#fff; padding:.7rem 1.4rem;
  border:none; border-radius:4px; cursor:pointer; font-weight:600; }
.btn.outline { background:transparent; color:var(--primary); border:2px solid var(--primary); }

/* Hero */
.hero { position: relative; height: 90vh; overflow: hidden; color:#fff;
  display:flex; align-items:center; justify-content:center; text-align:center; }
.hero-bg { position:absolute; inset:-10% 0; background: url('/images/banniere.jpg') center/cover no-repeat; will-change: transform; }
.hero::after { content:""; position:absolute; inset:0; background: linear-gradient(rgba(26,43,109,.55), rgba(26,43,109,.75)); }
.hero-content { position: relative; z-index:1; max-width: 800px; padding: 0 1rem; }
.hero h1 { color:#fff; font-size: clamp(2rem, 5vw, 3.5rem); margin:0 0 1rem; }
.hero .ctas { display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; }

/* Sections */
section { padding: 5rem 2rem; max-width: 1200px; margin: 0 auto; }
.divider { width: 60px; height: 4px; background: var(--accent); margin: 1rem 0 2rem; }
.club { display:grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items:center; }
.club-stats { display:grid; grid-template-columns: repeat(3,1fr); gap:1rem; margin-top:2rem; }
.stat { text-align:center; padding:1rem; background: var(--bg-alt); border-radius: 6px; }
.stat strong { display:block; font-size: 2rem; color: var(--accent); }

/* Tournaments */
.tournaments-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap: 1.5rem; }
.tournament-card { background:#fff; border-radius:8px; padding:1.5rem; box-shadow:0 4px 12px rgba(0,0,0,.06); transition: transform .3s; }
.tournament-card .badge { display:inline-block; padding:.2rem .6rem; border-radius:3px; font-size:.75rem;
  background: var(--accent); color:#fff; font-weight:600; }
.tournament-card .badge.members { background: var(--primary); }
.tournament-card .remaining { color:#666; font-size:.9rem; margin:.5rem 0 1rem; }

/* Gallery */
.gallery { background: var(--primary); color:#fff; }
.gallery h2 { color:#fff; }
.swiper { width:100%; padding: 2rem 0; }
.swiper-slide img { width:100%; height:300px; object-fit:cover; border-radius:6px; }

/* Contact */
.contact-grid { display:grid; grid-template-columns: 1fr 2fr; gap: 3rem; }
.contact iframe { width:100%; height: 300px; border:0; border-radius:6px; }

footer { background: var(--primary); color:#fff; text-align:center; padding: 2rem; }

/* Modal */
.modal { position: fixed; inset:0; background: rgba(0,0,0,.6); display:none; align-items:center;
  justify-content:center; z-index:1000; }
.modal.open { display:flex; }
.modal-content { background:#fff; padding: 2rem; border-radius:8px; width: min(500px,90%); }
.modal-content label { display:block; margin:.5rem 0; }
.modal-content input { width:100%; padding:.6rem; border:1px solid #ccc; border-radius:4px; }
.modal-content .close { float:right; cursor:pointer; background:none; border:none; font-size:1.5rem; }
.modal-feedback { margin-top:1rem; padding:1rem; border-radius:4px; display:none; }
.modal-feedback.ok { background: #e8f5e9; color: #2e7d32; display:block; }
.modal-feedback.err { background: #ffebee; color: #c62828; display:block; }

@media (max-width: 768px) {
  .club, .contact-grid { grid-template-columns: 1fr; }
  section { padding: 3rem 1rem; }
}
```
- [ ] **6.3.2** Create `assets/styles/admin.css`:
```css
:root { --primary: #1A2B6D; --accent: #E8721A; }
body.admin { font-family: "Segoe UI", system-ui, sans-serif; margin:0; background: #f7f8fc; color:#222; }
.admin-nav { background: var(--primary); color:#fff; padding: 1rem 2rem; display:flex; gap:1.5rem; }
.admin-nav a { color:#fff; text-decoration:none; }
.admin-main { padding: 2rem; max-width: 1200px; margin: 0 auto; }
table { width:100%; border-collapse: collapse; margin: 1rem 0; background:#fff; }
th, td { padding: .6rem .8rem; border-bottom: 1px solid #eee; text-align:left; }
th { background: #f0f2f7; }
.btn, button { background: var(--accent); color:#fff; border:none; padding:.5rem 1rem; border-radius:4px; cursor:pointer; }
a.btn { display:inline-block; text-decoration:none; }
.login-form { max-width: 380px; margin: 5rem auto; background:#fff; padding:2rem; border-radius:8px; }
.login-form label { display:block; margin:.5rem 0; }
.login-form input { width:100%; padding:.6rem; border:1px solid #ccc; border-radius:4px; }
.error { background:#ffebee; color:#c62828; padding:.6rem; border-radius:4px; margin-bottom:1rem; }
.stats { display:grid; grid-template-columns: repeat(3,1fr); gap:1rem; margin-bottom:2rem; }
.stats > div { background:#fff; padding:1.5rem; border-radius:6px; font-size:1.2rem; }
```
- [ ] **6.3.3** Create `templates/public/_partials/nav.html.twig`:
```twig
<nav class="nav">
  <a class="logo" href="#hero">ASTC Revigny</a>
  <ul>
    <li><a href="#club">Le club</a></li>
    <li><a href="#tournaments">Tournois</a></li>
    <li><a href="#gallery">Galerie</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <a class="btn" href="#tournaments">S'inscrire</a>
</nav>
```
- [ ] **6.3.4** Create `templates/public/_partials/hero.html.twig`:
```twig
<section id="hero" class="hero">
  <div class="hero-bg" data-parallax></div>
  <div class="hero-content">
    <h1>AS Tennis Club Revigny</h1>
    <p>Le tennis en Meuse, depuis plus de 40 ans.</p>
    <div class="ctas">
      <a class="btn" href="#tournaments">Voir les tournois</a>
      <a class="btn outline" href="#club">Découvrir le club</a>
    </div>
  </div>
</section>
```
- [ ] **6.3.5** Create `templates/public/_partials/club.html.twig`:
```twig
<section id="club" data-aos="fade-up">
  <h2>Le club</h2>
  <div class="divider"></div>
  <div class="club">
    <div>
      <p>Fondé en 1978, l'ASTC Revigny réunit passionnés et compétiteurs autour de courts couverts et extérieurs en plein cœur de Revigny-sur-Ornain.</p>
      <div class="club-stats">
        <div class="stat"><strong>4</strong>Courts</div>
        <div class="stat"><strong>120+</strong>Membres</div>
        <div class="stat"><strong>6</strong>Tournois/an</div>
      </div>
    </div>
    <img src="{{ asset('images/banniere.jpg') }}" alt="Courts ASTC" style="width:100%;border-radius:8px">
  </div>
</section>
```
- [ ] **6.3.6** Create `templates/public/_partials/tournaments.html.twig`:
```twig
<section id="tournaments" data-aos="fade-up">
  <h2>Tournois</h2>
  <div class="divider"></div>
  {% if tournaments|length == 0 %}
    <p>Aucun tournoi publié pour le moment.</p>
  {% else %}
    <div class="tournaments-grid">
      {% for t in tournaments %}
        <article class="tournament-card" data-card>
          <span class="badge {{ t.type == 'MEMBERS_ONLY' ? 'members' }}">
            {{ t.type == 'OPEN' ? 'OUVERT' : 'MEMBRES' }}
          </span>
          <h3>{{ t.name }}</h3>
          <p>{{ t.startDate|date('d/m/Y') }} → {{ t.endDate|date('d/m/Y') }}</p>
          {% if t.description %}<p>{{ t.description }}</p>{% endif %}
          <p class="remaining">Places restantes : {{ max(0, t.max - t.confirmed) }} / {{ t.max }}</p>
          <button class="btn" data-register="{{ t.id }}" data-type="{{ t.type }}" data-name="{{ t.name }}">S'inscrire</button>
        </article>
      {% endfor %}
    </div>
  {% endif %}
</section>
```
- [ ] **6.3.7** Create `templates/public/_partials/gallery.html.twig`:
```twig
<section id="gallery" class="gallery" data-aos="fade-up">
  <h2>Galerie</h2>
  <div class="divider"></div>
  <div class="swiper gallery-swiper">
    <div class="swiper-wrapper">
      {% for n in 1..6 %}
        <div class="swiper-slide"><img src="{{ asset('images/banniere.jpg') }}" alt="Photo {{ n }}"></div>
      {% endfor %}
    </div>
    <div class="swiper-pagination"></div>
  </div>
</section>
```
- [ ] **6.3.8** Create `templates/public/_partials/contact.html.twig`:
```twig
<section id="contact" data-aos="fade-up">
  <h2>Contact</h2>
  <div class="divider"></div>
  <div class="contact-grid">
    <div>
      <p><strong>ASTC Revigny</strong><br>55800 Revigny-sur-Ornain</p>
      <p><a href="https://facebook.com" target="_blank">Facebook</a></p>
    </div>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2658.0!2d5.0!3d48.83!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1"
      loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>
</section>
```
- [ ] **6.3.9** Create `templates/public/_partials/footer.html.twig`:
```twig
<footer>
  <p>&copy; {{ "now"|date("Y") }} AS Tennis Club Revigny — Tous droits réservés</p>
</footer>
```
- [ ] **6.3.10** Create `templates/public/_partials/modal_registration.html.twig`:
```twig
<div class="modal" id="registration-modal">
  <div class="modal-content">
    <button class="close" data-close>&times;</button>
    <h2>Inscription — <span data-tournament-name></span></h2>
    <form id="registration-form">
      <input type="hidden" name="tournamentId">
      <label>Nom *<input name="lastName" required></label>
      <label>Prénom *<input name="firstName" required></label>
      <label>Téléphone *<input name="phone" required></label>
      <label>Email<input type="email" name="email"></label>
      <button class="btn" type="submit">Envoyer</button>
    </form>
    <div class="modal-feedback" id="modal-feedback"></div>
  </div>
</div>
```
- [ ] **6.3.11** Create `templates/public/home.html.twig`:
```twig
{% extends 'base_public.html.twig' %}
{% block body %}
  {% include 'public/_partials/nav.html.twig' %}
  {% include 'public/_partials/hero.html.twig' %}
  {% include 'public/_partials/club.html.twig' %}
  {% include 'public/_partials/tournaments.html.twig' %}
  {% include 'public/_partials/gallery.html.twig' %}
  {% include 'public/_partials/contact.html.twig' %}
  {% include 'public/_partials/footer.html.twig' %}
  {% include 'public/_partials/modal_registration.html.twig' %}
{% endblock %}
```
- [ ] **6.3.12** Commit: `feat(public): add one-page homepage sections + templates`.

### Task 6.4 — Public JS (GSAP parallax, Swiper, AOS, modal fetch)

- [ ] **6.4.1** Create `assets/app.js`:
```js
import './styles/app.css';
import AOS from 'aos';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Swiper from 'swiper';
import { Pagination, EffectCoverflow, Autoplay } from 'swiper/modules';
import './js/hero-parallax.js';
import './js/cards-hover.js';
import './js/gallery-swiper.js';
import './js/registration-modal.js';

gsap.registerPlugin(ScrollTrigger);
document.addEventListener('DOMContentLoaded', () => {
  AOS.init({ duration: 700, once: true });
});

window.__astc = { gsap, ScrollTrigger, Swiper, Pagination, EffectCoverflow, Autoplay };
```
- [ ] **6.4.2** Create `assets/js/hero-parallax.js`:
```js
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);

document.addEventListener('DOMContentLoaded', () => {
  const bg = document.querySelector('[data-parallax]');
  if (!bg) return;
  gsap.to(bg, {
    yPercent: 20,
    ease: 'none',
    scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true },
  });
});
```
- [ ] **6.4.3** Create `assets/js/cards-hover.js`:
```js
import { gsap } from 'gsap';
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-card]').forEach(card => {
    card.addEventListener('mouseenter', () => gsap.to(card, { y: -6, boxShadow: '0 12px 24px rgba(0,0,0,.12)', duration: .25 }));
    card.addEventListener('mouseleave', () => gsap.to(card, { y: 0, boxShadow: '0 4px 12px rgba(0,0,0,.06)', duration: .25 }));
  });
});
```
- [ ] **6.4.4** Create `assets/js/gallery-swiper.js`:
```js
import Swiper from 'swiper';
import { Pagination, EffectCoverflow, Autoplay } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/effect-coverflow';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.querySelector('.gallery-swiper');
  if (!el) return;
  const mobile = window.matchMedia('(max-width: 768px)').matches;
  new Swiper(el, {
    modules: [Pagination, EffectCoverflow, Autoplay],
    slidesPerView: mobile ? 1.2 : 3,
    spaceBetween: 20,
    centeredSlides: mobile,
    effect: mobile ? 'coverflow' : 'slide',
    coverflowEffect: { rotate: 20, depth: 100, modifier: 1, slideShadows: false },
    autoplay: { delay: 4000 },
    pagination: { el: '.swiper-pagination', clickable: true },
    loop: true,
  });
});
```
- [ ] **6.4.5** Create `assets/js/registration-modal.js`:
```js
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('registration-modal');
  const form = document.getElementById('registration-form');
  const feedback = document.getElementById('modal-feedback');
  const nameEl = modal.querySelector('[data-tournament-name]');

  const open = (id, name) => {
    form.reset();
    feedback.className = 'modal-feedback';
    feedback.textContent = '';
    form.tournamentId.value = id;
    nameEl.textContent = name;
    modal.classList.add('open');
  };
  const close = () => modal.classList.remove('open');

  document.querySelectorAll('[data-register]').forEach(btn => {
    btn.addEventListener('click', () => open(btn.dataset.register, btn.dataset.name));
  });
  modal.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', close));
  modal.addEventListener('click', e => { if (e.target === modal) close(); });

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const payload = {
      tournamentId: form.tournamentId.value,
      lastName: form.lastName.value,
      firstName: form.firstName.value,
      phone: form.phone.value,
      email: form.email.value || null,
    };
    const res = await fetch('/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (data.ok) {
      feedback.className = 'modal-feedback ok';
      feedback.textContent = data.message;
      form.reset();
    } else {
      feedback.className = 'modal-feedback err';
      feedback.textContent = data.message || Object.values(data.errors || {}).join(' ');
    }
  });
});
```
- [ ] **6.4.6** Build assets: `docker compose exec php npx encore dev` — expect success.
- [ ] **6.4.7** Manual smoke test: open `http://localhost:8080/`. Confirm hero parallax, sections fade in, Swiper gallery, tournament cards, modal opens and submits successfully. Create a tournament first via `/admin` if empty.
- [ ] **6.4.8** Commit: `feat(public): add GSAP parallax, Swiper gallery, AOS, registration modal`.

---

## Phase 7 — Functional tests (end-to-end coverage)

### Task 7.1 — Test bootstrap

- [ ] **7.1.1** Create `tests/bootstrap.php`:
```php
<?php
require dirname(__DIR__).'/vendor/autoload.php';
if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) require dirname(__DIR__).'/config/bootstrap.php';
else (new Symfony\Component\Dotenv\Dotenv())->bootEnv(dirname(__DIR__).'/.env');

passthru('php bin/console doctrine:database:drop --force --env=test --if-exists');
passthru('php bin/console doctrine:database:create --env=test');
passthru('php bin/console doctrine:migrations:migrate --no-interaction --env=test');
```
- [ ] **7.1.2** Edit `.env.test`: set `DATABASE_URL="mysql://astc:astc@mysql:3306/astc_test?serverVersion=8.0"`.
- [ ] **7.1.3** Edit `phpunit.xml.dist` to register `dama/doctrine-test-bundle` extension:
```xml
<extensions>
  <bootstrap class="DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension"/>
</extensions>
```
- [ ] **7.1.4** Commit: `chore(test): bootstrap functional test database`.

### Task 7.2 — API registration functional test

- [ ] **7.2.1** Create `tests/Registration/Functional/RegistrationApiTest.php`:
```php
<?php
namespace App\Tests\Registration\Functional;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\Tournament;
use App\Tournament\Domain\TournamentRepository;
use App\Tournament\Domain\TournamentType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegistrationApiTest extends WebTestCase
{
    public function test_register_success(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(TournamentRepository::class);
        $t = Tournament::create(Uuid::generate(), 'Open',
            new \DateTimeImmutable('+1 day'), new \DateTimeImmutable('+2 days'),
            TournamentType::OPEN, 10, null);
        $t->publish(); $repo->save($t);

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tournamentId' => (string)$t->id(), 'lastName' => 'D', 'firstName' => 'J',
                         'phone' => '0612345678', 'email' => null]));
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['ok']);
        self::assertSame('PENDING', $data['status']);
    }

    public function test_register_validation_fails(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['tournamentId' => 'bad', 'lastName' => '', 'firstName' => '', 'phone' => '']));
        self::assertResponseStatusCodeSame(422);
    }
}
```
- [ ] **7.2.2** Run: `make test` — expect green.
- [ ] **7.2.3** Commit: `test(registration): add API functional test`.

---

## Phase 8 — Final polish

### Task 8.1 — README quickstart

- [ ] **8.1.1** Update `README.md` with the bootstrap commands:
```
make up
make install
make console c="doctrine:migrations:migrate --no-interaction"
make console c="app:create-admin admin@astc.local secret123"
docker compose exec php npx encore dev

# Public:  http://localhost:8080/
# Admin:   http://localhost:8080/admin/login
# Mailpit: http://localhost:8025/
```
- [ ] **8.1.2** Commit: `docs: add quickstart to README`.

### Task 8.2 — Full-pipeline smoke test

- [ ] **8.2.1** Run `make test` — expect all green.
- [ ] **8.2.2** Manual walk-through:
  - Log in at `/admin/login` → dashboard loads.
  - Create a Member via `/admin/members/new` → appears in list.
  - Create a Tournament via `/admin/tournaments/new`, publish it.
  - Visit `/` → tournament card visible.
  - Open registration modal → submit → success toast inside modal, no page reload.
  - Visit `/admin/registrations` → new registration in PENDING.
  - Click Confirm → status CONFIRMED.
  - Fill the tournament to capacity; next submission returns "liste d'attente".
  - Cancel a CONFIRMED → first WAITING_LIST becomes PENDING.
- [ ] **8.2.3** Commit (if any cleanup): `chore: final polish`.

---

## Self-review

- **Spec coverage:** Docker (§7) → Phase 0. DDD (§3) → Phase 0.3 + per-context Phases 2-5. Entities (§4) → Phases 2.1, 3.1, 4.1, 5.1. Waiting list (§5) → Task 4.4. Members-only match → Task 4.3. Public one-page + sections (§5) → Phase 6. Colors/fonts → Task 6.3.1. Back office routes (§6) → Phases 2.4, 3.4, 4.6, 5.4. `app:create-admin` → Task 5.3. Out of scope items are not implemented — correct.
- **Placeholder scan:** no TODO / TBD / "similar to above" — all code shown in full.
- **Consistency:** Every `$repo->get(Uuid)` returns `?Entity`; every Command is a plain readonly DTO; Handlers follow `__invoke`; Registration lifecycle matches the spec exactly (PENDING → CONFIRMED, WAITING_LIST → PENDING on CONFIRMED cancellation).
