# EXPLORE.md — Module Agile Project Management

> Phase 1 du prompt : rapport d'exploration de la codebase existante avant rédaction du `PLAN.md`.
> Branche : `16-epic-user-story-task` — HEAD : `389cbead WIP` (n'apporte rien d'agile : ce WIP concerne `Visitor` / `GroupActivity` / `IntegrationPathway`).

---

## 1. Stack & versions confirmées

| Couche | Version | Source |
|---|---|---|
| PHP | `^8.2` (runtime 8.4.8 d'après `laravel-boost`) | `composer.json` |
| Laravel | `^12.0` | `composer.json:20` |
| Inertia (Laravel) | `^2.0` | `composer.json:17` |
| Inertia (React) | `^2.0` | `package.json:24` |
| Fortify + Sanctum | `^1` / `^4` | `composer.json:19,21` |
| Spatie Permission | `^6.21` | `composer.json:33` |
| Spatie Activity Log | `^4.10` | `composer.json:31` |
| Pest | `^3.8` | `composer.json:53` |
| React | `^19.1` | `package.json:122` |
| TypeScript | `^5.9` | `package.json:139` |
| Tailwind | `^3.2` | `package.json:57` |
| Ziggy | `^2.6` | `composer.json:34`, `package.json:142` |

Mono-tenant. Aucune trace de `Tenant`, `Organization`, `Workspace`, `tenant_id`, ni global scope multi-tenant.

---

## 2. Existant pertinent pour le module

### 2.1. ⚠️ Décision architecturale déjà prise dans la codebase

**Le code existant traite Epic, User Story et Task comme un seul modèle `Task` discriminé par une colonne `type`** (`'epic' | 'story' | 'task'`). C'est l'**inverse** du schéma proposé dans le prompt (tables séparées `epics`, `user_stories`, `tasks`).

Évidence :

- `app/Models/Task.php` (~528 lignes) — modèle unique avec champs : `type`, `key`, `epic_id`, `parent_id`, `sprint_id`, `story_points`, `assigned_to`, `reporter_id`, `taskable_type/taskable_id` (polymorphe), `status_id` (FK vers `statuses`), `priority`, `progress`, `labels` (json), `custom_fields` (json), soft deletes.
- `app/Http/Controllers/EpicController.php:29-31` :
  ```php
  $query = Task::with(['taskable', 'assignee', 'reporter', 'status', 'attachments.user'])
      ->where('type', 'epic')
      ->where('taskable_type', \App\Models\Project::class);
  ```
- `database/seeders/ProjectTaskHistoricalSeeder.php:65-79` — seeders existants créent déjà des Tasks avec `'type' => 'story'` et `'type' => 'task'`.
- `app/Enums/EpicStatus.php` — enum existant : `TODO, PENDING, IN_PROGRESS, UNDER_REVIEW, COMPLETED, CANCELLED` (avec `label()`, `color()`, `isCompleted()`, `isActive()`).
- `app/Enums/TaskStatus.php` — enum existant pour les Task statuses non-Epic (statuts stockés dans une table `statuses` référencée par `status_id`).

**Conséquence** : appliquer le prompt à la lettre (créer des tables `epics` et `user_stories` distinctes) **dupliquerait** Epic et User Story (qui existent déjà comme `Task` filtrés). Cela casserait `EpicController`, `KanbanController`, `GanttController`, `ProjectController` et le seeder historique.

→ **Cette décision doit être tranchée avant le `PLAN.md`. Voir §6.**

### 2.2. Sprint — déjà implémenté, à réutiliser tel quel

`app/Models/Sprint.php` (~132 lignes) :
- `project_id`, `name`, `goal`, `start_date`, `end_date`, `status` (string : `planned | active | completed`), `capacity` (story points), `progress` (int).
- `tasks(): HasMany`, `project(): BelongsTo`, `attachments(): MorphMany`.
- Scopes `active()`, `upcoming()`. Accessor `velocity` qui somme les `story_points` des tasks complétées.
- `LogsActivity` (Spatie). Pas de soft deletes.

`SprintController` existe. Migration : `2025_10_02_190920_create_sprints_table.php`. **Aucune contrainte d'unicité d'un sprint actif par projet** côté base ni côté model — à ajouter (cf. exigence §5.4 du prompt).

### 2.3. Project — parent des Epic/Stories/Tasks

`app/Models/Project.php` :
- Champs : `uuid`, `name`, `slug`, `description`, `status` (cast `ProjectStatus`), `priority` (cast `Priority`), `start_date`, `end_date`, `budget` (decimal:2), `project_manager_id`, `reviewer_id`, `is_template`, `settings` (json), soft deletes.
- `tasks(): MorphMany` (polymorphe via `taskable`), `sprints(): HasMany`, `manager(): BelongsTo`, `members(): BelongsToMany` via pivot `project_members` (avec `is_lead`, `started_at`, `ended_at`), `participants(): HasMany ProjectParticipant`.
- Service métier : `app/Services/ProjectStatisticsService.php` (cache 10min, vélocité daily/weekly/monthly).

Le **parent naturel d'un Epic est `Project`**. Pas de Workspace/Board.

### 2.4. AcceptanceCriterion / TestScenario

**Aucun modèle, migration, contrôleur, ni table existante**. À créer entièrement (que l'on garde la table unifiée `tasks` ou non — voir §6).

---

## 3. Conventions à respecter

### Migrations
- snake_case, `foreignId('x_id')->constrained()->cascadeOnDelete()` pour FK fortes, `nullOnDelete()` pour FK lâches.
- `softDeletes()` sur les entités principales (Project, Task), pas sur les enfants en cascade (Sprint).
- Statut généralement stocké soit en `string` avec valeur par défaut, soit via FK vers table `statuses` (cas des Tasks). **Pas d'enum SQL** ; les enums PHP castent depuis une colonne string.
- Indexes composites ex. `(project_id, status)`.
- Exemple : `database/migrations/2025_10_02_190920_create_sprints_table.php`.

### Modèles
- `LogsActivity` (Spatie) presque systématique :
  ```php
  public function getActivitylogOptions(): LogOptions {
      return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
  }
  ```
- Casts : soit `protected $casts = [...]` (Project, Sprint), soit `protected function casts(): array { return [...]; }` (Task). Pas de règle stricte — j'aligne sur la table parente.
- `HasUuid` trait custom pour générer un `uuid` à la création.
- Return types stricts sur les relations : `public function tasks(): HasMany`.
- Scopes en `scopeXxx()`.

### Controllers
- Middleware d'auth + `can:*` dans le constructeur :
  ```php
  $this->middleware('auth');
  $this->middleware('can:view projects')->only([...]);
  ```
- Eager loading systématique avec `with()` + `withCount()`.
- Réponse via `Inertia::render('Folder/Page', [...])`.
- Validation : actuellement majoritairement **inline** (`$request->validate([...])`) plutôt qu'en FormRequest dédié (cf. `EpicController:130-137`). Le prompt impose des FormRequest — choix justifié à mentionner.
- Pas d'API Resource (`JsonResource`) observée — Inertia consomme directement les arrays.

### Policies
- `app/Policies/ProjectPolicy.php` modèle :
  ```php
  public function view(User $user, Project $project): bool {
      if ($project->project_manager_id === $user->id) return true;
      if ($project->participants?->contains('id', $user->id)) return true;
      return $user->can('view projects');
  }
  ```
- Owner check d'abord, puis fallback `can('permission')` Spatie.
- Méthodes standard : `viewAny, view, create, update, delete`. Méthodes custom autorisées (ex : `validate`, `recordRun`).

### RBAC
- `app/Enums/Role.php` (kebab-case values) — synchronisé avec `resources/js/Enums/Role.ts` (rappel `CLAUDE.md`).
- `database/seeders/RolesAndPermissionsSeeder.php` — point d'entrée pour ajouter les permissions du module agile.
- Permissions existantes pertinentes : `view projects`, `create projects`, `edit projects`, `view programs`. Aucune permission `*.story.*` ou `*.acceptance.*` n'existe — à créer.
- Pas de rôle "Product Owner" actuel. Le prompt y fait référence (§5.2). Décision à prendre : créer le rôle `product-owner` ou reposer sur `project-manager` existant. **Voir §6.**

### Routes
- `routes/web.php` pour pages Inertia, `routes/api.php` pour endpoints JSON purs.
- Pas de préfixe `/api/agile` actuellement. Le prompt suggère ce préfixe. À aligner sur la convention dominante : **Inertia first**, donc routes dans `routes/web.php`. Si endpoints JSON purs nécessaires (ex : nested resources `acceptance-criteria/{id}/validate`), `routes/api.php`.
- Ziggy actif → côté front utiliser `route('epics.index')`.

### Tests
- **Pest 3** uniquement. `tests/Pest.php` étend `Tests\TestCase` pour `Feature` et `Unit`.
- Pattern :
  ```php
  uses(RefreshDatabase::class);
  beforeEach(function (): void { /* setup roles/perms */ });
  it('does X', function (): void { /* ... */ });
  ```
- Auth de test : `$this->actingAs($user)`. Inertia : `$response->assertInertia(fn ($page) => $page->component('...'))`.
- Factories en `database/factories/`, états en `state(fn ($attrs): array => [...])`.

### i18n
- `resources/js/i18n.ts` : i18next + react-i18next. Clés hiérarchiques en JS/TS (pas de fichiers `lang/{fr,en,de}/*.php` côté serveur observés pour les nouvelles features). Côté serveur, les messages d'erreur peuvent rester en français (cf. enum `EpicStatus::label()` qui retourne du français en dur).
- Le prompt impose `__()` côté serveur — aligner.

### Events / Exceptions
- `app/Events/` existe (4 events : `WorkflowStarted`, etc.). Pas de namespace dédié pour exceptions métier — pas d'`app/Exceptions/Domain/`. À créer si besoin (le prompt en demande : `CannotCompleteStoryException`).

### Activity Log
- Trait `Spatie\Activitylog\Traits\LogsActivity` sur tous les modèles de domaine (User, Project, Sprint, Task…). À reproduire systématiquement.

---

## 4. Inventaire de l'existant agile

| Entité prompt | Existant | Forme | Action probable |
|---|---|---|---|
| `Epic` | ✅ | `Task` avec `type='epic'`, `EpicController`, `EpicStatus` enum, `Epics/Index` Inertia page | **Décision §6** |
| `Sprint` | ✅ | Modèle, table, controller, factory, seeder | Réutiliser, **ajouter** contrainte 1 actif/projet + actions `start`/`close` |
| `User Story` | ⚠️ partiel | `Task` avec `type='story'` (déjà dans seeders) — mais sans `as_a / i_want / so_that`, sans `AcceptanceCriterion` | **Décision §6** |
| `Acceptance Criterion` | ❌ | — | À créer (table dédiée, pas en JSON) |
| `Test Scenario` | ❌ | — | À créer (table dédiée, format Gherkin + free-form) |
| `Task` (technique) | ✅ | `Task` avec `type='task'`, `parent_id` pour rattacher à un parent | **Décision §6** |
| `work_item_links` polymorphe | ❌ | — | À créer si confirmé |
| `work_item_comments` polymorphe | ⚠️ | `task_comments` existe (parent_id pour threads) — mais non polymorphe | À étendre ou créer générique |

Migrations historiques pertinentes :
- `2025_08_21_064438_create_tasks_table.php`
- `2025_10_02_190920_create_sprints_table.php`
- `2025_10_02_191252_create_task_comments_table.php`
- `2025_10_28_181310_make_tasks_polymorphic.php`
- `2025_10_28_183222_migrate_project_tasks_to_tasks_table.php` *(consolidation passée d'une ancienne `project_tasks` vers `tasks` — précédent qui confirme la stratégie de table unifiée)*
- `2025_10_29_090727_drop_project_tasks_table.php`

L'historique de migrations montre clairement que **l'équipe a déjà fait le choix d'unifier vers `tasks`** (migration de `project_tasks` → `tasks` en oct. 2025).

---

## 5. Dette technique & risques côté agile

1. **Champs User Story manquants dans `tasks`** : `as_a`, `i_want`, `so_that` n'existent pas. Si on garde la table unifiée, ce sont des colonnes nullables (renseignées seulement quand `type='story'`).
2. **Pas de FormRequest pour Epic/Task** — création/édition sont validées inline. Le prompt impose des FormRequest → effort additionnel.
3. **Pas de Policy `EpicPolicy` / `TaskPolicy`** observée — `EpicController` repose sur `can:view programs` au niveau middleware. À créer.
4. **Pas de `JsonResource`** — Inertia consomme des arrays mappés à la main dans le controller (cf. `EpicController:48-93`). Suivre cette convention plutôt que d'introduire des Resources.
5. **Statut Task** : géré via FK `status_id` vers table `statuses` (workflow configurable). **Statut Epic** : géré via colonne string castée vers `EpicStatus` enum (incohérence interne déjà présente). Pour User Story, il faudra trancher : suivre Epic (enum simple) ou Task (FK status_id). Le prompt suggère un enum (`backlog, ready, in_progress, review, done`) → enum simple recommandé.
6. **`task_comments` non polymorphe** — ajouter un wrapper polymorphe ou créer une table `work_item_comments` si on veut commenter des AC et Test Scenarios.

---

## 6. ⚠️ Points de décision à trancher avant `PLAN.md`

Ces points ne peuvent pas être tranchés sans toi. Je propose une recommandation pour chacun, mais demande validation explicite.

### 6.1. **CRITIQUE — Tables séparées vs table `tasks` unifiée**

Le prompt §4.1 décrit des tables `epics` et `user_stories` séparées. La codebase a déjà choisi une table `tasks` unifiée discriminée par `type` (Epic = `Task` filtré). Trois options :

| Option | Description | Coût | Risque |
|---|---|---|---|
| **A. Suivre le prompt à la lettre** | Créer tables `epics` et `user_stories` séparées. Migrer les Tasks existants `type='epic'` / `type='story'` vers ces tables. Refondre `EpicController`. | Élevé — migration de données + refonte de 4 controllers + tests régression | Cassures côté front (Kanban/Gantt qui consomment `Task`). |
| **B. Adapter le prompt à l'existant (recommandé)** | Garder la table `tasks` unifiée. Ajouter colonnes manquantes (`as_a`, `i_want`, `so_that`, `business_value`, `target_date`, `labels`, etc.) en nullable. Créer **uniquement** les nouvelles tables `acceptance_criteria` et `test_scenarios`, rattachées via `user_story_id` qui pointe vers `tasks.id` (avec un check `type='story'` côté model). | Faible — additif, non destructif. | L'invariant "AC rattaché uniquement à story" doit être enforcé en model event. |
| **C. Hybride** | Tables séparées `epics` / `user_stories`, mais sans toucher aux Tasks existants `type='epic'` / `type='story'` (les déprécier en place). | Moyen | Deux concepts d'Epic en parallèle dans le code → confusion durable. **Déconseillé.** |

**Recommandation : Option B**, parce que :
- Non-destructif (règle d'or §1 du prompt).
- L'équipe a déjà choisi la stratégie unifiée (cf. migration `migrate_project_tasks_to_tasks_table` d'oct 2025).
- Les seeders existants (`ProjectTaskHistoricalSeeder`) continuent de fonctionner.
- Le frontend (Kanban, Gantt, EpicController) reste compatible.
- Les nouvelles tables dédiées (`acceptance_criteria`, `test_scenarios`) restent **bien des tables**, conformément à l'esprit du prompt §4.1 (qui insiste sur le fait que les AC ne doivent **pas** être en JSON — cela on le respecte).

### 6.2. Statut "Product Owner"

Le prompt §5.2 mentionne un rôle "Product Owner". Aucun rôle équivalent dans `Role` enum. Options :

- **B1.** Créer un nouveau rôle `product-owner` dans `Role.php` + `Role.ts` + seeder.
- **B2.** Mapper sur le rôle existant `project-manager`.
- **B3.** Reposer uniquement sur la permission granulaire `acceptance_criteria.validate` (que n'importe quel rôle peut recevoir).

**Recommandation : B3** + alias B1 (créer le rôle pour cohérence métier mais ne pas baser la logique dessus). Ainsi la policy vérifie la permission, pas le rôle, ce qui suit la convention de `ProjectPolicy`.

### 6.3. Préfixe d'URL

Le prompt §6 propose `/api/agile`. Le code n'utilise pas ce pattern (pas de `/api/<domain>` observé). Inertia → `routes/web.php` sans préfixe `/api`. Options :

- **C1.** Suivre l'existant : routes Inertia dans `web.php` sans préfixe `/api`. Endpoints d'action AJAX (validate, recordRun) dans `web.php` également (acceptés par middleware `auth`).
- **C2.** Préfixer `/api/agile` comme demandé, dans `routes/api.php`.

**Recommandation : C1** pour les pages Inertia (`/epics`, `/user-stories`, etc.) et `routes/api.php` sous le groupe existant pour les endpoints JSON purs invoqués depuis le front (ex : `POST /acceptance-criteria/{id}/validate`).

### 6.4. Statut User Story : enum simple ou FK `status_id` ?

- **D1.** Enum simple (`backlog, ready, in_progress, review, done`) comme demandé par le prompt §4.1. Cohérent avec EpicStatus.
- **D2.** FK `status_id` comme les Tasks normales (workflow configurable).

**Recommandation : D1** — le prompt l'impose, et la règle métier "story → done bloquée si AC non validés" est plus simple à enforcer avec un enum statique.

### 6.5. `work_item_links` et `sprint_reports`

Le prompt §4.1 mentionne `work_item_links` (liens polymorphes) et §5.6 évoque `sprint_reports` "à valider".

**Recommandation** :
- `work_item_links` : **inclure** dans le scope, c'est cohérent avec un module agile mature.
- `sprint_reports` : **reporter en V2**, tu pourras le décider sur la base des events `SprintClosed` qu'on émet déjà (prompt §8.11).

### 6.6. Comments polymorphes

`task_comments` existe mais n'est pas polymorphe. Options :

- **F1.** Créer une nouvelle table `work_item_comments` polymorphe (Epic, UserStory=Task, AC, TestScenario, Task).
- **F2.** Étendre `task_comments` en polymorphe via migration additive (ajouter `commentable_type/commentable_id` + script de backfill `commentable_type=Task`).

**Recommandation : F1** — plus sûr, n'altère pas une table existante très utilisée.

---

## 7. Livrable suivant

Si tu valides les points §6, je passe à `PLAN.md` (Phase 2) avec la liste détaillée et ordonnée :
- migrations,
- modèles + relations,
- enums,
- form requests,
- policies + permissions à ajouter au `RolesAndPermissionsSeeder`,
- routes,
- controllers,
- services métier (`UserStoryCompletionService`, `AcceptanceCriterionValidationService`, `SprintLifecycleService`),
- events + exceptions,
- factories + seeder,
- couverture de tests Pest (feature + unit + policy).

**STOP — En attente de validation des décisions §6 avant rédaction du `PLAN.md`.**
