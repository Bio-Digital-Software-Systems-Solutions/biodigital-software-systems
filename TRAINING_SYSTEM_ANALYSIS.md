# Training System - Complete File Structure and Analysis

## Overview
The AIG-App includes a comprehensive training management system with models, controllers, React components, migrations, policies, and routes. This document provides a complete inventory of all training-related files.

---

## 1. BACKEND - MODELS (App/Models)

### Core Training Models:
- **Training.php** - Main training model
- **TrainingClass.php** - Individual class instances of trainings
- **TrainingEnrollment.php** - Student enrollment records in trainings
- **TrainingTopic.php** - Topics/modules within a training
- **TrainingMaterial.php** - Training materials (deprecated - see TrainingClassMaterial)
- **TrainingClassMaterial.php** - Materials associated with training classes (replaces TrainingMaterial)
- **TrainingEvaluation.php** - Evaluation records for trainings
- **TrainingClassSchedule.php** - Weekly/recurring schedules for training classes
- **Attendance.php** - Student attendance records
- **Evaluation.php** - Student grade/evaluation records

---

## 2. BACKEND - CONTROLLERS (App/Http/Controllers)

### Main Controllers:

#### TrainingController.php
**Location:** `/Users/elmarce/Dev/icc-munich/app/Http/Controllers/TrainingController.php`

**Key Methods:**
- `adminIndex()` - List all trainings (admin view) with filters
- `index()` - Public API endpoint for landing page
- `show()` - Display single training with quizzes
- `create()` - Show training creation form
- `store()` - Create new training
- `edit()` - Edit training form
- `update()` - Update training
- `destroy()` - **DELETE training** (authorization required)
- `enroll()` - Student enrollment
- `studentDashboard()` - Student training dashboard
- `teacherDashboard()` - Teacher training dashboard with statistics
- `teacherFormations()` - Teacher's trainings (API)
- `teacherStudents()` - Teacher's students (API)
- `teacherAttendance()` - Attendance data (API)
- `markAttendance()` - Mark attendance
- `teacherEvaluations()` - Get evaluations
- `gradeStudent()` - Grade a student

**Delete Implementation (Line 365-376):**
```php
public function destroy(Training $training)
{
    $this->authorize('delete', $training);
    $training->delete();
    CacheService::forgetPattern('trainings');
    return redirect()->route('admin.trainings.index')
        ->with('success', 'Formation supprimée avec succès.');
}
```

#### TrainingClassController.php
**Location:** `/Users/elmarce/Dev/icc-munich/app/Http/Controllers/TrainingClassController.php`

**Key Methods:**
- `index()` - List all training classes
- `show()` - Display single class details
- `store()` - Create new training class
- `update()` - Update training class
- `destroy()` - **DELETE training class** (Line 333-341)
- `students()` - Get students for a class
- `markAttendance()` - Mark class attendance
- `getClassSchedules()` - Get schedules for a class
- `weekSchedule()` - Get week schedule
- `storeSchedule()` - Create schedule
- `updateSchedule()` - Update schedule
- `destroySchedule()` - **DELETE schedule** (Line 518-526)
- `scheduleAttendance()` - Get attendance for schedule
- `markScheduleAttendance()` - Mark schedule attendance
- `trainingStudents()` - Get training students
- `studentAttendanceHistory()` - Attendance history
- `statistics()` - Dashboard statistics

**Delete Training Class Implementation (Line 333-341):**
```php
public function destroy(TrainingClass $trainingClass)
{
    $trainingClass->delete();
    return response()->json([
        'success' => true,
        'message' => 'Classe supprimée avec succès',
    ]);
}
```

#### TrainingClassMaterialController.php
**Location:** `/Users/elmarce/Dev/icc-munich/app/Http/Controllers/TrainingClassMaterialController.php`

**Key Methods:**
- `index()` - List materials for a class
- `store()` - Create material
- `show()` - Display material details
- `update()` - Update material
- `destroy()` - **DELETE material** (Line 174-186)
- `studentIndex()` - List materials for students
- `download()` - Download/stream material
- `reorder()` - Reorder materials

**Delete Material Implementation (Line 174-186):**
```php
public function destroy(TrainingClass $trainingClass, TrainingClassMaterial $material)
{
    $this->authorize('delete', $material);
    
    if ($material->file_path) {
        Storage::disk('public')->delete($material->file_path);
    }
    
    $material->delete();
    return back()->with('success', 'Support de cours supprimé avec succès.');
}
```

---

## 3. BACKEND - POLICIES (App/Policies)

### TrainingPolicy.php
**Location:** `/Users/elmarce/Dev/icc-Munich/app/Policies/TrainingPolicy.php`

**Authorization Methods:**
- `viewAny()` - All authenticated users
- `view()` - Active trainings for all; inactive only for Admin/Teacher
- `create()` - Admin, SuperAdmin, Teacher
- `update()` - Admin, SuperAdmin, or assigned Teacher
- `delete()` - **Admin, SuperAdmin only** (Line 54-58)
- `enroll()` - All authenticated users
- `restore()` - Admin, SuperAdmin
- `forceDelete()` - Admin, SuperAdmin

**Delete Authorization (Line 54-58):**
```php
public function delete(User $user, Training $training): bool
{
    return $user->hasAnyRole(['Admin', 'SuperAdmin']);
}
```

### TrainingClassMaterialPolicy.php
**Location:** `/Users/elmarce/Dev/icc-munich/app/Policies/TrainingClassMaterialPolicy.php`

**Authorization Methods:**
- `viewAny()` - Admin, Class Teacher, Enrolled Students
- `view()` - Admin, Class Teacher, Enrolled Students (active materials)
- `create()` - Admin, Class Teacher
- `update()` - Admin, Material Uploader Teacher
- `delete()` - **Admin or Material Uploader Teacher** (Line 99-107)
- `download()` - Same as view
- `restore()` - Admin or Material Uploader
- `forceDelete()` - Admin or Material Uploader

**Delete Authorization (Line 99-107):**
```php
public function delete(User $user, TrainingClassMaterial $trainingClassMaterial): bool
{
    if ($user->hasRole(['admin', 'SuperAdmin', 'Admin'])) {
        return true;
    }
    return $trainingClassMaterial->teacher_id === $user->id;
}
```

---

## 4. DATABASE - MIGRATIONS

### Core Training Tables:
- `/database/migrations/2025_10_04_144431_create_trainings_tables.php` - Main tables
- `/database/migrations/2025_10_04_224624_add_training_class_id_to_training_enrollments_table.php`
- `/database/migrations/2025_10_05_051624_add_teacher_id_to_training_classes_table.php`
- `/database/migrations/2025_10_05_051648_add_teacher_id_to_trainings_table.php`
- `/database/migrations/2025_10_05_051431_create_attendances_table.php`
- `/database/migrations/2025_10_05_051437_create_evaluations_table.php`

### Training Class Materials:
- `/database/migrations/2025_11_02_005729_create_training_class_materials_table.php`
- `/database/migrations/2025_11_02_005855_migrate_training_materials_to_class_materials.php`

### Schedules & Other:
- `/database/migrations/2025_10_06_061606_add_max_students_to_training_classes_table.php`
- `/database/migrations/2025_10_06_092308_create_training_class_schedules_table.php`
- `/database/migrations/2025_10_06_092425_add_training_class_schedule_id_to_attendances_table.php`
- `/database/migrations/2025_10_07_085743_add_rejection_reason_to_training_enrollments_table.php`
- `/database/migrations/2025_10_07_101706_update_attendances_unique_constraint.php`
- `/database/migrations/2025_10_09_163410_add_uuid_to_training_classes_table.php`
- `/database/migrations/2025_11_03_135739_assign_classes_to_existing_enrollments.php`
- `/database/migrations/2025_11_04_create_quiz_training_class_table.php`
- `/database/migrations/2025_11_04_create_quiz_training_class_material_table.php`

---

## 5. DATABASE - FACTORIES & SEEDERS

### Factories:
- `database/factories/TrainingFactory.php`
- `database/factories/TrainingClassFactory.php`
- `database/factories/TrainingClassScheduleFactory.php`
- `database/factories/TrainingTopicFactory.php`
- `database/factories/TrainingMaterialFactory.php` (deprecated)
- `database/factories/TrainingClassMaterialFactory.php`
- `database/factories/TrainingEnrollmentFactory.php`
- `database/factories/TrainingEvaluationFactory.php`

### Seeders:
- `database/seeders/TrainingSeeder.php`
- `database/seeders/TrainingClassSeeder.php`
- `database/seeders/TrainingClassScheduleSeeder.php`
- `database/seeders/TrainingTopicSeeder.php`
- `database/seeders/TrainingMaterialSeeder.php` (deprecated)

---

## 6. FRONTEND - REACT COMPONENTS (resources/js)

### Training Management Pages:
- `/resources/js/Pages/Training/Index.tsx` - Admin training list with filters, search, multiple view modes
- `/resources/js/Pages/Training/Show.tsx` - Single training details
- `/resources/js/Pages/Training/Create.tsx` - Create training form
- `/resources/js/Pages/Training/Edit.tsx` - Edit training form
- `/resources/js/Pages/Training/ClassMaterials.tsx` - Materials management page

### Training Class Pages:
- `/resources/js/Pages/TrainingClass/Show.tsx` - Single class view
- `/resources/js/Pages/TrainingClass/Dashboard.tsx` - Class management dashboard
- `/resources/js/Pages/TrainingClass/Components/ClassesView.tsx`
- `/resources/js/Pages/TrainingClass/Components/AddClassModal.tsx` - Create class modal
- `/resources/js/Pages/TrainingClass/Components/EditClassModal.tsx` - Edit class modal
- `/resources/js/Pages/TrainingClass/Components/StudentsView.tsx`
- `/resources/js/Pages/TrainingClass/Components/EnrollmentsView.tsx`
- `/resources/js/Pages/TrainingClass/Components/StatsView.tsx`
- `/resources/js/Pages/TrainingClass/Components/ScheduleView.tsx`
- `/resources/js/Pages/TrainingClass/Components/AttendanceView.tsx`

### Materials Components:
- `/resources/js/Components/Training/ClassMaterials/ClassMaterialsList.tsx` - Main materials list with delete
- `/resources/js/Components/Training/ClassMaterials/MaterialCard.tsx` - Individual material card
- `/resources/js/Components/Training/ClassMaterials/AddMaterialModal.tsx` - Create material modal
- `/resources/js/Components/Training/ClassMaterials/EditMaterialModal.tsx` - Edit material modal

### Dashboard Pages:
- `/resources/js/Pages/StudentDashboard.tsx` - Student training dashboard
- `/resources/js/Pages/TeacherDashboard.tsx` - Teacher training dashboard
- `/resources/js/Pages/UserDashboard.tsx` - General user dashboard
- `/resources/js/Components/LandingPage/TrainingBrowseSection.tsx` - Landing page training browse

### Utility Components:
- `/resources/js/Pages/TrainingClass/types.ts` - TypeScript types for training classes

---

## 7. FRONTEND - DELETE IMPLEMENTATION PATTERNS

### Pattern 1: Training Index Page (Training/Index.tsx)
**Lines 307-314, 374-381, 436-443**

**Current Issue:** Delete button is rendering but NOT performing any action:
```tsx
{canDeleteTrainings && (
    <button
        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
        title="Supprimer"
    >
        <TrashIcon className="h-5 w-5" />
    </button>
)}
```

**Status:** INCOMPLETE - Button renders but has no onClick handler or functionality

---

### Pattern 2: ClassMaterialsList Component (ClassMaterialsList.tsx)
**Status:** CORRECT IMPLEMENTATION - Model for other components

**Key Features:**
1. State management for delete confirmation:
```tsx
const [materialToDelete, setMaterialToDelete] = useState<Material | null>(null);
```

2. Delete handler:
```tsx
const handleDelete = (material: Material) => {
    setMaterialToDelete(material);
};
```

3. Confirmation execution:
```tsx
const confirmDelete = () => {
    if (!materialToDelete) return;

    router.delete(
        route('training-classes.materials.destroy', [trainingClass.uuid, materialToDelete.uuid]),
        {
            onSuccess: () => {
                toast.success('Support de cours supprimé avec succès');
                fetchMaterials();
                setMaterialToDelete(null);
            },
            onError: (errors) => {
                toast.error('Erreur lors de la suppression');
                console.error(errors);
            },
        }
    );
};
```

4. DeleteConfirmationDialog integration:
```tsx
<DeleteConfirmationDialog
    open={!!materialToDelete}
    onOpenChange={(open) => !open && setMaterialToDelete(null)}
    onConfirm={confirmDelete}
    title="Supprimer le support de cours"
    description={`Êtes-vous sûr de vouloir supprimer "${materialToDelete?.title}" ? Cette action est irréversible.`}
/>
```

5. Delete button handler:
```tsx
onDelete={handleDelete}  // Passed to MaterialCard component
```

---

## 8. ROUTES (routes/web.php)

### Training Routes:
```
GET  /trainings                          → TrainingController@adminIndex
GET  /trainings/create                   → TrainingController@create
POST /trainings                          → TrainingController@store
GET  /trainings/{training}/edit          → TrainingController@edit
PUT  /trainings/{training}               → TrainingController@update
DELETE /trainings/{training}             → TrainingController@destroy  ← DELETE ENDPOINT
GET  /trainings/{training}               → TrainingController@show
POST /trainings/{training}/enroll        → TrainingController@enroll
```

### Training Class Routes:
```
GET  /training-classes                   → TrainingClassController@index
POST /training-classes                   → TrainingClassController@store
GET  /training-classes/{trainingClass}   → TrainingClassController@show
PUT  /training-classes/{trainingClass}   → TrainingClassController@update
DELETE /training-classes/{trainingClass} → TrainingClassController@destroy
GET  /training-classes/schedules         → TrainingClassController@schedules
```

### Material Routes:
```
GET  /training-classes/{trainingClass}/materials
POST /training-classes/{trainingClass}/materials
GET  /training-classes/{trainingClass}/materials/{material}
PUT  /training-classes/{trainingClass}/materials/{material}
DELETE /training-classes/{trainingClass}/materials/{material} ← DELETE ENDPOINT
```

---

## 9. HTTP REQUESTS (App/Http/Requests)

- `StoreTrainingClassScheduleRequest.php` - Validate schedule creation
- `UpdateTrainingClassScheduleRequest.php` - Validate schedule update

---

## 10. EMAIL NOTIFICATIONS

- `/resources/views/emails/training-enrollment-approved.blade.php`
- `/resources/views/emails/training-enrollment-rejected.blade.php`

### Mail Classes:
- `app/Mail/TrainingEnrollmentApproved.php`
- `app/Mail/TrainingEnrollmentRejected.php`

---

## 11. TESTING

### Feature Tests:
- `tests/Feature/TrainingTest.php` - Core training tests
- `tests/Feature/TrainingImageTest.php` - Image upload testing
- `tests/Feature/TrainingClassFilterTest.php` - Class filtering
- `tests/Feature/TrainingClassNPlusOneTest.php` - Query optimization
- `tests/Feature/TrainingClassScheduleTest.php` - Schedule management
- `tests/Feature/TrainingEnrollmentEmailTest.php` - Email sending
- `tests/Feature/TrainingTopicsTest.php` - Topic management
- `tests/Feature/TrainingClassMaterialControllerTest.php` - Material CRUD

---

## 12. UTILITY COMPONENTS

### Delete Confirmation Dialog
**Location:** `/Users/elmarce/Dev/icc-munich/resources/js/Components/ui/delete-confirmation-dialog.tsx`

**Status:** PROPER IMPLEMENTATION

**Features:**
- Alert icon with warning styling
- Customizable title and description
- Confirm/Cancel buttons
- Disabled state during deletion
- Dark mode support

**Props:**
```typescript
interface DeleteConfirmationDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    title: string;
    description: string;
    confirmText?: string;      // Default: "Supprimer"
    cancelText?: string;       // Default: "Annuler"
    isDeleting?: boolean;      // Disable buttons during deletion
}
```

---

## 13. ISSUES IDENTIFIED

### Issue 1: Training Index Delete Buttons Not Functional
**File:** `/resources/js/Pages/Training/Index.tsx`
**Lines:** 307-314 (table), 374-381 (list), 436-443 (grid)

**Problem:** Delete buttons render but have no onClick handler or functionality

**Required Fix:** Add delete handler similar to ClassMaterialsList pattern

### Issue 2: Missing DeleteConfirmationDialog in Training Index
**File:** `/resources/js/Pages/Training/Index.tsx`

**Problem:** No DeleteConfirmationDialog component imported or used

**Required Fix:** Import and implement DeleteConfirmationDialog

---

## 14. SUMMARY TABLE

| Component | Location | Delete Status | Authorization | Route |
|-----------|----------|----------------|----------------|-------|
| Training | Controller | ✅ Implemented | Policy: Admin/SuperAdmin | DELETE /trainings/{id} |
| Training | Frontend Index | ❌ Missing Handler | Check Permission | - |
| TrainingClass | Controller | ✅ Implemented | None | DELETE /training-classes/{id} |
| TrainingClassMaterial | Controller | ✅ Implemented | Policy: Admin/Teacher | DELETE /materials/{id} |
| TrainingClassMaterial | Frontend | ✅ Proper Pattern | Check Permission | - |
| DeleteConfirmationDialog | Component | ✅ Implemented | - | - |

---

## 15. QUICK REFERENCE - FILE PATHS

**Backend:**
- Controllers: `/Users/elmarce/Dev/icc-munich/app/Http/Controllers/`
  - `TrainingController.php`
  - `TrainingClassController.php`
  - `TrainingClassMaterialController.php`
- Policies: `/Users/elmarce/Dev/icc-munich/app/Policies/`
  - `TrainingPolicy.php`
  - `TrainingClassMaterialPolicy.php`
- Models: `/Users/elmarce/Dev/icc-munich/app/Models/`

**Frontend:**
- Pages: `/Users/elmarce/Dev/icc-munich/resources/js/Pages/`
  - `Training/`
  - `TrainingClass/`
- Components: `/Users/elmarce/Dev/icc-munich/resources/js/Components/`
  - `Training/ClassMaterials/`
  - `ui/delete-confirmation-dialog.tsx`

**Routes:**
- Web Routes: `/Users/elmarce/Dev/icc-munich/routes/web.php`

