# Design — Export Excel des inscriptions aux tournois ouverts

**Date :** 2026-05-27

## Contexte

Le back-office doit permettre d'exporter en Excel les inscriptions aux tournois ouverts (statut PUBLISHED). L'export doit inclure les informations de chaque inscrit et le tournoi concerné.

## Périmètre fonctionnel

- **Tournois couverts :** statut PUBLISHED uniquement
- **Statuts d'inscription inclus :** CONFIRMED, PENDING, WAITING_LIST (CANCELLED exclu)
- **Données par ligne :** Tournoi, Nom, Prénom, Téléphone, Email, Statut, Date d'inscription
- **Modes d'export :**
  - Tous les tournois PUBLISHED → un onglet par tournoi, fichier `inscriptions-<YYYY-MM-DD>.xlsx`
  - Un tournoi spécifique (`?tournament=<uuid>`) → une seule feuille, fichier `inscriptions-<nom-tournoi>.xlsx`

## Architecture

**Approche choisie : ajout dans `RegistrationController`**, cohérent avec le pattern de `MemberController::export()` qui utilise PhpSpreadsheet (déjà installé).

Aucune query/handler dédié nécessaire : `RegistrationRepository::byTournament()` et `TournamentRepository::published()` couvrent le besoin.

## Nouvelle route

```
GET /admin/registrations/export
  ?tournament=all     → tous les tournois PUBLISHED, un onglet par tournoi
  ?tournament=<uuid>  → un tournoi spécifique
```

Sans paramètre `tournament`, comportement identique à `all`.

## Modifications fichiers

### `src/Registration/Infrastructure/Http/Admin/RegistrationController.php`
- Nouvelle action `export(Request, RegistrationRepository, TournamentRepository)`
- Mode `all` : itère `$tournamentRepo->published()`, pour chaque tournoi crée un onglet, filtre les registrations par statut (CONFIRMED, PENDING, WAITING_LIST)
- Mode `<uuid>` : vérifie que le tournoi est PUBLISHED (sinon 404), une seule feuille
- Réponse `StreamedResponse` avec header `Content-Disposition` via `HeaderUtils::makeDisposition`

### `templates/admin/registration/list.html.twig`
- Bouton "Exporter Excel" à côté des filtres existants
- Si un tournoi est sélectionné dans le filtre : `?tournament=<uuid>` ; sinon `?tournament=all`

### `templates/admin/tournament/list.html.twig`
- Bouton/lien "Exporter inscriptions" sur chaque ligne de tournoi PUBLISHED, pointant vers `/admin/registrations/export?tournament=<uuid>`

## Colonnes Excel

| Colonne | Source |
|---|---|
| Nom | `Registration::lastName()` |
| Prénom | `Registration::firstName()` |
| Téléphone | `Registration::phone()` |
| Email | `Registration::email()` |
| Statut | `Registration::status()->value` |
| Date d'inscription | `Registration::registeredAt()->format('d/m/Y H:i')` |

En mode "tous", une colonne **Tournoi** est ajoutée en première position sur la feuille récapitulative (ou chaque onglet porte le nom du tournoi).

## Gestion des cas limites

- Aucun tournoi PUBLISHED → fichier Excel vide avec message dans la première cellule
- Tournoi PUBLISHED sans inscription → onglet créé avec uniquement les en-têtes
- UUID inconnu ou tournoi non-PUBLISHED → `404 Not Found`

## Ce qui ne change pas

- Pas de nouveau contexte DDD, pas de nouveau service
- Pas de modification du domaine (entités, repositories interfaces)
- Pas d'enrichissement avec le contexte Member (les données de Registration suffisent)
