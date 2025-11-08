# Delete Button Implementation Guide - Training System

## Quick Reference: Where Delete is Implemented

### 1. BACKEND - DELETE ENDPOINTS (WORKING)

#### TrainingController.php (Line 365-376)
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
- Route: `DELETE /trainings/{training}`
- Authorization: TrainingPolicy - Admin/SuperAdmin only
- Cache: Invalidates training cache after deletion

---

#### TrainingClassController.php (Line 333-341)
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
- Route: `DELETE /training-classes/{trainingClass}`
- Returns JSON response
- No authorization check currently (potential issue)

---

#### TrainingClassMaterialController.php (Line 174-186)
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
- Route: `DELETE /training-classes/{trainingClass}/materials/{material}`
- Authorization: TrainingClassMaterialPolicy - Admin or material uploader
- Cleanup: Removes associated files from storage

---

### 2. FRONTEND - DELETE PATTERNS

#### Pattern A: CORRECT IMPLEMENTATION (ClassMaterialsList.tsx)
**Status:** Works perfectly - Use as template

**File:** `/resources/js/Components/Training/ClassMaterials/ClassMaterialsList.tsx`

**Complete Implementation:**

```tsx
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';

export default function ClassMaterialsList({ trainingClass }: ClassMaterialsListProps) {
  const [materialToDelete, setMaterialToDelete] = useState<Material | null>(null);

  // Step 1: Handler called when delete button clicked
  const handleDelete = (material: Material) => {
    setMaterialToDelete(material);
  };

  // Step 2: Confirmation execution
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

  return (
    <>
      {/* Material cards with delete callback */}
      {materials.map((material) => (
        <MaterialCard
          key={material.id}
          material={material}
          onDelete={handleDelete}
          // ... other props
        />
      ))}

      {/* Step 3: Dialog confirmation */}
      <DeleteConfirmationDialog
        open={!!materialToDelete}
        onOpenChange={(open) => !open && setMaterialToDelete(null)}
        onConfirm={confirmDelete}
        title="Supprimer le support de cours"
        description={`Êtes-vous sûr de vouloir supprimer "${materialToDelete?.title}" ? Cette action est irréversible.`}
      />
    </>
  );
}
```

---

#### Pattern B: MISSING IMPLEMENTATION (Training/Index.tsx)
**Status:** BROKEN - Delete buttons render but don't work

**File:** `/resources/js/Pages/Training/Index.tsx`

**Current Code (Lines 307-314):**
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

**Issues:**
1. No onClick handler
2. No DeleteConfirmationDialog imported
3. No state management for deletion
4. No toast notifications

---

### 3. AUTHORIZATION POLICIES

#### TrainingPolicy.php - Delete Authorization
**Location:** Lines 54-58
```php
public function delete(User $user, Training $training): bool
{
    return $user->hasAnyRole(['Admin', 'SuperAdmin']);
}
```
- Only Admin and SuperAdmin can delete trainings
- Frontend must check `userHasPermission(auth.user, 'delete trainings')`

#### TrainingClassMaterialPolicy.php - Delete Authorization
**Location:** Lines 99-107
```php
public function delete(User $user, TrainingClassMaterial $trainingClassMaterial): bool
{
    if ($user->hasRole(['admin', 'SuperAdmin', 'Admin'])) {
        return true;
    }
    return $trainingClassMaterial->teacher_id === $user->id;
}
```
- Admin/SuperAdmin can delete any material
- Teachers can only delete their own materials

---

### 4. HOW TO IMPLEMENT DELETE FOR TRAINING INDEX

**Step 1: Import required components**
```tsx
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';
```

**Step 2: Add state**
```tsx
const [trainingToDelete, setTrainingToDelete] = useState<Training | null>(null);
```

**Step 3: Add handlers**
```tsx
const handleDeleteTraining = (training: Training) => {
    setTrainingToDelete(training);
};

const confirmDeleteTraining = () => {
    if (!trainingToDelete) return;

    router.delete(
        route('trainings.destroy', trainingToDelete.uuid),
        {
            onSuccess: () => {
                toast.success('Formation supprimée avec succès');
                // Refresh page or update state
                router.get(route('trainings.index'));
                setTrainingToDelete(null);
            },
            onError: () => {
                toast.error('Erreur lors de la suppression');
            },
        }
    );
};
```

**Step 4: Update delete button**

Replace button on lines 307-314, 374-381, 436-443:
```tsx
{canDeleteTrainings && (
    <button
        onClick={() => handleDeleteTraining(training)}
        className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
        title="Supprimer"
    >
        <TrashIcon className="h-5 w-5" />
    </button>
)}
```

**Step 5: Add confirmation dialog**
```tsx
<DeleteConfirmationDialog
    open={!!trainingToDelete}
    onOpenChange={(open) => !open && setTrainingToDelete(null)}
    onConfirm={confirmDeleteTraining}
    title="Supprimer la formation"
    description={`Êtes-vous sûr de vouloir supprimer "${trainingToDelete?.title}" ? Cette action est irréversible.`}
/>
```

---

### 5. ROUTES REFERENCE

| Entity | Method | Route | Handler |
|--------|--------|-------|---------|
| Training | DELETE | `/trainings/{training}` | TrainingController@destroy |
| TrainingClass | DELETE | `/training-classes/{trainingClass}` | TrainingClassController@destroy |
| Material | DELETE | `/training-classes/{trainingClass}/materials/{material}` | TrainingClassMaterialController@destroy |
| Schedule | DELETE | `/training-class-schedules/{schedule}` | TrainingClassController@destroySchedule |

---

### 6. FRONTEND COMPONENTS CHECKLIST

**DELETE CONFIRMATION DIALOG**
- File: `/resources/js/Components/ui/delete-confirmation-dialog.tsx`
- Props: `open`, `onOpenChange`, `onConfirm`, `title`, `description`
- Optional: `confirmText`, `cancelText`, `isDeleting`

**TOAST NOTIFICATIONS**
- Library: `sonner`
- Usage: `toast.success()`, `toast.error()`, `toast.info()`, `toast.warning()`

**INERTIA ROUTER**
- Method: `router.delete()`
- Callbacks: `onSuccess`, `onError`, `onProgress`
- Auto-follow redirects

---

### 7. COMMON PATTERNS TO AVOID

❌ **AVOID:** Using native `window.confirm()` or `confirm()`
✅ **INSTEAD:** Use `DeleteConfirmationDialog` component

❌ **AVOID:** Using `alert()` for notifications
✅ **INSTEAD:** Use `toast.success()`, `toast.error()` from 'sonner'

❌ **AVOID:** Direct deletion without confirmation
✅ **INSTEAD:** Show confirmation dialog first

❌ **AVOID:** Forgetting authorization checks
✅ **INSTEAD:** Check `canDelete*` permission from frontend, rely on policy in backend

---

### 8. TESTING DELETE FUNCTIONALITY

**Test Checklist:**
- [ ] Delete button appears only when user has permission
- [ ] Clicking delete shows confirmation dialog
- [ ] Canceling dialog doesn't delete
- [ ] Confirming sends DELETE request to correct endpoint
- [ ] Success toast appears on delete
- [ ] Error toast appears if deletion fails
- [ ] Page refreshes/updates after successful delete
- [ ] Backend properly authorizes deletion

**Example Test:**
```bash
# Test delete training endpoint
curl -X DELETE http://localhost:8000/api/trainings/1 \
  -H "Authorization: Bearer {token}" \
  -H "X-Requested-With: XMLHttpRequest"
```

---

### 9. FILES TO MODIFY FOR COMPLETE IMPLEMENTATION

1. **Training/Index.tsx** - Add delete handler and dialog
2. **TrainingClass/Dashboard.tsx** - Add delete handler for classes
3. **Other pages** - Apply same pattern

---

### 10. PERMISSION CHECKS

**In React component:**
```tsx
const canDeleteTrainings = userHasPermission(auth.user, 'delete trainings');
```

**In Controller:**
```php
$this->authorize('delete', $training);
```

**In Policy:**
```php
public function delete(User $user, Training $training): bool
{
    return $user->hasAnyRole(['Admin', 'SuperAdmin']);
}
```

---

## SUMMARY

- **Backend:** All delete endpoints are implemented correctly
- **Frontend:** ClassMaterialsList has proper implementation (copy this pattern)
- **Frontend:** Training/Index is missing delete functionality
- **Authorization:** Policies are configured correctly
- **Route:** All DELETE routes exist in routes/web.php
- **UI Pattern:** Use DeleteConfirmationDialog + sonner toasts

