# UI Guidelines - AIG App

## Règles de Design et d'Expérience Utilisateur

### 1. Confirmations de Suppression

**RÈGLE OBLIGATOIRE**: Ne jamais utiliser `confirm()` ou `window.confirm()` natif du navigateur pour les confirmations de suppression.

**✅ À FAIRE**:
- Utiliser le composant `DeleteConfirmationDialog` situé dans `/resources/js/Components/ui/delete-confirmation-dialog.tsx`
- Ce composant offre une meilleure expérience utilisateur avec:
  - Un design cohérent avec le reste de l'application
  - Support du mode sombre
  - Icône d'avertissement visuelle
  - Messages personnalisables
  - État de chargement pendant la suppression
  - Animation et transitions fluides

**❌ À ÉVITER**:
```typescript
// MAUVAIS - Ne pas faire
const handleDelete = () => {
    if (confirm('Êtes-vous sûr ?')) {
        // logique de suppression
    }
}
```

**✅ BON EXEMPLE**:
```typescript
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';

export default function MyComponent() {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [itemToDelete, setItemToDelete] = useState<number | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDeleteClick = (id: number) => {
        setItemToDelete(id);
        setDeleteDialogOpen(true);
    };

    const handleDeleteConfirm = async () => {
        if (!itemToDelete) return;

        setIsDeleting(true);
        try {
            await axios.delete(route('resource.destroy', itemToDelete));
            // Logique après suppression réussie
            setDeleteDialogOpen(false);
            setItemToDelete(null);
        } catch (error) {
            console.error('Error deleting:', error);
            alert('Erreur lors de la suppression');
        } finally {
            setIsDeleting(false);
        }
    };

    return (
        <>
            <Button onClick={() => handleDeleteClick(item.id)}>
                Supprimer
            </Button>

            <DeleteConfirmationDialog
                open={deleteDialogOpen}
                onOpenChange={setDeleteDialogOpen}
                onConfirm={handleDeleteConfirm}
                title="Êtes-vous sûr de vouloir supprimer cet élément ?"
                description="Cette action est irréversible. L'élément sera définitivement supprimé."
                isDeleting={isDeleting}
            />
        </>
    );
}
```

### Props du DeleteConfirmationDialog

| Prop | Type | Obligatoire | Description |
|------|------|-------------|-------------|
| `open` | boolean | ✓ | État d'ouverture du dialogue |
| `onOpenChange` | (open: boolean) => void | ✓ | Callback lors du changement d'état |
| `onConfirm` | () => void | ✓ | Callback lors de la confirmation |
| `title` | string | ✓ | Titre du dialogue |
| `description` | string | ✓ | Description détaillée |
| `confirmText` | string | ✗ | Texte du bouton de confirmation (défaut: "Supprimer") |
| `cancelText` | string | ✗ | Texte du bouton d'annulation (défaut: "Annuler") |
| `isDeleting` | boolean | ✗ | État de chargement (défaut: false) |

### 2. Notifications Toast

**RÈGLE OBLIGATOIRE**: Toujours utiliser des toasts pour confirmer le succès ou l'échec des opérations.

**✅ À FAIRE**:
- Utiliser `toast.success()` pour les opérations réussies
- Utiliser `toast.error()` pour les erreurs
- Utiliser `toast.warning()` pour les avertissements
- Utiliser `toast.info()` pour les informations

**❌ À ÉVITER**:
```typescript
// MAUVAIS - Ne pas faire
alert('Opération réussie!');
```

**✅ BON EXEMPLE**:
```typescript
import { toast } from 'sonner';

// Succès avec description
toast.success('Classe supprimée avec succès', {
    description: 'La classe et toutes ses données ont été supprimées.',
});

// Erreur avec description
toast.error('Erreur lors de la suppression', {
    description: 'Une erreur est survenue lors de la suppression de la classe.',
});

// Dans un try-catch
try {
    await axios.delete(route('resource.destroy', id));
    toast.success('Élément supprimé', {
        description: 'L\'élément a été supprimé avec succès.',
    });
} catch (error) {
    toast.error('Erreur', {
        description: error.message || 'Une erreur est survenue.',
    });
}
```

### 3. Autres Confirmations

Pour d'autres types de confirmations (non-destructives), utilisez le composant `Dialog` de base avec des boutons appropriés.

## Exemples d'Implémentation

Les fichiers suivants sont des références d'implémentation correcte:
- `/resources/js/Pages/TrainingClass/Components/ClassesView.tsx`

## Migration des Composants Existants

Si vous trouvez du code utilisant `confirm()` ou `window.confirm()`, veuillez le migrer vers `DeleteConfirmationDialog` en suivant le pattern ci-dessus.
