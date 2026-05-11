# Cotisations membres — Design Spec

Date : 2026-05-11
Contexte : Site ASTC Révigny (Symfony 7.4 / PHP 8.3 / MySQL 8)

---

## Contexte et objectifs

Le contexte `Member` existant gère les membres du club (CRUD admin + import CSV). Il faut y ajouter :

1. **3 types de cotisation** (terrain seul, terrain + tournois, terrain + tournois + cours)
2. **Un statut de paiement** par membre par saison (payé / en attente)
3. **Un historique par saison** (entité `MemberSubscription` séparée)
4. **Un workflow de renouvellement** en début de saison (septembre)
5. **Un export Excel** de la liste courante
6. **Un import CSV mis à jour** acceptant les en-têtes français et les mises à jour masse
7. **Une restriction tournois** : seuls les membres avec cotisation Terrains+Tournois ou Terrains+Tournois+Cours (et statut PAID) peuvent s'inscrire aux tournois internes

---

## Modèle de données

### `Member` — inchangé

Table `members` : id, last_name, first_name, phone, email, birth_date.

### Nouvelle entité `MemberSubscription`

Table `member_subscriptions` :

| Colonne | Type | Contrainte |
|---|---|---|
| `id` | CHAR(36) | PK |
| `member_id` | CHAR(36) | NOT NULL (référence croisée, pas de FK Doctrine) |
| `season` | VARCHAR(9) | NOT NULL — ex. `"2025-2026"` |
| `type` | ENUM('TERRAIN','TERRAIN_TOURNOIS','TERRAIN_TOURNOIS_COURS') | NOT NULL |
| `status` | ENUM('PENDING','PAID') | NOT NULL, défaut PENDING |
| `created_at` | DATETIME | NOT NULL |
| `updated_at` | DATETIME | NOT NULL |

Contrainte unique sur `(member_id, season)`.

### Enums PHP (src/Member/Domain/)

```php
enum MembershipType: string {
    case TERRAIN = 'TERRAIN';
    case TERRAIN_TOURNOIS = 'TERRAIN_TOURNOIS';
    case TERRAIN_TOURNOIS_COURS = 'TERRAIN_TOURNOIS_COURS';

    public function label(): string {
        return match($this) {
            self::TERRAIN => 'Terrains',
            self::TERRAIN_TOURNOIS => 'Terrains + Tournois',
            self::TERRAIN_TOURNOIS_COURS => 'Terrains + Tournois + Cours',
        };
    }

    public function hasTournamentAccess(): bool {
        return $this !== self::TERRAIN;
    }
}

enum SubscriptionStatus: string {
    case PENDING = 'PENDING';
    case PAID = 'PAID';

    public function label(): string {
        return match($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payé',
        };
    }
}
```

---

## Service `SeasonHelper`

`src/Member/Domain/SeasonHelper.php` — calcule la saison courante depuis la date système :

- mois ≥ 9 → `"ANNÉE/ANNÉE+1"` (ex. sept. 2026 → `"2026-2027"`)
- mois < 9 → `"ANNÉE-1/ANNÉE"` (ex. mai 2026 → `"2025-2026"`)

Méthodes :
- `currentSeason(): string`
- `nextSeason(): string`
- `previousSeason(): string`

---

## Couche Application (CQRS)

### Nouvelles commandes

**`CreateMemberSubscriptionCommand`**
- Propriétés : memberId (string), season (string), type (MembershipType), status (SubscriptionStatus = PENDING)
- Handler : crée et persiste une `MemberSubscription`; lève `DomainException` si une souscription existe déjà pour ce membre+saison

**`UpdateMemberSubscriptionCommand`**
- Propriétés : id (string), type (MembershipType), status (SubscriptionStatus)
- Handler : charge par id, met à jour type/status/updated_at, persiste

**`StartNewSeasonCommand`**
- Propriétés : season (string) — la nouvelle saison à démarrer
- Handler : récupère toutes les souscriptions `PAID` de la saison précédente, crée une `MemberSubscription` `PENDING` avec le même type pour chaque membre concerné, ignore les doublons (idempotent)

### Nouvelles queries

**`GetCurrentSubscriptionQuery`**
- Propriétés : memberId (string)
- Handler : retourne `?MemberSubscription` pour la saison courante

**`GetSubscriptionHistoryQuery`**
- Propriétés : memberId (string)
- Handler : retourne `MemberSubscription[]` triées par saison DESC

---

## Repository

`MemberSubscriptionRepository` (interface) dans `src/Member/Domain/` :
- `save(MemberSubscription): void`
- `get(Uuid $id): ?MemberSubscription`
- `findByMemberAndSeason(string $memberId, string $season): ?MemberSubscription`
- `findPaidBySeason(string $season): MemberSubscription[]`
- `findByMember(string $memberId): MemberSubscription[]` — triées par saison DESC

`DoctrineMemberSubscriptionRepository` dans `src/Member/Infrastructure/Doctrine/`.

---

## UI Admin

### Liste des membres (`GET /admin/members`)

Colonnes ajoutées : **Type de cotisation** et **Statut paiement** pour la saison courante (badge coloré vert = Payé, orange = En attente, gris = "—" si aucune souscription).

Boutons en haut de page :
- **"Démarrer la saison XXXX-XXXX"** — visible uniquement si aucune souscription n'existe encore pour la prochaine saison. Déclenche `StartNewSeasonCommand`.
- **"Exporter Excel"** — télécharge un `.xlsx` via PhpSpreadsheet.

### Page "Voir" membre (`GET /admin/members/{id}`) — nouvelle

- Bloc infos membre (lecture seule)
- Tableau historique des souscriptions : Saison / Type / Statut / Date de création — triées de la plus récente à la plus ancienne

### Formulaire création/édition

Nouveau bloc **"Cotisation saison courante"** avec deux champs :
- `Type de cotisation` (select : Terrains / Terrains + Tournois / Terrains + Tournois + Cours)
- `Statut paiement` (select : En attente / Payé)

À la création : crée le membre + la souscription dans le même handler (ou deux commandes enchaînées dans le contrôleur).
À l'édition : si une souscription courante existe → `UpdateMemberSubscriptionCommand` ; sinon → `CreateMemberSubscriptionCommand`.

### Suppression membre

Supprime le membre et en cascade toutes ses souscriptions (via `ON DELETE CASCADE` en SQL ou suppression explicite dans le handler).

---

## CSV Import / Export

### Format (en-têtes français)

```
id,Nom,Prénom,Téléphone,Email,Date de naissance,Type de cotisation,Statut paiement
```

**Valeurs acceptées pour "Type de cotisation" :**
- `Terrains`
- `Terrains + Tournois`
- `Terrains + Tournois + Cours`

**Valeurs acceptées pour "Statut paiement" :**
- `Payé` → PAID
- `En attente` → PENDING

### Logique d'import (approche C)

Pour chaque ligne :
1. Si colonne `id` présente et non vide → matching par ID
2. Sinon → fallback sur `Nom` + `Téléphone`
3. **Membre trouvé** → mise à jour des champs présents (nom, prénom, téléphone, email, date de naissance) + création ou mise à jour de la souscription saison courante
4. **Membre non trouvé** → création du membre + souscription saison courante si `Type de cotisation` fourni

Les colonnes `Type de cotisation`, `Statut paiement` et `id` sont optionnelles. Les erreurs sont collectées ligne par ligne (comportement actuel conservé).

### Export Excel

`GET /admin/members/export` — génère un `.xlsx` via `phpoffice/phpspreadsheet` avec :
- En-têtes en français (première ligne en gras)
- Colonnes : id, Nom, Prénom, Téléphone, Email, Date de naissance, Type de cotisation, Statut paiement, Saison
- Données : tous les membres, souscription filtrée sur la saison courante (valeurs "—" si absente)
- Nom du fichier : `membres-SAISON-COURANTE.xlsx`

---

## Logique des tournois

### Situation actuelle

`MatchMemberQuery(lastName, phone)` → booléen. Vérifie uniquement l'existence du membre.

### Nouvelle logique

`MatchMemberQuery` reçoit un paramètre supplémentaire : `requireTournamentAccess: bool` (défaut `true`).

Le handler lève une `DomainException` dans tous les cas de refus (au lieu de retourner `false`) :

1. Cherche le membre via `findByLastNameAndPhone()`
2. Si non trouvé → lève `DomainException('Ce tournoi est réservé aux membres du club.')`
3. Si trouvé et `requireTournamentAccess = true` → vérifie qu'il a une souscription `PAID` avec `type->hasTournamentAccess() === true` pour la saison courante
4. Si souscription insuffisante → lève `DomainException('Ce tournoi est réservé aux membres avec accès aux tournois (cotisation Terrains + Tournois ou Terrains + Tournois + Cours).')`
5. Sinon → retourne `true`

`RegisterHandler` se contente d'appeler le handler et laisse les exceptions remonter (plus de vérification du booléen retourné).

---

## Fichiers à créer / modifier

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
| `src/Member/Application/Query/MatchMemberHandler.php` | Modifier — logique tournois |
| `src/Member/Infrastructure/Doctrine/DoctrineMemberSubscriptionRepository.php` | Créer |
| `src/Member/Infrastructure/Http/Admin/MemberController.php` | Modifier — show, export, start season |
| `src/Member/Infrastructure/Http/Admin/Form/MemberType.php` | Modifier — ajouter bloc cotisation |
| `templates/admin/member/list.html.twig` | Modifier — colonnes + boutons |
| `templates/admin/member/show.html.twig` | Créer — détail + historique |
| `templates/admin/member/new.html.twig` | Modifier — bloc cotisation |
| `templates/admin/member/edit.html.twig` | Modifier — bloc cotisation |
| `migrations/VersionXXX.php` | Créer — table member_subscriptions |
| `tests/Member/Domain/MemberSubscriptionTest.php` | Créer |
| `tests/Member/Application/CreateMemberSubscriptionHandlerTest.php` | Créer |
| `tests/Member/Application/StartNewSeasonHandlerTest.php` | Créer |
| `tests/Member/Application/MatchMemberHandlerTest.php` | Modifier |
