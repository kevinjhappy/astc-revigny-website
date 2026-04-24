# Graph Report - src  (2026-04-24)

## Corpus Check
- Corpus is ~4,909 words - fits in a single context window. You may not need a graph.

## Summary
- 312 nodes · 375 edges · 40 communities detected
- Extraction: 66% EXTRACTED · 34% INFERRED · 0% AMBIGUOUS · INFERRED: 126 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Registration Lifecycle Handlers|Registration Lifecycle Handlers]]
- [[_COMMUNITY_Tournament & Shared Infrastructure|Tournament & Shared Infrastructure]]
- [[_COMMUNITY_Member Domain & Value Objects|Member Domain & Value Objects]]
- [[_COMMUNITY_Public Registration Flow|Public Registration Flow]]
- [[_COMMUNITY_Doctrine Member Repository|Doctrine Member Repository]]
- [[_COMMUNITY_Cancel & Waiting List Logic|Cancel & Waiting List Logic]]
- [[_COMMUNITY_Admin Security|Admin Security]]
- [[_COMMUNITY_Delete Handlers|Delete Handlers]]
- [[_COMMUNITY_Admin Dashboard & Tournament Repo|Admin Dashboard & Tournament Repo]]
- [[_COMMUNITY_Registration Repository Interface|Registration Repository Interface]]
- [[_COMMUNITY_Tournament Repository Interface|Tournament Repository Interface]]
- [[_COMMUNITY_Member Repository Interface|Member Repository Interface]]
- [[_COMMUNITY_Login Controller|Login Controller]]
- [[_COMMUNITY_Create Member Command|Create Member Command]]
- [[_COMMUNITY_Delete Member Command|Delete Member Command]]
- [[_COMMUNITY_Update Member Command|Update Member Command]]
- [[_COMMUNITY_Match Member Query|Match Member Query]]
- [[_COMMUNITY_Member Form Type|Member Form Type]]
- [[_COMMUNITY_Reset Registration Handler|Reset Registration Handler]]
- [[_COMMUNITY_Register Result DTO|Register Result DTO]]
- [[_COMMUNITY_Delete Registration Command|Delete Registration Command]]
- [[_COMMUNITY_Confirm Registration Command|Confirm Registration Command]]
- [[_COMMUNITY_Cancel Registration Command|Cancel Registration Command]]
- [[_COMMUNITY_Register Command|Register Command]]
- [[_COMMUNITY_Reset Registration Command|Reset Registration Command]]
- [[_COMMUNITY_Public Registration API|Public Registration API]]
- [[_COMMUNITY_Admin User Repository|Admin User Repository]]
- [[_COMMUNITY_Publish Tournament Command|Publish Tournament Command]]
- [[_COMMUNITY_Close Tournament Command|Close Tournament Command]]
- [[_COMMUNITY_Create Tournament Command|Create Tournament Command]]
- [[_COMMUNITY_Reopen Tournament Command|Reopen Tournament Command]]
- [[_COMMUNITY_Unpublish Tournament Command|Unpublish Tournament Command]]
- [[_COMMUNITY_Update Tournament Command|Update Tournament Command]]
- [[_COMMUNITY_Publish Tournament Handler|Publish Tournament Handler]]
- [[_COMMUNITY_Unpublish Tournament Handler|Unpublish Tournament Handler]]
- [[_COMMUNITY_Contact API Controller|Contact API Controller]]
- [[_COMMUNITY_App Kernel|App Kernel]]
- [[_COMMUNITY_Registration Status Enum|Registration Status Enum]]
- [[_COMMUNITY_Tournament Status Enum|Tournament Status Enum]]
- [[_COMMUNITY_Tournament Form Type|Tournament Form Type]]

## God Nodes (most connected - your core abstractions)
1. `Uuid` - 25 edges
2. `Tournament` - 17 edges
3. `Registration` - 16 edges
4. `Member` - 11 edges
5. `PhoneNumber` - 10 edges
6. `DoctrineRegistrationRepository` - 9 edges
7. `AdminUser` - 9 edges
8. `TournamentController` - 9 edges
9. `Email` - 9 edges
10. `DoctrineTournamentRepository` - 8 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Communities

### Community 0 - "Registration Lifecycle Handlers"
Cohesion: 0.08
Nodes (6): CloseTournamentHandler, ConfirmRegistrationHandler, MemberController, RegistrationController, ReopenTournamentHandler, TournamentController

### Community 1 - "Tournament & Shared Infrastructure"
Cohesion: 0.07
Nodes (6): CreateTournamentHandler, TournamentType, UpdateMemberHandler, UpdateTournamentHandler, Uuid, UuidType

### Community 2 - "Member Domain & Value Objects"
Cohesion: 0.08
Nodes (4): CreateMemberHandler, Email, EmailType, Member

### Community 3 - "Public Registration Flow"
Cohesion: 0.13
Nodes (3): HomeController, RegisterHandler, Tournament

### Community 4 - "Doctrine Member Repository"
Cohesion: 0.09
Nodes (4): DoctrineMemberRepository, MatchMemberHandler, PhoneNumber, PhoneNumberType

### Community 5 - "Cancel & Waiting List Logic"
Cohesion: 0.1
Nodes (2): CancelRegistrationHandler, Registration

### Community 6 - "Admin Security"
Cohesion: 0.1
Nodes (3): AdminUser, CreateAdminCommand, DoctrineAdminUserRepository

### Community 7 - "Delete Handlers"
Cohesion: 0.12
Nodes (3): DeleteMemberHandler, DeleteRegistrationHandler, DoctrineRegistrationRepository

### Community 8 - "Admin Dashboard & Tournament Repo"
Cohesion: 0.18
Nodes (2): DashboardController, DoctrineTournamentRepository

### Community 9 - "Registration Repository Interface"
Cohesion: 0.25
Nodes (0): 

### Community 10 - "Tournament Repository Interface"
Cohesion: 0.29
Nodes (0): 

### Community 11 - "Member Repository Interface"
Cohesion: 0.33
Nodes (0): 

### Community 12 - "Login Controller"
Cohesion: 0.5
Nodes (1): LoginController

### Community 13 - "Create Member Command"
Cohesion: 0.67
Nodes (1): CreateMemberCommand

### Community 14 - "Delete Member Command"
Cohesion: 0.67
Nodes (1): DeleteMemberCommand

### Community 15 - "Update Member Command"
Cohesion: 0.67
Nodes (1): UpdateMemberCommand

### Community 16 - "Match Member Query"
Cohesion: 0.67
Nodes (1): MatchMemberQuery

### Community 17 - "Member Form Type"
Cohesion: 0.67
Nodes (1): MemberType

### Community 18 - "Reset Registration Handler"
Cohesion: 0.67
Nodes (1): ResetRegistrationHandler

### Community 19 - "Register Result DTO"
Cohesion: 0.67
Nodes (1): RegisterResult

### Community 20 - "Delete Registration Command"
Cohesion: 0.67
Nodes (1): DeleteRegistrationCommand

### Community 21 - "Confirm Registration Command"
Cohesion: 0.67
Nodes (1): ConfirmRegistrationCommand

### Community 22 - "Cancel Registration Command"
Cohesion: 0.67
Nodes (1): CancelRegistrationCommand

### Community 23 - "Register Command"
Cohesion: 0.67
Nodes (1): RegisterCommand

### Community 24 - "Reset Registration Command"
Cohesion: 0.67
Nodes (1): ResetRegistrationCommand

### Community 25 - "Public Registration API"
Cohesion: 0.67
Nodes (1): RegistrationApiController

### Community 26 - "Admin User Repository"
Cohesion: 0.67
Nodes (0): 

### Community 27 - "Publish Tournament Command"
Cohesion: 0.67
Nodes (1): PublishTournamentCommand

### Community 28 - "Close Tournament Command"
Cohesion: 0.67
Nodes (1): CloseTournamentCommand

### Community 29 - "Create Tournament Command"
Cohesion: 0.67
Nodes (1): CreateTournamentCommand

### Community 30 - "Reopen Tournament Command"
Cohesion: 0.67
Nodes (1): ReopenTournamentCommand

### Community 31 - "Unpublish Tournament Command"
Cohesion: 0.67
Nodes (1): UnpublishTournamentCommand

### Community 32 - "Update Tournament Command"
Cohesion: 0.67
Nodes (1): UpdateTournamentCommand

### Community 33 - "Publish Tournament Handler"
Cohesion: 0.67
Nodes (1): PublishTournamentHandler

### Community 34 - "Unpublish Tournament Handler"
Cohesion: 0.67
Nodes (1): UnpublishTournamentHandler

### Community 35 - "Contact API Controller"
Cohesion: 0.67
Nodes (1): ContactApiController

### Community 36 - "App Kernel"
Cohesion: 1.0
Nodes (1): Kernel

### Community 37 - "Registration Status Enum"
Cohesion: 1.0
Nodes (0): 

### Community 38 - "Tournament Status Enum"
Cohesion: 1.0
Nodes (0): 

### Community 39 - "Tournament Form Type"
Cohesion: 1.0
Nodes (0): 

## Knowledge Gaps
- **1 isolated node(s):** `Kernel`
  These have ≤1 connection - possible missing edges or undocumented components.
- **Thin community `App Kernel`** (2 nodes): `Kernel`, `Kernel.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Registration Status Enum`** (1 nodes): `RegistrationStatus.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Tournament Status Enum`** (1 nodes): `TournamentStatus.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.
- **Thin community `Tournament Form Type`** (1 nodes): `TournamentType.php`
  Too small to be a meaningful cluster - may be noise or needs more connections extracted.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Uuid` connect `Tournament & Shared Infrastructure` to `Registration Lifecycle Handlers`, `Member Domain & Value Objects`, `Public Registration Flow`, `Cancel & Waiting List Logic`, `Admin Security`, `Delete Handlers`?**
  _High betweenness centrality (0.169) - this node is a cross-community bridge._
- **Why does `PhoneNumber` connect `Doctrine Member Repository` to `Tournament & Shared Infrastructure`, `Member Domain & Value Objects`, `Public Registration Flow`?**
  _High betweenness centrality (0.076) - this node is a cross-community bridge._
- **Why does `Email` connect `Member Domain & Value Objects` to `Tournament & Shared Infrastructure`, `Public Registration Flow`, `Cancel & Waiting List Logic`?**
  _High betweenness centrality (0.048) - this node is a cross-community bridge._
- **Are the 19 inferred relationships involving `Uuid` (e.g. with `.__invoke()` and `.__invoke()`) actually correct?**
  _`Uuid` has 19 INFERRED edges - model-reasoned connections that need verification._
- **Are the 5 inferred relationships involving `PhoneNumber` (e.g. with `.__invoke()` and `.__invoke()`) actually correct?**
  _`PhoneNumber` has 5 INFERRED edges - model-reasoned connections that need verification._
- **What connects `Kernel` to the rest of the system?**
  _1 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Registration Lifecycle Handlers` be split into smaller, more focused modules?**
  _Cohesion score 0.08 - nodes in this community are weakly interconnected._