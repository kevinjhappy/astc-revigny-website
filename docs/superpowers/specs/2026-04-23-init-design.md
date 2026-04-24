# Spec — Initialisation du projet ASTC Revigny

**Date :** 2026-04-23
**Statut :** Validé

---

## 1. Contexte

Site web pour l'ASTC Revigny (AS Tennis Club Revigny), club de tennis local basé à Revigny-sur-Ornain (Meuse, 55).

Deux espaces distincts :
- **Page publique** — one-page, UX soignée, présentation du club et inscriptions aux tournois
- **Back office** — gestion des tournois, membres et inscriptions (authentifié)

Développement local sous Docker. Pas de contrainte de déploiement à ce stade.

---

## 2. Stack technique

| Composant | Choix |
|---|---|
| Langage | PHP 8.3 |
| Framework | Symfony 7 |
| ORM | Doctrine |
| Base de données | MySQL 8 |
| Templates | Twig |
| Animations front | GSAP, Swiper.js, AOS |
| Auth | Symfony Security |
| Emails dev | Mailpit |
| Infra locale | Docker Compose |

**Conteneurs Docker :** `php-fpm`, `nginx`, `mysql`, `mailpit`

---

## 3. Architecture — DDD Bounded Contexts

```
src/
├── Tournament/
│   ├── Domain/          # Entités, Value Objects, interfaces Repository, Domain Events
│   ├── Application/     # Commands, Queries, Handlers (CQRS léger)
│   └── Infrastructure/  # Repositories Doctrine, Controllers, templates Twig
├── Member/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
├── Registration/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
├── Security/
│   ├── Domain/          # AdminUser
│   └── Infrastructure/  # Controller login, UserProvider
└── Shared/
    └── Domain/          # Value Objects communs (PhoneNumber, Email, Uuid)
```

**Principes :**
- Les contextes communiquent via événements Symfony (`EventDispatcher`), jamais via dépendances directes entre entités Doctrine cross-context.
- Les références cross-context se font par `uuid` (ex : `Registration::tournamentId`), pas par objet hydraté.
- Les Value Objects communs vivent dans `Shared/Domain/`.

**Deux entrées applicatives :**
- `/` → page publique, aucune authentification requise
- `/admin` → back office, `ROLE_ADMIN` requis

---

## 4. Modèle de données

### Member
```
id:         uuid (PK)
firstName:  string
lastName:   string
phone:      PhoneNumber (VO)
email:      Email|null (VO)
```

### Tournament
```
id:              uuid (PK)
name:            string
startDate:       DateTimeImmutable
endDate:         DateTimeImmutable
type:            enum(OPEN, MEMBERS_ONLY)
maxParticipants: int
status:          enum(DRAFT, PUBLISHED, CLOSED)
description:     string|null
```

### Registration
```
id:             uuid (PK)
tournamentId:   uuid (référence, pas FK cross-context)
firstName:      string
lastName:       string
phone:          PhoneNumber (VO)
email:          Email|null (VO)
status:         enum(PENDING, CONFIRMED, CANCELLED, WAITING_LIST)
registeredAt:   DateTimeImmutable
```

**Logique liste d'attente :** si le nombre d'inscriptions `CONFIRMED` atteint `Tournament::maxParticipants`, les nouvelles inscriptions sont créées en `WAITING_LIST`. Lorsqu'une inscription `CONFIRMED` est annulée, la première `WAITING_LIST` (par `registeredAt` ASC) passe en `PENDING` pour que l'admin confirme.

### AdminUser
```
id:       uuid (PK)
email:    string (identifiant de connexion)
password: string (hashé, Symfony PasswordHasher)
roles:    array (["ROLE_ADMIN"])
```

---

## 5. Page publique — one-page

### Sections (ordre de scroll)
1. **Nav** — sticky, logo, ancres : Le club / Tournois / Galerie / Contact, CTA "S'inscrire"
2. **Hero** — photo bannière courts en plein écran, titre, deux boutons CTA
3. **Le club** — texte de présentation, stats (courts, membres, tournois/an), photo
4. **Tournois** — cards des tournois publiés, badge OPEN/MEMBRES, places restantes, bouton "S'inscrire"
5. **Galerie** — grille photos avec Swiper sur mobile
6. **Contact** — adresse, lien Facebook, carte Google Maps embed
7. **Footer**

### Charte visuelle
| Rôle | Valeur |
|---|---|
| Primaire (nav, titres) | `#1A2B6D` |
| Accent (CTA, labels, dividers) | `#E8721A` |
| Fond clair | `#FFFFFF` / `#F7F8FC` |
| Fond sombre (galerie) | `#1A2B6D` |
| Police titres | Georgia (serif) |
| Police corps | Segoe UI / système |

### Animations
- Hero : parallax au scroll (GSAP)
- Sections : entrée au scroll (AOS `fade-up`)
- Cards tournois : hover élévation (GSAP)
- Galerie : carrousel Swiper (`coverflow` sur mobile)

### Formulaire d'inscription (modale)
- Champs : Nom, Prénom, Téléphone (obligatoires) + Email (optionnel)
- Validation : Symfony Validator côté serveur
- Tournoi `MEMBERS_ONLY` : vérification que nom+téléphone correspondent à un `Member` existant
- Retour : confirmation visuelle dans la modale sans rechargement de page (fetch API)
- Tournoi complet : inscription créée en `WAITING_LIST`, message informatif affiché

---

## 6. Back office

### Routes
```
GET  /admin/login
POST /admin/login
GET  /admin/dashboard
GET  /admin/tournaments
GET  /admin/tournaments/new
POST /admin/tournaments/new
GET  /admin/tournaments/{id}
PUT  /admin/tournaments/{id}
GET  /admin/members
GET  /admin/members/new
POST /admin/members/new
GET  /admin/members/{id}/edit
PUT  /admin/members/{id}
DELETE /admin/members/{id}
GET  /admin/registrations
DELETE /admin/registrations/{id}
PATCH  /admin/registrations/{id}/status
```

### Pages
- **Dashboard** — stats : tournois actifs, total inscrits, membres, dernières inscriptions récentes
- **Tournois** — liste + statut, créer/éditer, publier/clôturer, voir les inscrits par tournoi
- **Membres** — CRUD complet, recherche par nom/téléphone
- **Inscriptions** — vue transversale de toutes les inscriptions, filtrable par tournoi et statut, actions : confirmer / annuler / supprimer

### Style
- Sobre et fonctionnel, même charte (bleu marine + orange)
- Twig + formulaires Symfony natifs (pas d'EasyAdmin)
- Pas d'animations front, priorité à l'ergonomie et la lisibilité

### Gestion des admins
- Plusieurs admins avec le même rôle `ROLE_ADMIN`
- Création via commande Symfony CLI (`app:create-admin email password`)

---

## 7. Docker Compose — services

```yaml
services:
  php:    # php-fpm 8.3, image custom avec extensions Symfony
  nginx:  # reverse proxy → php-fpm, port 8080:80
  mysql:  # MySQL 8, volume persistant
  mailpit # Capture emails dev, UI port 8025
```

---

## 8. Ce qui est hors scope (v1)

- Paiement des droits d'inscription
- Espace membre avec login
- Système de notification email automatique aux inscrits
- Gestion des résultats de tournois
- Multilingue
