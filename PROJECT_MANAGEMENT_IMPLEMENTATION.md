# Module de Gestion de Projets - Guide d'Implémentation

## Vue d'ensemble

Ce document détaille l'implémentation complète du module de gestion de projets type Jira/Linear pour AIG-App.

## État actuel

✅ Créé:
- Enums: TaskStatus, Priority, ProjectStatus, TaskType
- Migrations: projects, project_tasks, sprints (partielles)

⏳ En cours:
- Migrations restantes
- Modèles Eloquent
- Controllers & Services
- Interface React/TypeScript

## Structure des fichiers créés

```
app/
├── Enums/
│   ├── TaskStatus.php ✅
│   ├── Priority.php ✅
│   ├── ProjectStatus.php ✅
│   └── TaskType.php ✅
│
database/migrations/
├── 2025_10_02_190912_create_projects_table.php ✅
├── 2025_10_02_191251_create_project_tasks_table.php ✅
├── 2025_10_02_190920_create_sprints_table.php ✅
└── (autres à créer)
```

## Migrations à compléter

### 1. Table project_roles
```php
Schema::create('project_roles', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('category')->default('technical');
    $table->string('color', 7)->default('#6b7280');
    $table->string('icon')->nullable();
    $table->json('permissions')->nullable();
    $table->boolean('can_manage_team')->default(false);
    $table->boolean('can_assign_tasks')->default(false);
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### 2. Table project_members
```php
Schema::create('project_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('project_role_id')->constrained()->cascadeOnDelete();
    $table->decimal('hourly_rate', 8, 2)->nullable();
    $table->integer('availability_percentage')->default(100);
    $table->date('started_at')->default(now());
    $table->date('ended_at')->nullable();
    $table->boolean('is_lead')->default(false);
    $table->timestamps();

    $table->unique(['project_id', 'user_id']);
});
```

### 3. Table task_comments
```php
Schema::create('task_comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('content');
    $table->foreignId('parent_id')->nullable()->constrained('task_comments')->cascadeOnDelete();
    $table->timestamps();
});
```

### 4. Table time_entries
```php
Schema::create('time_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('task_id')->constrained('project_tasks')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->integer('minutes');
    $table->text('description')->nullable();
    $table->datetime('started_at');
    $table->datetime('stopped_at')->nullable();
    $table->timestamps();
});
```

## Commandes pour créer les modèles

```bash
php artisan make:model Project
php artisan make:model ProjectTask
php artisan make:model Sprint
php artisan make:model ProjectRole
php artisan make:model ProjectMember
php artisan make:model TaskComment
php artisan make:model TimeEntry
```

## Modèle Project (exemple)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\ProjectStatus;
use App\Enums\Priority;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'status', 'priority',
        'color', 'start_date', 'end_date', 'budget',
        'project_manager_id', 'is_template', 'settings'
    ];

    protected $casts = [
        'status' => ProjectStatus::class,
        'priority' => Priority::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'is_template' => 'boolean',
        'settings' => 'json',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function sprints(): HasMany
    {
        return $this->hasMany(Sprint::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot(['project_role_id', 'hourly_rate', 'is_lead'])
                    ->withTimestamps();
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    // Accessors
    public function getProgressAttribute(): float
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;

        $completed = $this->tasks()->where('status', 'done')->count();
        return round(($completed / $total) * 100, 2);
    }
}
```

## Controllers à créer

```bash
php artisan make:controller ProjectController --resource --requests
php artisan make:controller ProjectTaskController --resource
php artisan make:controller SprintController --resource
php artisan make:controller Api/ProjectController --api
php artisan make:controller Api/TaskController --api
```

## Routes API (routes/api.php)

```php
Route::middleware('auth:sanctum')->group(function () {
    // Projects
    Route::apiResource('projects', Api\ProjectController::class);
    Route::get('projects/{project}/tasks', [Api\ProjectController::class, 'tasks']);
    Route::post('projects/{project}/members', [Api\ProjectController::class, 'addMember']);

    // Tasks
    Route::apiResource('tasks', Api\TaskController::class);
    Route::patch('tasks/{task}/status', [Api\TaskController::class, 'updateStatus']);
    Route::post('tasks/{task}/comments', [Api\TaskController::class, 'addComment']);
    Route::post('tasks/{task}/time', [Api\TaskController::class, 'logTime']);

    // Sprints
    Route::apiResource('sprints', Api\SprintController::class);
    Route::post('sprints/{sprint}/start', [Api\SprintController::class, 'start']);
    Route::post('sprints/{sprint}/complete', [Api\SprintController::class, 'complete']);
});
```

## Types TypeScript

Créer: `resources/js/Types/Project.ts`

```typescript
export interface Project {
  id: number;
  name: string;
  slug: string;
  description?: string;
  status: ProjectStatus;
  priority: Priority;
  color: string;
  start_date?: string;
  end_date?: string;
  budget?: number;
  progress: number;
  manager?: User;
  members: ProjectMember[];
  tasks_count: number;
  created_at: string;
  updated_at: string;
}

export interface ProjectTask {
  id: number;
  title: string;
  key: string;
  description?: string;
  project_id: number;
  project?: Project;
  parent_id?: number;
  assignee?: User;
  reporter: User;
  status: TaskStatus;
  priority: Priority;
  type: TaskType;
  story_points?: number;
  estimated_hours?: number;
  due_date?: string;
  labels: string[];
  created_at: string;
  updated_at: string;
}

export enum ProjectStatus {
  PLANNING = 'planning',
  ACTIVE = 'active',
  ON_HOLD = 'on_hold',
  COMPLETED = 'completed',
  CANCELLED = 'cancelled'
}

export enum TaskStatus {
  TODO = 'todo',
  IN_PROGRESS = 'in_progress',
  IN_REVIEW = 'in_review',
  BLOCKED = 'blocked',
  DONE = 'done',
  CANCELLED = 'cancelled'
}
```

## Composants React à créer

### 1. KanbanBoard
`resources/js/Components/Projects/KanbanBoard.tsx`

### 2. TaskCard
`resources/js/Components/Projects/TaskCard.tsx`

### 3. ProjectForm
`resources/js/Components/Projects/ProjectForm.tsx`

### 4. TaskModal
`resources/js/Components/Projects/TaskModal.tsx`

## Hooks personnalisés

### useProjects
```typescript
// resources/js/Hooks/useProjects.ts
export const useProjects = () => {
  const [projects, setProjects] = useState<Project[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchProjects = async () => {
    const response = await axios.get('/api/projects');
    setProjects(response.data.data);
    setLoading(false);
  };

  useEffect(() => {
    fetchProjects();
  }, []);

  return { projects, loading, refetch: fetchProjects };
};
```

### useTasks
```typescript
// resources/js/Hooks/useTasks.ts
export const useTasks = (projectId: number) => {
  const [tasks, setTasks] = useState<ProjectTask[]>([]);

  const updateTaskStatus = async (taskId: number, status: TaskStatus) => {
    await axios.patch(`/api/tasks/${taskId}/status`, { status });
    // Update local state
  };

  return { tasks, updateTaskStatus };
};
```

## Installation des dépendances

```bash
# Drag & Drop pour Kanban
npm install react-beautiful-dnd @types/react-beautiful-dnd

# Charts pour reporting
npm install recharts

# Date utilities
npm install date-fns
```

## Tests à créer

```bash
php artisan make:test ProjectTest
php artisan make:test ProjectTaskTest
php artisan make:test SprintTest
```

### Exemple de test
```php
// tests/Feature/ProjectTest.php
public function test_user_can_create_project()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/projects', [
        'name' => 'New Project',
        'description' => 'Test project',
        'status' => 'planning',
        'priority' => 'high'
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('projects', ['name' => 'New Project']);
}
```

## Seeders

```bash
php artisan make:seeder ProjectRoleSeeder
php artisan make:seeder ProjectSeeder
```

## Prochaines étapes

1. ✅ Créer les migrations restantes
2. Créer les modèles avec relations
3. Implémenter les controllers CRUD
4. Créer les services métier
5. Implémenter l'interface React
6. Ajouter les tests
7. Créer les seeders de démo

## Commandes utiles

```bash
# Exécuter les migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Reset et seed
php artisan migrate:fresh --seed

# Créer un contrôleur
php artisan make:controller NomController

# Créer un modèle avec migration
php artisan make:model NomModel -m

# Tests
php artisan test --filter=ProjectTest
```

## Notes importantes

- La table `tasks` existante est utilisée pour les programmes
- Nouvelle table `project_tasks` pour la gestion de projets
- Permissions Spatie à intégrer: `view projects`, `create projects`, etc.
- Utiliser les couleurs existantes du thème (icc-blue, etc.)
- Respecter les conventions de nommage existantes
