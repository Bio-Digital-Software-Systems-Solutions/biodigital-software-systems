# PLAN.md — Module Agile Project Management

> Phase 2 validée. Plan final avant Phase 3 (Code).
> Basé sur `EXPLORE.md` + décisions utilisateur.

---

## 0. Décisions entérinées

| # | Décision | Choix retenu |
|---|---|---|
| 6.1 | Tables séparées Epic / User Story | ✅ Tables séparées `epics` et `user_stories` |
| 6.1-bis | Table pour Task technique (décomposition de story) | ✅ **Réutiliser la table `tasks` existante** via relation polymorphe (`taskable_type = UserStory::class`) |
| 6.2 | Rôle Product Owner | Créer rôle `product-owner` + logique basée sur permission `validate acceptance criteria` |
| 6.3 | URL | Pages Inertia sous `/agile/*` dans `routes/web.php`, endpoints d'action JSON sous `/api/agile/*` dans `routes/api.php` |
| 6.4 | Statut User Story | Enum PHP `backlog, ready, in_progress, review, done` |
| 6.5 | Scope | `work_item_links` inclus ; `sprint_reports` reporté en V2 |
| 6.6 | Comments | Nouvelle table polymorphe `work_item_comments` |
| §17.1 | Nommage nouvelle table task | **Résolu** — pas de nouvelle table, relation polymorphe sur `tasks` existant |
| §17.2 | Sprint controller | **Étendre** `App\Http\Controllers\SprintController` existant |
| §17.3 | Format réponse API action | **`JsonResource`** |
| §17.4 | Découpage permissions | **OK** (cf. §6.2) |

---

## 1. Stratégie d'intégration de la table `tasks` existante

La table `tasks` (unifiée, polymorphe, ~25 colonnes, utilisée par Kanban / Gantt / ProjectController / EpicController legacy) est **conservée telle quelle**. Les « story tasks » du prompt §4.1 (décomposition technique d'une User Story) sont stockés dans cette même table via :

- `taskable_type = App\Models\Agile\UserStory::class`
- `taskable_id = <user_story.id>`
- `type = 'task'` (valeur existante du discriminant)
- `status_id` → référence la table `statuses` (déjà utilisée par les Task legacy)

Pour répondre à la distinction `type ∈ {dev, test, devops, design, doc}` du prompt (qui n'a pas d'équivalent dans l'existant — la colonne `type` sert déjà au discriminant Epic/Story/Task), on ajoute une **colonne additive nullable** `work_type` sur `tasks` (migration non-destructive, valeur nulle pour tous les Task legacy).

Sur le modèle `UserStory`, on déclare :
```php
public function storyTasks(): MorphMany
{
    return $this->morphMany(\App\Models\Task::class, 'taskable');
}
```

Aucune modification à `App\Models\Task` hormis :
- ajout du cast `'work_type' => \App\Enums\Agile\StoryTaskType::class` dans la méthode `casts()` existante,
- ajout de `work_type` au `$fillable`.

---

## 2. Namespacing

| Rubrique | Namespace |
|---|---|
| Modèles Agile | `App\Models\Agile\{Epic, UserStory, AcceptanceCriterion, TestScenario, WorkItemLink, WorkItemComment}` |
| Modèle Task legacy | **Inchangé** — `App\Models\Task` (réutilisé) |
| Controllers Agile | `App\Http\Controllers\Agile\{EpicController, UserStoryController, StoryTaskController, AcceptanceCriterionController, TestScenarioController}` |
| SprintController | `App\Http\Controllers\SprintController` **étendu** (pas de duplication) |
| Policies | `App\Policies\Agile\{EpicPolicy, UserStoryPolicy, AcceptanceCriterionPolicy, TestScenarioPolicy, SprintPolicy}` + `App\Policies\TaskPolicy` (nouveau, n'existe pas actuellement — registered via `Gate::policy(Task::class, ...)` pour contextualiser les story-tasks) |
| FormRequests | `App\Http\Requests\Agile\*` |
| Enums | `App\Enums\Agile\{EpicStatus, UserStoryStatus, StoryTaskType, AcceptanceCriterionStatus, TestScenarioExecutionStatus, WorkItemLinkType}` |
| Services | `App\Services\Agile\{UserStoryCompletionService, AcceptanceCriterionValidationService, SprintLifecycleService}` |
| Events | `App\Events\Agile\{UserStoryCompleted, AcceptanceCriterionValidated, AcceptanceCriterionRejected, SprintStarted, SprintClosed, EpicStatusChanged}` |
| Exceptions | `App\Exceptions\Agile\{CannotCompleteStoryException, ActiveSprintAlreadyExistsException, ClosedSprintCannotAcceptStoriesException, AcceptanceCriterionHasPassedTestsException}` |
| API Resources | `App\Http\Resources\Agile\{EpicResource, UserStoryResource, AcceptanceCriterionResource, TestScenarioResource, StoryTaskResource, SprintResource}` |

---

## 3. Migrations (ordonnées)

| Ordre | Migration | Type | Clé |
|---|---|---|---|
| 1 | `create_epics_table` | create | nouvelle table |
| 2 | `create_user_stories_table` | create | dépend de `epics`, `sprints`, `users` |
| 3 | `create_acceptance_criteria_table` | create | dépend de `user_stories`, `users` |
| 4 | `create_test_scenarios_table` | create | dépend de `acceptance_criteria`, `users` |
| 5 | `add_work_type_to_tasks_table` | **additive** | ajoute 1 colonne nullable à `tasks` |
| 6 | `create_work_item_links_table` | create | polymorphe |
| 7 | `create_work_item_comments_table` | create | polymorphe |

### 3.1. `epics`

```php
Schema::create('epics', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('project_id')->constrained()->cascadeOnDelete();
    $table->foreignId('owner_id')->constrained('users');
    $table->string('title');
    $table->text('description')->nullable();
    $table->text('business_value')->nullable();
    $table->string('status', 32)->default('draft');
    $table->unsignedTinyInteger('priority')->default(3);
    $table->date('target_date')->nullable();
    $table->json('labels')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['project_id', 'status']);
    $table->index('owner_id');
});
```

### 3.2. `user_stories`

```php
Schema::create('user_stories', function (Blueprint $table): void {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('epic_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('sprint_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('reporter_id')->constrained('users');
    $table->string('title');
    $table->string('as_a');
    $table->text('i_want');
    $table->text('so_that');
    $table->unsignedSmallInteger('story_points')->nullable();
    $table->unsignedTinyInteger('priority')->default(3);
    $table->string('status', 32)->default('backlog');
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['epic_id', 'status']);
    $table->index(['sprint_id', 'status']);
    $table->index('assignee_id');
});
```

### 3.3. `acceptance_criteria`

```php
Schema::create('acceptance_criteria', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_story_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('position');
    $table->string('title');
    $table->text('description');
    $table->string('status', 16)->default('pending');
    $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('validated_at')->nullable();
    $table->text('validation_notes')->nullable();
    $table->timestamps();

    $table->index(['user_story_id', 'position']);
    $table->index(['user_story_id', 'status']);
});
```

### 3.4. `test_scenarios`

```php
Schema::create('test_scenarios', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('acceptance_criterion_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('given')->nullable();
    $table->text('when')->nullable();
    $table->text('then')->nullable();
    $table->text('free_form')->nullable();
    $table->string('automated_test_ref')->nullable();
    $table->string('execution_status', 16)->default('not_run');
    $table->foreignId('last_executed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('last_executed_at')->nullable();
    $table->text('failure_notes')->nullable();
    $table->timestamps();

    $table->index(['acceptance_criterion_id', 'execution_status']);
});
```

### 3.5. `add_work_type_to_tasks_table` (additive)

```php
Schema::table('tasks', function (Blueprint $table): void {
    $table->string('work_type', 16)->nullable()->after('type');
    $table->index('work_type');
});
```

Rollback : `dropColumn('work_type')`.

### 3.6. `work_item_links` (polymorphe)

```php
Schema::create('work_item_links', function (Blueprint $table): void {
    $table->id();
    $table->string('source_type');
    $table->unsignedBigInteger('source_id');
    $table->string('target_type');
    $table->unsignedBigInteger('target_id');
    $table->string('link_type', 32);
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();

    $table->index(['source_type', 'source_id'], 'wil_source_idx');
    $table->index(['target_type', 'target_id'], 'wil_target_idx');
    $table->unique(
        ['source_type', 'source_id', 'target_type', 'target_id', 'link_type'],
        'wil_unique'
    );
});
```

### 3.7. `work_item_comments` (polymorphe)

```php
Schema::create('work_item_comments', function (Blueprint $table): void {
    $table->id();
    $table->string('commentable_type');
    $table->unsignedBigInteger('commentable_id');
    $table->foreignId('user_id')->constrained('users');
    $table->foreignId('parent_id')->nullable()->constrained('work_item_comments')->nullOnDelete();
    $table->text('body');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['commentable_type', 'commentable_id'], 'wic_commentable_idx');
});
```

### 3.8. Contrainte "1 sprint actif par projet"

Pas de migration. Enforcement double :
- **Model event** sur `Sprint::saving()` : si `status === 'active'` et qu'il existe déjà un autre Sprint `active` pour le même `project_id`, throw.
- **Service** `SprintLifecycleService::start()` : même vérification en amont pour un message d'erreur propre.

---

## 4. Modèles Eloquent

Tous en `App\Models\Agile\*`. Conventions : `HasFactory`, `LogsActivity` (Spatie, `logFillable + logOnlyDirty + dontSubmitEmptyLogs`), `SoftDeletes` quand pertinent, `HasUuid` sur Epic et UserStory, return types stricts sur les relations.

| Modèle | Relations |
|---|---|
| `Epic` | `belongsTo Project, User(owner)`; `hasMany UserStory`; `morphMany WorkItemComment`; `morphMany WorkItemLink` (as `source_links` + `target_links`) |
| `UserStory` | `belongsTo Epic, Sprint, User(assignee), User(reporter)`; `hasMany AcceptanceCriterion orderBy('position')`; **`morphMany Task (storyTasks via 'taskable')`**; `hasManyThrough TestScenario via AcceptanceCriterion`; `morphMany WorkItemComment`; `morphMany WorkItemLink` |
| `AcceptanceCriterion` | `belongsTo UserStory, User(validatedBy)`; `hasMany TestScenario`; `morphMany WorkItemComment` |
| `TestScenario` | `belongsTo AcceptanceCriterion, User(lastExecutedBy)`; `morphMany WorkItemComment` |
| `WorkItemLink` | `morphTo source, morphTo target`; `belongsTo User(createdBy)` |
| `WorkItemComment` | `morphTo commentable`; `belongsTo User, parent (self)`; `hasMany replies (self)` |

Méthodes de domaine (testées unitairement) :
- `Epic::completionPercentage(): int` (stories done / total)
- `UserStory::canBeCompleted(): bool`
- `UserStory::hasPendingCriteria(): bool`
- `AcceptanceCriterion::isValidated(): bool`
- `AcceptanceCriterion::hasPassingScenarios(): bool`

Sur `App\Models\Task` (legacy) :
- ajouter `work_type` dans `$fillable`,
- ajouter `'work_type' => StoryTaskType::class` dans la méthode `casts()` existante (ligne 208).

Aucun scope ajouté sur Task pour garder le modèle legacy stable.

---

## 5. Enums (`App\Enums\Agile\*`)

Chaque enum fournit `label(): string` (via `__('agile.statuses.*')`) et `color(): string` (classes Tailwind).

| Enum | Cases |
|---|---|
| `EpicStatus` | `DRAFT, READY, IN_PROGRESS, DONE, ARCHIVED` |
| `UserStoryStatus` | `BACKLOG, READY, IN_PROGRESS, REVIEW, DONE` |
| `StoryTaskType` | `DEV, TEST, DEVOPS, DESIGN, DOC` |
| `AcceptanceCriterionStatus` | `PENDING, IN_REVIEW, VALIDATED, REJECTED` |
| `TestScenarioExecutionStatus` | `NOT_RUN, PASSED, FAILED, BLOCKED` |
| `WorkItemLinkType` | `BLOCKS, RELATES_TO, DUPLICATES, PARENT_OF` |

**Statut** d'une story-task : on **réutilise** la table `statuses` existante (via `task.status_id`) — pas d'enum `StoryTaskStatus`. Les workflows de statuts restent configurables globalement.

Aucun conflit avec l'`App\Enums\EpicStatus` legacy : namespace distinct.

---

## 6. RBAC

### 6.1. Nouveau rôle

Dans `App\Enums\Role.php` + `resources/js/Enums/Role.ts` (synchronisation CLAUDE.md) :

```php
case PRODUCT_OWNER = 'product-owner';
```

### 6.2. Nouvelles permissions (convention : "verbe noun" avec espaces)

```
view epics                  create epics                  edit epics                  delete epics
view user stories           create user stories           edit user stories           delete user stories
complete user stories       move stories to sprint
view story tasks            create story tasks            edit story tasks            delete story tasks
view acceptance criteria    create acceptance criteria    edit acceptance criteria    delete acceptance criteria
validate acceptance criteria
view test scenarios         create test scenarios         edit test scenarios         delete test scenarios
execute test scenarios
start sprints               close sprints
```

Ajout dans `database/seeders/RolesAndPermissionsSeeder.php` :
- `super-admin` : toutes.
- `admin` : toutes sauf `delete epics` et `delete user stories` (alignement avec conventions existantes du seeder — à confirmer par inspection en Phase 3).
- `project-manager` : toutes sauf `validate acceptance criteria`.
- `product-owner` : `view *`, `create/edit epics, user stories`, `validate acceptance criteria`, `complete user stories`, `move stories to sprint`, `view story tasks`, `view test scenarios`.
- `member` : `view *` uniquement.
- Autres rôles : aucun ajout (permission héritée uniquement via nouveaux rôles).

---

## 7. Policies (`App\Policies\Agile\*` + `App\Policies\TaskPolicy`)

Registration dans `App\Providers\AuthServiceProvider` (ou `bootstrap/app.php` selon Laravel 12). Pattern owner-first (imité de `ProjectPolicy`).

| Policy | Cible modèle | Méthodes |
|---|---|---|
| `Agile\EpicPolicy` | `Epic` | viewAny, view, create, update, delete |
| `Agile\UserStoryPolicy` | `UserStory` | viewAny, view, create, update, delete, **complete**, **moveToSprint** |
| `Agile\AcceptanceCriterionPolicy` | `AcceptanceCriterion` | viewAny, view, create, update, delete, **validate**, **reject** |
| `Agile\TestScenarioPolicy` | `TestScenario` | viewAny, view, create, update, delete, **recordRun** |
| `Agile\SprintPolicy` | `Sprint` | viewAny, view, create, update, delete, **start**, **close** |
| `TaskPolicy` | `Task` (legacy) | viewAny, view, create, update, delete — scoped à l'usage story-task du module agile ; les controllers legacy n'utilisent pas cette policy pour rester compatibles |

Règle-clé `AcceptanceCriterionPolicy::validate` :
```php
return $user->id === $ac->userStory->epic?->owner_id
    || $user->can('validate acceptance criteria');
```

---

## 8. FormRequests (`App\Http\Requests\Agile\*`)

Validation en array form (convention majoritaire). `authorize()` s'appuie sur Policy. Messages via `__('agile.validation.*')`.

| Request | Règles clés |
|---|---|
| `StoreEpicRequest` | `project_id req\|exists`, `owner_id req\|exists:users`, `title req\|max:255`, `status nullable\|in:`, `priority int\|between:1,5`, `target_date nullable\|date` |
| `UpdateEpicRequest` | idem sauf `project_id` immutable |
| `StoreUserStoryRequest` | `epic_id nullable\|exists`, `title req`, `as_a req\|max:255`, `i_want req`, `so_that req`, `story_points nullable\|int\|min:0\|max:999` |
| `UpdateUserStoryRequest` | idem ; passage à `done` refusé ici → levé par le service |
| `MoveUserStoryToSprintRequest` | `sprint_id nullable\|exists:sprints,id` |
| `StoreAcceptanceCriterionRequest` | `title req\|max:255`, `description req`, `position nullable\|int\|min:1` |
| `ValidateAcceptanceCriterionRequest` | `notes nullable\|string\|max:2000` |
| `RejectAcceptanceCriterionRequest` | `notes req\|string\|max:2000` |
| `ReorderAcceptanceCriteriaRequest` | `ordered_ids req\|array`, `ordered_ids.* req\|int\|exists:acceptance_criteria,id` |
| `StoreTestScenarioRequest` | `title req`, mutex Gherkin vs free_form via rule custom |
| `UpdateTestScenarioRequest` | idem |
| `RecordTestRunRequest` | `status req\|in:passed,failed,blocked`, `failure_notes required_if:status,failed` |
| `StoreStoryTaskRequest` | `user_story_id req\|exists:user_stories,id`, `title req`, `work_type req\|in:dev,test,devops,design,doc`, `estimated_hours nullable\|numeric\|min:0` |
| `UpdateStoryTaskRequest` | idem, `user_story_id` immutable |
| `StartSprintRequest` / `CloseSprintRequest` | pas de body, autorisation uniquement |

---

## 9. Controllers & Routes

### 9.1. Controllers REST Agile (`App\Http\Controllers\Agile\*`)

Chaque controller :
- Middleware `auth` + `verified` au constructeur.
- **Aucune logique métier** (délégation aux Services).
- Eager loading systématique + pagination + filtres query string.
- **Pages Inertia** : retour `Inertia::render('Agile/<Entity>/<Action>', [...])` avec array mappé à la main (convention existante — suit `EpicController` legacy).
- **Endpoints d'action JSON** : retour `JsonResource` (cf. 9.3).

### 9.2. SprintController (extension)

`App\Http\Controllers\SprintController` existant est **étendu** avec trois actions nouvelles :

```php
public function start(Sprint $sprint, SprintLifecycleService $service): SprintResource
public function close(Sprint $sprint, SprintLifecycleService $service): SprintResource
public function moveStoryToSprint(MoveUserStoryToSprintRequest $req, UserStory $story, SprintLifecycleService $service): UserStoryResource
```

Les méthodes existantes (`index, show, store, update, destroy`) sont **inchangées**.

### 9.3. Routes Inertia (`routes/web.php`)

```php
Route::middleware(['auth', 'verified'])->prefix('agile')->name('agile.')->group(function (): void {
    Route::resource('epics', Agile\EpicController::class);
    Route::resource('user-stories', Agile\UserStoryController::class);
    Route::resource('user-stories.acceptance-criteria', Agile\AcceptanceCriterionController::class)->shallow();
    Route::resource('acceptance-criteria.test-scenarios', Agile\TestScenarioController::class)->shallow();
    Route::resource('user-stories.story-tasks', Agile\StoryTaskController::class)->shallow();
});
```

### 9.4. Routes d'action JSON (`routes/api.php`) — retour `JsonResource`

```php
Route::middleware(['auth:sanctum'])->prefix('agile')->name('api.agile.')->group(function (): void {
    Route::post('sprints/{sprint}/start',         [SprintController::class, 'start'])->name('sprints.start');
    Route::post('sprints/{sprint}/close',         [SprintController::class, 'close'])->name('sprints.close');
    Route::post('user-stories/{userStory}/move-to-sprint', [SprintController::class, 'moveStoryToSprint'])->name('user-stories.move');
    Route::post('user-stories/{userStory}/complete',       [Agile\UserStoryController::class, 'complete'])->name('user-stories.complete');
    Route::post('acceptance-criteria/{criterion}/validate',[Agile\AcceptanceCriterionController::class, 'validate'])->name('ac.validate');
    Route::post('acceptance-criteria/{criterion}/reject',  [Agile\AcceptanceCriterionController::class, 'reject'])->name('ac.reject');
    Route::post('user-stories/{userStory}/acceptance-criteria/reorder', [Agile\AcceptanceCriterionController::class, 'reorder'])->name('ac.reorder');
    Route::post('test-scenarios/{scenario}/record-run',    [Agile\TestScenarioController::class, 'recordRun'])->name('ts.record');
});
```

### 9.5. API Resources (`App\Http\Resources\Agile\*`)

Conformes à la décision §17.3. Réponses JSON des endpoints d'action utilisent exclusivement ces Resources.

- `EpicResource` (avec `userStoriesCount`, `completionPercentage`)
- `UserStoryResource` (avec `acceptanceCriteria`, `canBeCompleted`)
- `AcceptanceCriterionResource` (avec `testScenariosCount`, `validatedBy` nested)
- `TestScenarioResource`
- `StoryTaskResource` (wrapper sur `Task` pour le contexte agile)
- `SprintResource` (enrichi de `userStoriesCount`, `velocity`)

---

## 10. Services métier (`App\Services\Agile\*`)

### 10.1. `UserStoryCompletionService`

```php
public function complete(UserStory $story, User $actor): UserStory
public function canBeCompleted(UserStory $story): bool
```

- `complete()` : vérifie tous les AC `validated` → sinon throw `CannotCompleteStoryException`. Set `status = done`, `completed_at = now()`. Dispatch `UserStoryCompleted`.

### 10.2. `AcceptanceCriterionValidationService`

```php
public function validate(AcceptanceCriterion $ac, User $validator, ?string $notes): AcceptanceCriterion
public function reject(AcceptanceCriterion $ac, User $validator, string $notes): AcceptanceCriterion
public function guardDelete(AcceptanceCriterion $ac): void
public function reorder(UserStory $story, array $orderedIds): void
```

- `guardDelete()` : throw `AcceptanceCriterionHasPassedTestsException` si `$ac->testScenarios()->where('execution_status', 'passed')->exists()`.

### 10.3. `SprintLifecycleService`

```php
public function start(Sprint $sprint): Sprint
public function close(Sprint $sprint): Sprint
public function moveStoryToSprint(UserStory $story, ?Sprint $target): UserStory
```

- `start()` : refuse si `Sprint::where('project_id', $sprint->project_id)->where('status', 'active')->where('id', '!=', $sprint->id)->exists()` → throw `ActiveSprintAlreadyExistsException`.
- `moveStoryToSprint()` : refuse si `$target?->status === 'completed'` → throw `ClosedSprintCannotAcceptStoriesException`.

---

## 11. Events & Exceptions

### Events (`App\Events\Agile\*`)
`Dispatchable + SerializesModels`. Pas de `ShouldBroadcast` (V2).

- `EpicStatusChanged(Epic, string $from, string $to)`
- `UserStoryCompleted(UserStory, User)`
- `AcceptanceCriterionValidated(AcceptanceCriterion, User)`
- `AcceptanceCriterionRejected(AcceptanceCriterion, User, string $notes)`
- `SprintStarted(Sprint)`
- `SprintClosed(Sprint)`

### Exceptions (`App\Exceptions\Agile\*`)
Étendent `RuntimeException`. Render en **HTTP 422** via handler global avec payload `{ message, errors? }`. Message via `__('agile.errors.*')`.

- `CannotCompleteStoryException`
- `ActiveSprintAlreadyExistsException`
- `ClosedSprintCannotAcceptStoriesException`
- `AcceptanceCriterionHasPassedTestsException`

Enregistrement dans `bootstrap/app.php` via `$exceptions->render(...)`.

---

## 12. Factories & Seeders

### Factories
Un fichier par nouveau modèle, avec états :

- `EpicFactory::draft(), ::ready(), ::inProgress(), ::done(), ::forProject(Project $p)`
- `UserStoryFactory::inBacklog(), ::inSprint(Sprint $s), ::withCriteria(int $n), ::withValidatedCriteria(int $n), ::completable()`
- `AcceptanceCriterionFactory::pending(), ::inReview(), ::validated(User $by), ::rejected(User $by)`
- `TestScenarioFactory::gherkin(), ::freeForm(), ::passed(), ::failed(User $by), ::blocked()`

### TaskFactory (extension)
Ajouter à `TaskFactory` existant :
- `::asStoryTask(UserStory $story)` → set `taskable_type=UserStory::class, taskable_id=$story->id, type='task'`
- `::withWorkType(StoryTaskType $type)` → set `work_type`

### Seeder
`database/seeders/Agile/AgileDemoSeeder.php` — optionnel, non inclus dans `DatabaseSeeder` par défaut. Rattache au premier Project, crée 2 Epics × 3 Stories × 3 AC (dont un validé), quelques TestScenarios, quelques story-tasks via `TaskFactory::asStoryTask()`.

Mise à jour `RolesAndPermissionsSeeder` pour inclure les nouvelles permissions et le rôle `product-owner`.

---

## 13. i18n

Créer :
- `lang/fr/agile.php`
- `lang/en/agile.php`
- `lang/de/agile.php`

Clés : `statuses.*`, `validation.*`, `errors.*`, `labels.*`, `roles.product_owner`.

Front-end React : hors scope (prompt séparé).

---

## 14. Tests Pest

Organisation : `tests/Feature/Agile/*`, `tests/Unit/Agile/*`. Tous avec `uses(RefreshDatabase::class)`.

### 14.1. Feature tests

| Fichier | Couverture |
|---|---|
| `EpicCrudTest` | CRUD, filtrage par project, eager loading |
| `UserStoryCrudTest` | CRUD, format « En tant que… je veux… », cohérence epic/sprint |
| `UserStoryCompletionRuleTest` | **Critique** — 100% couverture : ✅ done si tous AC validés, ❌ 422 sinon, event dispatch, `completed_at` set |
| `UserStoryMoveToSprintTest` | sprint planned/active OK, closed → 422 |
| `AcceptanceCriterionCrudTest` | création avec position auto, réordonnancement, listing ordonné |
| `AcceptanceCriterionValidationTest` | ✅ validation user autorisé, ❌ 403 non autorisé, ❌ rejet notes obligatoires, timestamps tracés |
| `AcceptanceCriterionDeletionTest` | suppression refusée si scenario passed attaché |
| `TestScenarioCrudTest` | Gherkin vs free_form mutex |
| `TestScenarioExecutionTest` | record run passed/failed/blocked, timestamps + user, `failure_notes` obligatoire si failed |
| `SprintStartTest` | un seul active par project → 422 sinon |
| `SprintCloseTest` | transition + event |
| `StoryTaskCrudTest` | CRUD via `TaskPolicy`, persistance dans `tasks` avec `taskable_type=UserStory`, `work_type` obligatoire |
| `WorkItemLinkTest` | création/suppression liens polymorphes, unicité |
| `WorkItemCommentTest` | création/threading, polymorphe sur 5 entités |
| `AgileIndexFiltersTest` | pagination + filtres combinés |
| `AgileAuthorizationTest` | matrice rôle × permission (product-owner valide AC, project-manager ne peut pas, member read-only) |

### 14.2. Unit tests

| Fichier | Couverture |
|---|---|
| `EpicTest` | `completionPercentage()` (0 stories/0%, 2/5/40%, etc.) |
| `UserStoryTest` | `canBeCompleted()` — vrai si tous AC validated, faux sinon |
| `AcceptanceCriterionTest` | `isValidated()`, `hasPassingScenarios()` |
| `UserStoryCompletionServiceTest` | logique de complétion isolée |
| `SprintLifecycleServiceTest` | transitions + invariants |

### 14.3. Policy tests

| Fichier | Matrice × {owner, project-manager, product-owner, member, guest} |
|---|---|
| `EpicPolicyTest` | viewAny, view, create, update, delete |
| `UserStoryPolicyTest` | + complete, moveToSprint |
| `AcceptanceCriterionPolicyTest` | + validate, reject |
| `TestScenarioPolicyTest` | + recordRun |
| `SprintPolicyTest` | + start, close |
| `TaskPolicyAgileContextTest` | story-task CRUD authorization |

### 14.4. Couverture cible
- **100%** sur `UserStoryCompletionService` (invariant critique).
- 100% sur toutes les policies.
- ≥ 90% sur `AcceptanceCriterionValidationService`, `SprintLifecycleService`.
- Happy path + ≥ 2 failure paths par endpoint.

---

## 15. Ordre d'exécution (Phase 3 — commits atomiques)

| # | Scope | Commit message |
|---|---|---|
| 1 | Enums Agile (`App\Enums\Agile\*`) | `feat(agile): add agile module enums` |
| 2 | Exceptions métier | `feat(agile): add domain exceptions` |
| 3 | Migration `epics` + Model `Epic` + Factory + Unit tests | `feat(agile): add epic model and migration` |
| 4 | Migration `user_stories` + Model `UserStory` + Factory + Unit tests | `feat(agile): add user story model and migration` |
| 5 | Migration `acceptance_criteria` + Model + Factory + Unit tests | `feat(agile): add acceptance criterion model` |
| 6 | Migration `test_scenarios` + Model + Factory + Unit tests | `feat(agile): add test scenario model` |
| 7 | Migration `add_work_type_to_tasks` + cast sur `Task` + states `TaskFactory` | `feat(agile): add work_type to tasks for story-task context` |
| 8 | Migrations + Models `WorkItemLink`, `WorkItemComment` | `feat(agile): add polymorphic links and comments` |
| 9 | Events Agile | `feat(agile): add domain events` |
| 10 | Services (`UserStoryCompletionService`, `AcceptanceCriterionValidationService`, `SprintLifecycleService`) + Unit tests | `feat(agile): add domain services with completion invariant` |
| 11 | Rôle `product-owner` + permissions dans `RolesAndPermissionsSeeder` + sync `Role.ts` | `feat(agile): add product-owner role and permissions` |
| 12 | Policies + Policy tests | `feat(agile): add authorization policies` |
| 13 | FormRequests | `feat(agile): add form requests` |
| 14 | API Resources (`App\Http\Resources\Agile\*`) | `feat(agile): add api resources` |
| 15 | Controllers REST + routes Inertia | `feat(agile): add resource controllers` |
| 16 | Extension `SprintController` + endpoints d'action JSON (`api.php`) | `feat(agile): add action endpoints with json resources` |
| 17 | Feature tests (workflows + authorization + filtres) | `test(agile): feature coverage` |
| 18 | i18n `agile.php` FR/EN/DE + register handler rendu 422 | `feat(agile): add i18n and exception rendering` |
| 19 | Pint `--dirty` + PHPStan | `chore(agile): apply code style and static analysis` |

**Processus par étape** : `vendor/bin/pint --dirty` + `php artisan test --filter=<le_test>` → commit. Full suite `php artisan test` avant la PR.

---

## 16. Hors scope (confirmé)

- Frontend React/Inertia (Pages, forms, views).
- Webhooks, import/export JIRA.
- Burndown / métriques avancées.
- `sprint_reports` (V2).
- `ShouldBroadcast` / Reverb.

---

**Fin du plan. Prêt à passer en Phase 3 (Code).**
