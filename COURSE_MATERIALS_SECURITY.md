# Sécurité des Supports de Cours

## Vue d'ensemble

Les supports de cours sont maintenant **strictement filtrés par classe**. Seuls les étudiants inscrits dans une classe spécifique peuvent voir les supports de cette classe.

## Principe de sécurité

### 1. Isolation par classe
- Chaque support de cours appartient à **une seule classe** (`training_class_id`)
- Les étudiants sont assignés à **une classe spécifique** lors de leur inscription
- Un étudiant ne peut voir **QUE** les supports de sa propre classe

### 2. Filtrage côté serveur
Le filtrage est fait **côté serveur** dans `TrainingController::studentDashboard()` (lignes 403-408) :

```php
// Get the class for this student
$studentClass = $training->classes()
    ->where('id', $enrollment->pivot->training_class_id)  // ← Filtrage par classe de l'étudiant
    ->with(['materials' => function ($query) {
        $query->active()->ordered();  // ← Seulement les matériaux actifs
    }])
    ->first();

// Return only materials from student's class
'materials' => $studentClass?->materials ?? [],
```

### 3. Vérifications d'accès

**Pour les enseignants :**
- Peuvent voir/gérer les supports de leurs propres classes
- Admins peuvent voir/gérer tous les supports

**Pour les étudiants :**
- Ne voient QUE les supports de leur classe assignée
- Ne voient QUE les supports actifs (`is_active = true`)
- Accès en lecture seule (consultation uniquement)

## Migration des données existantes

### Problème initial
Les inscriptions (`training_enrollments`) créées avant novembre 2025 n'avaient pas de `training_class_id` assigné, donc les étudiants ne voyaient aucun support.

### Solution appliquée
Migration `2025_11_03_135739_assign_classes_to_existing_enrollments.php` :

```php
// Pour chaque inscription sans classe
$enrollments = DB::table('training_enrollments')
    ->whereNull('training_class_id')
    ->get();

foreach ($enrollments as $enrollment) {
    // Assigner la première classe disponible de la formation
    $class = DB::table('training_classes')
        ->where('training_id', $enrollment->training_id)
        ->orderBy('date')
        ->first();

    if ($class) {
        DB::table('training_enrollments')
            ->where('user_id', $enrollment->user_id)
            ->where('training_id', $enrollment->training_id)
            ->update([
                'training_class_id' => $class->id,
                'updated_at' => now(),
            ]);
    }
}
```

**Résultat** : 196 inscriptions mises à jour avec succès

## Architecture de la base de données

### Table: `training_class_materials`
```sql
- id (bigint)
- uuid (string) - Identifiant unique
- training_class_id (bigint) - FK vers training_classes
- teacher_id (bigint) - FK vers users (enseignant qui a ajouté)
- title (string)
- type (string: pdf, video, audio, powerpoint, document)
- file_path (string, nullable) - Chemin du fichier uploadé
- url (string, nullable) - URL externe
- duration (string, nullable) - Durée pour vidéos/audio
- description (text, nullable)
- order (integer) - Ordre d'affichage
- is_active (boolean) - Visible aux étudiants
- created_at, updated_at
```

### Table: `training_enrollments` (pivot)
```sql
- user_id (bigint) - FK vers users
- training_id (bigint) - FK vers trainings
- training_class_id (bigint) - FK vers training_classes ← CRUCIAL
- status (string: pending, approved, rejected)
- enrolled_at (datetime)
- motivation (text)
- payment_method (string)
```

## Flux de données

### 1. Inscription d'un étudiant
```
Étudiant → Formulaire d'inscription → TrainingController::enroll()
  ↓
Sélection d'une classe spécifique (selectedClassId)
  ↓
Création dans training_enrollments avec training_class_id
  ↓
Statut: pending → L'admin approuve → Statut: approved
```

### 2. Consultation des supports (Étudiant)
```
StudentDashboard → TrainingController::studentDashboard()
  ↓
Récupère enrollment.training_class_id de l'étudiant
  ↓
Charge SEULEMENT les matériaux de cette classe
  ↓
Filtre: is_active = true
  ↓
Retourne materials[] à l'étudiant
```

### 3. Gestion des supports (Enseignant)
```
ClassMaterials Page → TrainingClassMaterialController::index()
  ↓
Vérifie: user = teacher de la classe OU admin
  ↓
Charge tous les matériaux (actifs + inactifs)
  ↓
CRUD: Create, Update, Delete, Reorder
```

## Politique d'autorisation

### `TrainingClassMaterialPolicy`

```php
public function viewAny(User $user, TrainingClass $trainingClass): bool
{
    // Admin peut tout voir
    if ($user->hasRole(['admin', 'SuperAdmin', 'Admin'])) {
        return true;
    }

    // Enseignant peut voir les supports de sa classe
    if ($trainingClass->teacher_id === $user->id) {
        return true;
    }

    // Étudiant peut voir les supports de sa classe (si approuvé)
    return $trainingClass->training->students()
        ->where('user_id', $user->id)
        ->where('status', 'approved')
        ->where('training_class_id', $trainingClass->id)
        ->exists();
}
```

## Exemples d'accès

### Scénario 1 : Étudiant Jean inscrit à la Classe A
- ✅ Voit les supports de la Classe A (actifs)
- ❌ Ne voit PAS les supports de la Classe B
- ❌ Ne voit PAS les supports de la Classe C
- ❌ Ne voit PAS les supports inactifs

### Scénario 2 : Enseignant Marie de la Classe B
- ✅ Voit/gère les supports de la Classe B (tous)
- ❌ Ne voit PAS les supports de la Classe A
- ✅ Peut ajouter des supports à la Classe B
- ✅ Peut marquer des supports comme inactifs

### Scénario 3 : Admin Paul
- ✅ Voit TOUS les supports de TOUTES les classes
- ✅ Peut gérer tous les supports
- ✅ Peut ajouter des supports à n'importe quelle classe

## Vérification de la sécurité

### Tester l'isolation des classes

```bash
# Vérifier qu'un étudiant a une classe assignée
php artisan tinker
>>> $enrollment = \App\Models\Training::first()->students()->first();
>>> $enrollment->pivot->training_class_id; // Doit retourner un ID, pas NULL

# Vérifier les matériaux d'une classe
>>> $class = \App\Models\TrainingClass::find(1);
>>> $class->materials()->active()->count(); // Nombre de matériaux actifs

# Vérifier qu'un étudiant voit les bons matériaux
>>> $user = \App\Models\User::find(27);
>>> $training = $user->trainings()->first();
>>> $enrollment = $training->students()->where('user_id', $user->id)->first();
>>> $classId = $enrollment->pivot->training_class_id;
>>> $materials = \App\Models\TrainingClass::find($classId)->materials()->active()->get();
>>> $materials->count(); // Doit afficher le nombre de matériaux
```

## Maintenance

### Ajouter un étudiant à une classe

**Lors de l'inscription :**
Le `training_class_id` est automatiquement assigné via le formulaire d'inscription.

**Modification manuelle (admin) :**
```sql
UPDATE training_enrollments
SET training_class_id = <nouvelle_classe_id>,
    updated_at = NOW()
WHERE user_id = <user_id>
  AND training_id = <training_id>;
```

### Migrer des étudiants vers une autre classe

```sql
-- Migrer tous les étudiants de la Classe 1 vers la Classe 2
UPDATE training_enrollments
SET training_class_id = 2,
    updated_at = NOW()
WHERE training_class_id = 1;
```

## Logs et monitoring

Les opérations sur les matériaux sont loggées via **Spatie Activity Log** :
- Création de matériau
- Modification de matériau
- Suppression de matériau
- Changement de visibilité (is_active)

## Questions fréquentes

**Q: Un étudiant peut-il être dans plusieurs classes ?**
R: Non, chaque enrollment est lié à une seule classe via `training_class_id`.

**Q: Comment ajouter un support visible à toutes les classes ?**
R: Il faut créer un support pour chaque classe (duplication).

**Q: Un étudiant peut-il voir les supports inactifs ?**
R: Non, seuls les enseignants et admins voient les supports inactifs.

**Q: Que se passe-t-il si une classe n'a pas de matériaux ?**
R: L'étudiant voit "Aucun support de cours disponible pour le moment".

**Q: Comment rendre un support invisible temporairement ?**
R: Modifier `is_active = false` dans l'interface enseignant.

## Déploiement en production

1. Tirer les derniers changements :
   ```bash
   git pull origin main
   ```

2. Exécuter la migration :
   ```bash
   php artisan migrate
   ```

3. Vérifier les logs :
   ```bash
   tail -f storage/logs/laravel.log | grep "Assigned classes"
   ```

4. Vider les caches :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

## Commits associés

- `74bb03f` - Fix: assign classes to existing enrollments (Migration)
- `0a30069` - Feat: make material title and icon clickable
- `0af18cc` - Fix: load training relation before accessing in materials controller
- `b0b96d9` - Implement training class resources (Initial implementation)
