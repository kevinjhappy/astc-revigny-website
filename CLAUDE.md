# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Website for ASTC Revigny (Association Sportive et de Tennis Club de Revigny), a local tennis club.

Two distinct areas:
- **Public one-page site** — scrollable page with hero, club info, tournaments, gallery, contact
- **Back office** — admin-only area for managing tournaments, members, and registrations

## Developer Profile

PHP developer with 15 years of experience. No need to over-explain PHP fundamentals.

## Tech Stack

- **PHP 8.3** / **Symfony 7.4** (framework-bundle, security, form, validator, mailer, twig)
- **Doctrine ORM 3** with MySQL 8 — custom Doctrine types: UuidType, EmailType, PhoneNumberType
- **Webpack Encore** with GSAP, Swiper.js, AOS (animations on public page)
- **Docker Compose**: php-fpm (port 9000), nginx (port 8080), mysql (port 3306), mailpit (port 8026)
- DDD with bounded contexts: Shared, Member, Tournament, Registration, Security, Public

## Common Commands

```bash
# Start / stop
make up          # docker compose up -d
make down        # docker compose down

# App shell
make sh          # exec into php container

# Symfony console
make console CMD="cache:clear"
make console CMD="doctrine:migrations:migrate"

# Tests
make test        # php bin/phpunit --testdox

# Install dependencies
make install     # composer install
```

## Architecture

DDD bounded contexts, each in `src/<Context>/`:
- `Domain/` — Entities, Value Objects, Repository interfaces
- `Application/Command/` and `Application/Query/` — CQRS handlers
- `Infrastructure/Doctrine/` — Repository implementations
- `Infrastructure/Http/` — Symfony controllers
  - `Admin/` — ROLE_ADMIN protected routes
  - `Public/` — public routes (registration API lives here)

**Cross-context references:** by UUID string only — no Doctrine FK between contexts.

**Value Objects:** `Uuid` (ramsey/uuid), `Email`, `PhoneNumber` (libphonenumber) in `src/Shared/Domain/ValueObject/`.

**Security:** form_login on `/admin/login`, `ROLE_ADMIN` required for all `/admin/*` routes. Create admin with `make console CMD="app:create-admin email password"`.

**Public registration:** `POST /api/register` (JSON). Waiting-list logic: when `confirmed >= maxParticipants`, status → WAITING_LIST. Cancelling a CONFIRMED registration auto-promotes the first WAITING_LIST entry to PENDING.

**Frontend assets:** `assets/app.js` imports all JS modules; `assets/styles/app.css` uses CSS custom properties (`--primary: #1A2B6D`, `--accent: #E8721A`).

## Key Requirements

- Public page: single scrollable page (one-page pattern)
- Back office: authentication required; admins manage tournaments, members, registrations
- MEMBERS_ONLY tournament type: validates registrant's lastName + phone against the members table

## graphify

This project has a graphify knowledge graph at graphify-out/.

Rules:
- Before answering architecture or codebase questions, read graphify-out/GRAPH_REPORT.md for god nodes and community structure
- If graphify-out/wiki/index.md exists, navigate it instead of reading raw files
- After modifying code files in this session, run `graphify update .` to keep the graph current (AST-only, no API cost)
