# Permissions Admin pour les Supports de Cours

## Résumé des modifications

Les **admins** et **super admins** peuvent désormais gérer les supports de cours de **n'importe quelle classe**, pas seulement les classes dont ils sont enseignants.

## Permissions accordées

### Rôles concernés
- `admin`
- `Admin`
- `SuperAdmin`

### Actions autorisées pour les admins

1. **Voir tous les supports de cours** (même inactifs)
   - Route: `GET /training-classes/{uuid}/materials`

2. **Ajouter un support de cours à n'importe quelle classe**
   - Route: `POST /training-classes/{uuid}/materials`
   - Formulaire identique à celui des enseignants

3. **Modifier n'importe quel support de cours**
   - Route: `PUT /training-classes/{uuid}/materials/{material-uuid}`
   - Peut modifier les supports ajoutés par d'autres enseignants

4. **Supprimer n'importe quel support de cours**
   - Route: `DELETE /training-classes/{uuid}/materials/{material-uuid}`

5. **Télécharger/consulter n'importe quel support** (même inactif)
   - Route: `GET /training-class-materials/{material-uuid}/download`

6. **Restaurer des supports supprimés** (si soft delete activé)

7. **Supprimer définitivement des supports** (force delete)

## Comparaison des permissions

| Action | Enseignant | Admin/SuperAdmin | Étudiant |
|--------|-----------|------------------|----------|
| Voir supports de sa classe | ✅ | ✅ (toutes les classes) | ✅ (actifs seulement) |
| Voir supports inactifs | ✅ (sa classe) | ✅ (toutes les classes) | ❌ |
| Ajouter un support | ✅ (sa classe) | ✅ (toutes les classes) | ❌ |
| Modifier un support | ✅ (ses supports) | ✅ (tous les supports) | ❌ |
| Supprimer un support | ✅ (ses supports) | ✅ (tous les supports) | ❌ |
| Télécharger un support | ✅ (sa classe) | ✅ (toutes les classes) | ✅ (actifs de sa classe) |

## Processus pour un Admin

### Étape 1 : Accéder aux classes
1. Se connecter avec un compte Admin ou SuperAdmin
2. Aller dans "Gestion des Classes" (Training Classes)

### Étape 2 : Choisir une classe
1. Cliquer sur le bouton "Supports" (📄) de n'importe quelle classe
   - Même si l'admin n'est pas l'enseignant de cette classe

### Étape 3 : Gérer les supports
1. L'interface est identique à celle des enseignants
2. Possibilité d'ajouter, modifier ou supprimer des supports
3. Possibilité de voir les supports inactifs

## Fichiers modifiés

### Policy
- `app/Policies/TrainingClassMaterialPolicy.php`
  - Ajout de vérifications `hasRole(['admin', 'SuperAdmin', 'Admin'])` dans toutes les méthodes
  - Les admins ont tous les droits sur tous les supports de toutes les classes

### Tests
- `tests/Feature/TrainingClassMaterialControllerTest.php`
  - 6 nouveaux tests ajoutés :
    - `admin_can_add_material_to_any_class()`
    - `super_admin_can_add_material_to_any_class()`
    - `admin_can_update_any_material()`
    - `admin_can_delete_any_material()`
    - `admin_can_view_all_materials()`
    - `admin_can_view_inactive_materials()`

## Notes importantes

✅ **Sécurité** : Les étudiants ne peuvent toujours voir que les supports actifs de leur classe
✅ **Audit** : Toutes les modifications sont loggées via Spatie Activity Log
✅ **Flexibilité** : Les admins peuvent intervenir sur n'importe quelle classe en cas de besoin
✅ **Interface** : Aucune modification frontend nécessaire - les admins utilisent la même interface que les enseignants

## Cas d'usage typiques

### 1. Ajout de support par un admin
Un admin peut ajouter du matériel de formation standardisé à toutes les classes d'une formation spécifique.

### 2. Correction par un admin
Un admin peut corriger ou supprimer un support problématique uploadé par un enseignant sans avoir à contacter l'enseignant.

### 3. Gestion centralisée
Un super admin peut gérer tous les supports de cours de toutes les formations depuis un seul compte.

### 4. Support technique
Un admin peut accéder aux supports inactifs pour diagnostiquer des problèmes signalés par les étudiants.

## Vérification des permissions

Pour vérifier qu'un utilisateur a les bonnes permissions :

```php
// Dans un contrôleur ou une vue
$user->hasRole(['admin', 'SuperAdmin', 'Admin']); // true pour les admins

// Avec la policy
Gate::allows('create', [TrainingClassMaterial::class, $trainingClass]); // true pour admins
```

## Routes concernées

```php
// Toutes ces routes sont maintenant accessibles aux admins pour toutes les classes
GET    /training-classes/{uuid}/materials
POST   /training-classes/{uuid}/materials
PUT    /training-classes/{uuid}/materials/{material-uuid}
DELETE /training-classes/{uuid}/materials/{material-uuid}
GET    /training-class-materials/{material-uuid}/download
```
