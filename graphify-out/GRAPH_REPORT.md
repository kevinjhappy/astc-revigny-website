# Graph Report - .  (2026-05-21)

## Corpus Check
- 109 files · ~9,433 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 561 nodes · 588 edges · 88 communities (18 shown, 70 thin omitted)
- Extraction: 83% EXTRACTED · 17% INFERRED · 0% AMBIGUOUS · INFERRED: 98 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Member Command Handlers|Member Command Handlers]]
- [[_COMMUNITY_Registration Tests|Registration Tests]]
- [[_COMMUNITY_Member Matching Tests|Member Matching Tests]]
- [[_COMMUNITY_Registration Domain Core|Registration Domain Core]]
- [[_COMMUNITY_PostBlog Management|Post/Blog Management]]
- [[_COMMUNITY_UUID Value Object|UUID Value Object]]
- [[_COMMUNITY_Member Subscription Repository|Member Subscription Repository]]
- [[_COMMUNITY_Tournament CQRS Handlers|Tournament CQRS Handlers]]
- [[_COMMUNITY_Member Admin Controller|Member Admin Controller]]
- [[_COMMUNITY_Tournament Admin Controller|Tournament Admin Controller]]
- [[_COMMUNITY_Registration Repository|Registration Repository]]
- [[_COMMUNITY_Admin User Auth|Admin User Auth]]
- [[_COMMUNITY_AutoLink Twig Tests|AutoLink Twig Tests]]
- [[_COMMUNITY_Post Admin Controller|Post Admin Controller]]
- [[_COMMUNITY_Tournament Repository|Tournament Repository]]
- [[_COMMUNITY_Member Repository|Member Repository]]
- [[_COMMUNITY_Post Repository|Post Repository]]
- [[_COMMUNITY_Season Helper Tests|Season Helper Tests]]
- [[_COMMUNITY_Registration Controller|Registration Controller]]
- [[_COMMUNITY_Admin User Repository|Admin User Repository]]
- [[_COMMUNITY_Email Doctrine Type|Email Doctrine Type]]
- [[_COMMUNITY_UUID Doctrine Type|UUID Doctrine Type]]
- [[_COMMUNITY_Create Admin CLI|Create Admin CLI]]
- [[_COMMUNITY_Member Subscription Tests|Member Subscription Tests]]
- [[_COMMUNITY_Season Helper Utility|Season Helper Utility]]
- [[_COMMUNITY_Email Value Object|Email Value Object]]
- [[_COMMUNITY_Email Value Object Tests|Email Value Object Tests]]
- [[_COMMUNITY_Close Tournament Handler|Close Tournament Handler]]
- [[_COMMUNITY_Reopen Tournament Handler|Reopen Tournament Handler]]
- [[_COMMUNITY_Unpublish Post Handler|Unpublish Post Handler]]
- [[_COMMUNITY_Membership Type Enum|Membership Type Enum]]
- [[_COMMUNITY_Cancel Registration Handler|Cancel Registration Handler]]
- [[_COMMUNITY_Confirm Registration Handler|Confirm Registration Handler]]
- [[_COMMUNITY_Delete Member Handler|Delete Member Handler]]
- [[_COMMUNITY_Delete Post Handler|Delete Post Handler]]
- [[_COMMUNITY_Delete Registration Handler|Delete Registration Handler]]
- [[_COMMUNITY_Publish Post Handler|Publish Post Handler]]
- [[_COMMUNITY_Publish Tournament Handler|Publish Tournament Handler]]
- [[_COMMUNITY_Reset Registration Handler|Reset Registration Handler]]
- [[_COMMUNITY_Unpublish Tournament Handler|Unpublish Tournament Handler]]
- [[_COMMUNITY_Update Subscription Handler|Update Subscription Handler]]
- [[_COMMUNITY_Update Post Handler|Update Post Handler]]
- [[_COMMUNITY_Dashboard Controller|Dashboard Controller]]
- [[_COMMUNITY_Home Public Controller|Home Public Controller]]
- [[_COMMUNITY_Login Auth Controller|Login Auth Controller]]
- [[_COMMUNITY_AutoLink Twig Extension|AutoLink Twig Extension]]
- [[_COMMUNITY_Create Member Tests|Create Member Tests]]
- [[_COMMUNITY_Cancel Registration Command|Cancel Registration Command]]
- [[_COMMUNITY_Close Tournament Command|Close Tournament Command]]
- [[_COMMUNITY_Confirm Registration Command|Confirm Registration Command]]
- [[_COMMUNITY_Create Member Command|Create Member Command]]
- [[_COMMUNITY_Create Subscription Command|Create Subscription Command]]
- [[_COMMUNITY_Create Post Command|Create Post Command]]
- [[_COMMUNITY_Create Tournament Command|Create Tournament Command]]
- [[_COMMUNITY_Delete Member Command|Delete Member Command]]
- [[_COMMUNITY_Delete Post Command|Delete Post Command]]
- [[_COMMUNITY_Delete Registration Command|Delete Registration Command]]
- [[_COMMUNITY_Publish Post Command|Publish Post Command]]
- [[_COMMUNITY_Publish Tournament Command|Publish Tournament Command]]
- [[_COMMUNITY_Register Command|Register Command]]
- [[_COMMUNITY_Register Result DTO|Register Result DTO]]
- [[_COMMUNITY_Reopen Tournament Command|Reopen Tournament Command]]
- [[_COMMUNITY_Reset Registration Command|Reset Registration Command]]
- [[_COMMUNITY_Start New Season Command|Start New Season Command]]
- [[_COMMUNITY_Unpublish Post Command|Unpublish Post Command]]
- [[_COMMUNITY_Unpublish Tournament Command|Unpublish Tournament Command]]
- [[_COMMUNITY_Update Member Command|Update Member Command]]
- [[_COMMUNITY_Update Subscription Command|Update Subscription Command]]
- [[_COMMUNITY_Update Post Command|Update Post Command]]
- [[_COMMUNITY_Update Tournament Command|Update Tournament Command]]
- [[_COMMUNITY_Member Form Type|Member Form Type]]
- [[_COMMUNITY_Post Form Type|Post Form Type]]
- [[_COMMUNITY_Contact API Controller|Contact API Controller]]
- [[_COMMUNITY_Registration API Controller|Registration API Controller]]
- [[_COMMUNITY_Match Member Query|Match Member Query]]
- [[_COMMUNITY_Symfony Kernel|Symfony Kernel]]

## God Nodes (most connected - your core abstractions)
1. `Uuid` - 60 edges
2. `Tournament` - 24 edges
3. `PhoneNumber` - 20 edges
4. `MemberSubscription` - 19 edges
5. `Registration` - 19 edges
6. `Member` - 15 edges
7. `Post` - 13 edges
8. `MatchMemberHandlerTest` - 11 edges
9. `DoctrineMemberSubscriptionRepository` - 10 edges
10. `MemberController` - 9 edges

## Surprising Connections (you probably didn't know these)
- None detected - all connections are within the same source files.

## Communities (88 total, 70 thin omitted)

### Community 0 - "Member Command Handlers"
Cohesion: 0.05
Nodes (8): CreateMemberHandler, UpdateMemberHandler, Member, MemberTest, MatchMemberHandler, PhoneNumberType, PhoneNumber, PhoneNumberTest

### Community 1 - "Registration Tests"
Cohesion: 0.07
Nodes (4): RegisterHandlerTest, Tournament, TournamentTest, RegistrationApiTest

### Community 2 - "Member Matching Tests"
Cohesion: 0.10
Nodes (4): MatchMemberHandlerTest, CreateMemberSubscriptionHandler, StartNewSeasonHandler, MemberSubscription

### Community 3 - "Registration Domain Core"
Cohesion: 0.08
Nodes (4): CancelRegistrationHandlerTest, RegisterHandler, Registration, RegistrationTest

### Community 4 - "Post/Blog Management"
Cohesion: 0.10
Nodes (3): CreatePostHandler, Post, PostTest

### Community 7 - "Tournament CQRS Handlers"
Cohesion: 0.18
Nodes (3): CreateTournamentHandler, UpdateTournamentHandler, TournamentType

## Knowledge Gaps
- **1 isolated node(s):** `Kernel`
  These have ≤1 connection - possible missing edges or undocumented components.
- **70 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Uuid` connect `UUID Value Object` to `Member Command Handlers`, `Registration Tests`, `Member Matching Tests`, `Registration Domain Core`, `Post/Blog Management`, `Tournament CQRS Handlers`, `Member Admin Controller`, `Tournament Admin Controller`, `Post Admin Controller`, `UUID Doctrine Type`, `Create Admin CLI`, `Member Subscription Tests`, `Close Tournament Handler`, `Reopen Tournament Handler`, `Unpublish Post Handler`, `Cancel Registration Handler`, `Confirm Registration Handler`, `Delete Member Handler`, `Delete Post Handler`, `Delete Registration Handler`, `Publish Post Handler`, `Publish Tournament Handler`, `Reset Registration Handler`, `Unpublish Tournament Handler`, `Update Subscription Handler`, `Update Post Handler`?**
  _High betweenness centrality (0.243) - this node is a cross-community bridge._
- **Why does `PhoneNumber` connect `Member Command Handlers` to `Registration Tests`, `Registration Domain Core`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **Why does `Tournament` connect `Registration Tests` to `Tournament CQRS Handlers`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **Are the 54 inferred relationships involving `Uuid` (e.g. with `.__invoke()` and `.__invoke()`) actually correct?**
  _`Uuid` has 54 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `Tournament` (e.g. with `.__invoke()` and `.openTournament()`) actually correct?**
  _`Tournament` has 8 INFERRED edges - model-reasoned connections that need verification._
- **Are the 15 inferred relationships involving `PhoneNumber` (e.g. with `.__invoke()` and `.__invoke()`) actually correct?**
  _`PhoneNumber` has 15 INFERRED edges - model-reasoned connections that need verification._
- **Are the 8 inferred relationships involving `MemberSubscription` (e.g. with `.__invoke()` and `.__invoke()`) actually correct?**
  _`MemberSubscription` has 8 INFERRED edges - model-reasoned connections that need verification._