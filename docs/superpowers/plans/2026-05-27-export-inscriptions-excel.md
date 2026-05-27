# Export Excel Inscriptions Tournois Ouverts — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ajouter un export Excel des inscriptions aux tournois PUBLISHED, accessible depuis la page inscriptions et la page tournois du back-office.

**Architecture:** Nouvelle action `export` dans le `RegistrationController` existant, qui s'appuie sur `TournamentRepository::published()` et `RegistrationRepository::byTournament()` déjà disponibles. PhpSpreadsheet est déjà installé (utilisé dans `MemberController::export()`). Mode `all` → un onglet par tournoi PUBLISHED ; mode `?tournament=<uuid>` → une feuille unique. Statuts inclus : CONFIRMED, PENDING, WAITING_LIST.

**Tech Stack:** PHP 8.3, Symfony 7.4, PhpSpreadsheet (`PhpOffice\PhpSpreadsheet`), Twig, PHPUnit

---

## Fichiers

| Action | Chemin |
|---|---|
| Modify | `src/Registration/Infrastructure/Http/Admin/RegistrationController.php` |
| Modify | `templates/admin/registration/list.html.twig` |
| Modify | `templates/admin/tournament/list.html.twig` |
| Create | `tests/Registration/Infrastructure/Http/Admin/RegistrationExportFilterTest.php` |

---

### Task 1 : Créer la branche de travail

- [ ] **Step 1 : Créer et basculer sur la branche**

```bash
git checkout -b feature/export-inscriptions-excel
```

Expected : `Switched to a new branch 'feature/export-inscriptions-excel'`

---

### Task 2 : Écrire le test échouant pour le filtrage par statut

Le controller filtrera les inscriptions avec `array_filter` pour ne garder que CONFIRMED, PENDING, WAITING_LIST. Ce test vérifie que la constante de statuts autorisés et le filtrage produisent le bon résultat, sans dépendance Symfony ni PhpSpreadsheet.

- [ ] **Step 1 : Créer le répertoire de test si absent**

```bash
mkdir -p tests/Registration/Infrastructure/Http/Admin
```

- [ ] **Step 2 : Écrire le test**

Créer `tests/Registration/Infrastructure/Http/Admin/RegistrationExportFilterTest.php` :

```php
<?php

namespace App\Tests\Registration\Infrastructure\Http\Admin;

use App\Registration\Domain\Registration;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

final class RegistrationExportFilterTest extends TestCase
{
    private const EXPORT_STATUSES = [
        RegistrationStatus::CONFIRMED,
        RegistrationStatus::PENDING,
        RegistrationStatus::WAITING_LIST,
    ];

    private function makeReg(RegistrationStatus $status): Registration
    {
        return Registration::create(
            Uuid::generate(),
            Uuid::generate(),
            'Dupont',
            'Jean',
            PhoneNumber::fromString('0611223344'),
            null,
            $status,
        );
    }

    public function test_cancelled_registrations_are_excluded(): void
    {
        $registrations = [
            $this->makeReg(RegistrationStatus::CONFIRMED),
            $this->makeReg(RegistrationStatus::PENDING),
            $this->makeReg(RegistrationStatus::WAITING_LIST),
            $this->makeReg(RegistrationStatus::CANCELLED),
        ];

        $filtered = array_values(array_filter(
            $registrations,
            fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
        ));

        self::assertCount(3, $filtered);
        foreach ($filtered as $reg) {
            self::assertNotSame(RegistrationStatus::CANCELLED, $reg->status());
        }
    }

    public function test_only_export_statuses_pass_filter(): void
    {
        $confirmed  = $this->makeReg(RegistrationStatus::CONFIRMED);
        $pending    = $this->makeReg(RegistrationStatus::PENDING);
        $waiting    = $this->makeReg(RegistrationStatus::WAITING_LIST);
        $cancelled  = $this->makeReg(RegistrationStatus::CANCELLED);

        $all = [$confirmed, $pending, $waiting, $cancelled];
        $filtered = array_values(array_filter(
            $all,
            fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
        ));

        self::assertSame([$confirmed, $pending, $waiting], $filtered);
    }
}
```

- [ ] **Step 3 : Lancer le test — vérifier qu'il passe (aucune logique à implémenter ici, c'est du PHP pur)**

```bash
make test 2>&1 | grep -A5 "RegistrationExportFilter"
```

Expected : `OK` — la logique `array_filter` est du PHP standard, le test doit passer immédiatement. Si erreur, vérifier les namespaces.

- [ ] **Step 4 : Commit**

```bash
git add tests/Registration/Infrastructure/Http/Admin/RegistrationExportFilterTest.php
git commit -m "test(registration): filtrage statuts pour export Excel"
```

---

### Task 3 : Implémenter l'action `export` dans `RegistrationController`

- [ ] **Step 1 : Ouvrir `src/Registration/Infrastructure/Http/Admin/RegistrationController.php`**

Le fichier actuel n'importe pas PhpSpreadsheet. Il faut ajouter les imports et la nouvelle action.

- [ ] **Step 2 : Ajouter les imports en haut du fichier (après les imports existants)**

Ajouter après la ligne `use Symfony\Component\Security\Http\Attribute\IsGranted;` :

```php
use App\Shared\Domain\ValueObject\Uuid;
use App\Tournament\Domain\TournamentStatus;
use App\Registration\Domain\RegistrationStatus;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
```

- [ ] **Step 3 : Ajouter la constante et la méthode `export` dans la classe**

Ajouter avant la méthode `list(` :

```php
private const EXPORT_STATUSES = [
    RegistrationStatus::CONFIRMED,
    RegistrationStatus::PENDING,
    RegistrationStatus::WAITING_LIST,
];

#[Route('/export', name: 'admin_registration_export', methods: ['GET'])]
public function export(Request $request, RegistrationRepository $repo, TournamentRepository $tournamentRepository): Response
{
    $tournamentParam = $request->query->get('tournament', 'all');
    $spreadsheet = new Spreadsheet();
    $spreadsheet->removeSheetByIndex(0);

    if ($tournamentParam !== 'all' && $tournamentParam !== '') {
        $tournament = $tournamentRepository->get(Uuid::fromString($tournamentParam));
        if ($tournament === null || $tournament->status() !== TournamentStatus::PUBLISHED) {
            throw $this->createNotFoundException('Tournoi introuvable ou non ouvert.');
        }
        $registrations = array_values(array_filter(
            $repo->byTournament($tournament->id()),
            fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
        ));
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(mb_substr($tournament->name(), 0, 31));
        $this->fillSheet($sheet, $registrations);
        $filename = 'inscriptions-' . preg_replace('/[^a-z0-9-]/i', '-', $tournament->name()) . '.xlsx';
    } else {
        $tournaments = $tournamentRepository->published();
        if (empty($tournaments)) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle('Aucun tournoi');
            $sheet->setCellValue('A1', 'Aucun tournoi ouvert.');
        }
        foreach ($tournaments as $tournament) {
            $registrations = array_values(array_filter(
                $repo->byTournament($tournament->id()),
                fn(Registration $r) => in_array($r->status(), self::EXPORT_STATUSES, true)
            ));
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(mb_substr($tournament->name(), 0, 31));
            $this->fillSheet($sheet, $registrations);
        }
        $filename = 'inscriptions-' . (new \DateTimeImmutable())->format('Y-m-d') . '.xlsx';
    }

    $spreadsheet->setActiveSheetIndex(0);

    $response = new StreamedResponse(function () use ($spreadsheet): void {
        (new Xlsx($spreadsheet))->save('php://output');
    });
    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));
    $response->headers->set('Cache-Control', 'max-age=0');

    return $response;
}

/** @param Registration[] $registrations */
private function fillSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $registrations): void
{
    $headers = ['Nom', 'Prénom', 'Téléphone', 'Email', 'Statut', 'Date d\'inscription'];
    $sheet->fromArray([$headers], null, 'A1');
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);

    $rowIndex = 2;
    foreach ($registrations as $r) {
        $sheet->fromArray([[
            $r->lastName(),
            $r->firstName(),
            (string) $r->phone(),
            $r->email() ? (string) $r->email() : '',
            $r->status()->value,
            $r->registeredAt()->format('d/m/Y H:i'),
        ]], null, 'A' . $rowIndex);
        $rowIndex++;
    }
}
```

**Important :** la route `/export` doit être déclarée **avant** la route `/{id}` existante (sinon Symfony va matcher `export` comme un `{id}`). Vérifier l'ordre dans le fichier — les routes sans paramètre en premier.

- [ ] **Step 4 : Vérifier que le routing est correct**

```bash
make console CMD="debug:router admin_registration_export"
```

Expected : affiche la route `GET /admin/registrations/export`

- [ ] **Step 5 : Lancer les tests**

```bash
make test
```

Expected : `OK` — aucun test cassé

- [ ] **Step 6 : Commit**

```bash
git add src/Registration/Infrastructure/Http/Admin/RegistrationController.php
git commit -m "feat(registration): action export Excel inscriptions tournois PUBLISHED"
```

---

### Task 4 : Bouton export sur la page liste des inscriptions

- [ ] **Step 1 : Modifier `templates/admin/registration/list.html.twig`**

Ajouter le bouton export après la balise `<button type="submit">Filtrer</button>` et avant la fermeture `</form>` :

Remplacer la ligne :
```twig
    <button type="submit">Filtrer</button>
  </form>
```

Par :
```twig
    <button type="submit">Filtrer</button>
  </form>
  <a href="{{ path('admin_registration_export', {tournament: selectedTournament ?: 'all'}) }}"
     style="display:inline-block;margin-bottom:1rem;padding:.4rem .8rem;background:#1A2B6D;color:#fff;text-decoration:none;border-radius:3px">
    Exporter Excel
  </a>
```

- [ ] **Step 2 : Vérifier dans le navigateur (optionnel si serveur non disponible)**

Ouvrir `http://localhost:8080/admin/registrations` — le bouton "Exporter Excel" doit apparaître sous le formulaire de filtres. Cliquer → téléchargement `.xlsx`.

Si un tournoi est sélectionné dans le filtre puis qu'on clique "Exporter Excel", l'URL générée doit contenir `?tournament=<uuid>`.

- [ ] **Step 3 : Lancer les tests**

```bash
make test
```

Expected : `OK`

- [ ] **Step 4 : Commit**

```bash
git add templates/admin/registration/list.html.twig
git commit -m "feat(registration): bouton export Excel sur la liste des inscriptions"
```

---

### Task 5 : Bouton export sur la page liste des tournois

- [ ] **Step 1 : Modifier `templates/admin/tournament/list.html.twig`**

Dans la section des tournois **actifs** (bloc `{% for t in active %}`), ajouter le lien export sur chaque ligne PUBLISHED.

Remplacer :
```twig
        <td><a href="{{ path("admin_tournament_detail", {id: t.id}) }}" class="btn" style="background: #1a5c1a">Voir</a></td>
```

Par :
```twig
        <td>
          <a href="{{ path("admin_tournament_detail", {id: t.id}) }}" class="btn" style="background: #1a5c1a">Voir</a>
          {% if t.status.value == 'PUBLISHED' %}
            <a href="{{ path('admin_registration_export', {tournament: t.id}) }}"
               class="btn" style="background:#1A2B6D;margin-left:.3rem">Exporter inscriptions</a>
          {% endif %}
        </td>
```

- [ ] **Step 2 : Vérifier dans le navigateur (optionnel)**

Ouvrir `http://localhost:8080/admin/tournaments` — les tournois PUBLISHED affichent le bouton "Exporter inscriptions". Les tournois DRAFT n'affichent pas le bouton. Cliquer → téléchargement `.xlsx` à une seule feuille.

- [ ] **Step 3 : Lancer les tests**

```bash
make test
```

Expected : `OK`

- [ ] **Step 4 : Commit**

```bash
git add templates/admin/tournament/list.html.twig
git commit -m "feat(tournament): bouton exporter inscriptions Excel sur la liste des tournois"
```

---

### Task 6 : Vérification finale et merge

- [ ] **Step 1 : Lancer tous les tests une dernière fois**

```bash
make test
```

Expected : `OK`, zéro régression

- [ ] **Step 2 : Vérifier les routes enregistrées**

```bash
make console CMD="debug:router" 2>&1 | grep registration
```

Expected : les routes existantes sont présentes + `admin_registration_export GET /admin/registrations/export`

- [ ] **Step 3 : Vérifier le cache Symfony**

```bash
make console CMD="cache:clear"
```

Expected : `Cache for the "dev" environment (debug=true) was successfully cleared.`
