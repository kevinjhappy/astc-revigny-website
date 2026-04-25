# Design Spec — Bloc "Actualités du club"

Date: 2026-04-26

## Contexte

Ajout d'un encart "Actualités du club" sur la page publique one-page du site ASTC Revigny, géré via le back office admin. Les actualités sont analogues à des posts Facebook : titre court + texte libre avec sauts de ligne et URLs.

---

## 1. Domaine — bounded context `News`

### Entité `Post` (`src/News/Domain/Post.php`)

| Champ | Type Doctrine | Type PHP | Remarque |
|---|---|---|---|
| `id` | `uuid` (custom type) | `Uuid` | Clé primaire |
| `title` | `string(150)` | `string` | Titre court obligatoire |
| `content` | `text` | `string` | Texte libre (sauts de ligne, URLs) |
| `status` | `string(20)` | `string` | VARCHAR, pas d'enumType Doctrine |
| `publishedAt` | `datetime_immutable`, nullable | `?DateTimeImmutable` | Null si DRAFT |

**Enum PHP `PostStatus`** (`src/News/Domain/PostStatus.php`) :
```php
enum PostStatus: string { case DRAFT = 'DRAFT'; case PUBLISHED = 'PUBLISHED'; }
```
Le mapping Doctrine est `type: 'string'` sans `enumType` — MySQL stocke un VARCHAR. La conversion se fait manuellement dans l'entité (`$this->status = $status->value` / `PostStatus::from($this->status)`). Ajouter un statut futur = nouvelle case PHP, zéro migration BDD.

**Méthodes métier :**
- `Post::create(Uuid, string $title, string $content): self` — crée en DRAFT, `publishedAt = null`
- `update(string $title, string $content): void`
- `publish(): void` — DRAFT → PUBLISHED, enregistre `publishedAt = new DateTimeImmutable()`
- `unpublish(): void` — PUBLISHED → DRAFT, remet `publishedAt = null`
- Getters : `id()`, `title()`, `content()`, `status(): PostStatus`, `publishedAt(): ?DateTimeImmutable`

### Interface `PostRepository` (`src/News/Domain/PostRepository.php`)

```php
interface PostRepository {
    public function save(Post $post): void;
    public function get(Uuid $id): ?Post;
    public function delete(Uuid $id): void;
    public function all(): array;                         // admin, tous statuts
    public function latestPublished(int $limit = 6): array; // front, PUBLISHED, DESC publishedAt
}
```

### CQRS — Commands & Handlers

Chaque opération admin est une paire Command/Handler dans `src/News/Application/Command/` :

| Command | Handler | Effet |
|---|---|---|
| `CreatePostCommand(title, content)` | `CreatePostHandler` | Crée Post en DRAFT |
| `UpdatePostCommand(id, title, content)` | `UpdatePostHandler` | Met à jour titre/contenu |
| `PublishPostCommand(id)` | `PublishPostHandler` | DRAFT → PUBLISHED |
| `UnpublishPostCommand(id)` | `UnpublishPostHandler` | PUBLISHED → DRAFT |
| `DeletePostCommand(id)` | `DeletePostHandler` | Supprime le post |

### Infrastructure Doctrine

- `src/News/Infrastructure/Doctrine/DoctrinePostRepository.php` — implémente `PostRepository`
- Table : `news_posts`
- Migration Doctrine à générer

---

## 2. Back office admin

### Controller (`src/News/Infrastructure/Http/Admin/PostController.php`)

Routes sous `#[Route('/admin/posts')]`, `#[IsGranted('ROLE_ADMIN')]` :

| Méthode | Route | Nom | Action |
|---|---|---|---|
| GET | `/admin/posts` | `admin_post_list` | Liste tous les posts |
| GET/POST | `/admin/posts/new` | `admin_post_new` | Création |
| GET/POST | `/admin/posts/{id}/edit` | `admin_post_edit` | Édition |
| POST | `/admin/posts/{id}/publish` | `admin_post_publish` | Publier (token CSRF) |
| POST | `/admin/posts/{id}/unpublish` | `admin_post_unpublish` | Dépublier (token CSRF) |
| POST | `/admin/posts/{id}/delete` | `admin_post_delete` | Supprimer (token CSRF) |

### Formulaire `PostType` (`src/News/Infrastructure/Http/Admin/Form/PostType.php`)

- `title` : `TextType`, constraints `NotBlank`, `Length(max: 150)`
- `content` : `TextareaType`, constraint `NotBlank`
- Pas de champ statut — transitions via boutons dédiés

### Templates admin

- `templates/admin/post/list.html.twig` — tableau avec colonnes Titre / Statut / Date publication / Actions (Voir, Publier/Dépublier, Supprimer)
- `templates/admin/post/new.html.twig`
- `templates/admin/post/edit.html.twig`

**Suppression :** bouton "Supprimer" déclenche une modale de confirmation JavaScript (inline, pas de dépendance externe) avant de soumettre le formulaire POST avec token CSRF.

### Navigation admin

Ajout d'un lien "Actualités" dans `templates/base_admin.html.twig` pointant vers `admin_post_list`.

---

## 3. Front public

### Placement

Ordre des sections après modification :

```
hero → club (Notre histoire) → news (Actualités) → tournaments (Nos tournois) → gallery → membership → contact
```

Justification : regroupe le contenu informatif du club (histoire + actualités) avant les sections actionnables (tournois, adhésion).

### Navigation publique

Ajout du lien `#news` entre "Le club" et "Tournois" dans `templates/public/_partials/nav.html.twig` (nav desktop + drawer mobile).

### Template `public/_partials/news.html.twig`

- `<section id="news" class="section section--alt" data-aos="fade-up">`
- Eyebrow : "Vie du club" / Titre : "Actualités"
- Grille CSS identique à `.tournaments-grid` (responsive, jusqu'à 3 colonnes)
- Jusqu'à 6 posts, triés du plus récent au plus ancien

**Card `.ncard`** (structure calquée sur `.tcard`) :
- Barre d'accent colorée `--primary` en haut
- Header : badge "Actualité" + date "Posté le 18 avril 2026" (format français long)
- Titre du post
- Corps : contenu texte formaté (voir ci-dessous)
- Pas de footer (aucune action visiteur)

**État vide :** composant `.empty-state` identique à la section tournois, icône + "Aucune actualité pour le moment."

### Formatage du contenu texte

Extension Twig custom `src/Shared/Infrastructure/Twig/AutoLinkExtension.php` :
- Filtre `autolink` : détecte les URLs `https?://...` par regex et les enveloppe dans `<a href="…" target="_blank" rel="noopener noreferrer">…</a>`
- Ordre d'application dans le template : `autolink` d'abord, puis `nl2br`, puis `|raw`
- Contenu contrôlé exclusivement par l'admin → usage de `|raw` acceptable

### HomeController

Injection de `PostRepository`, appel `latestPublished(6)` passé à la vue sous la clé `newsPosts`.

---

## 4. CSS

Nouvelles règles dans `assets/styles/app.css` pour `.ncard` (copie structurelle de `.tcard` avec accent `--primary` à la place des variantes de couleur par type). Pas de nouvelle dépendance JS.

---

## Fichiers à créer / modifier

### Nouveaux fichiers
- `src/News/Domain/Post.php`
- `src/News/Domain/PostStatus.php`
- `src/News/Domain/PostRepository.php`
- `src/News/Application/Command/CreatePostCommand.php` + Handler
- `src/News/Application/Command/UpdatePostCommand.php` + Handler
- `src/News/Application/Command/PublishPostCommand.php` + Handler
- `src/News/Application/Command/UnpublishPostCommand.php` + Handler
- `src/News/Application/Command/DeletePostCommand.php` + Handler
- `src/News/Infrastructure/Doctrine/DoctrinePostRepository.php`
- `src/News/Infrastructure/Http/Admin/Form/PostType.php`
- `src/News/Infrastructure/Http/Admin/PostController.php`
- `src/Shared/Infrastructure/Twig/AutoLinkExtension.php`
- `templates/admin/post/list.html.twig`
- `templates/admin/post/new.html.twig`
- `templates/admin/post/edit.html.twig`
- `templates/public/_partials/news.html.twig`
- Migration Doctrine

### Fichiers modifiés
- `templates/public/home.html.twig` — ajout de l'include news entre club et tournaments
- `templates/public/_partials/nav.html.twig` — ajout du lien #news
- `templates/base_admin.html.twig` — ajout du lien Actualités
- `src/Public/Infrastructure/Http/HomeController.php` — injection PostRepository + passage newsPosts
- `assets/styles/app.css` — styles `.ncard`
