# Test du Bouton Retour Intelligent

## Fonctionnalité
Le bouton "Retour" sur la page de détail d'une tâche (`/tasks/{id}`) s'adapte intelligemment en fonction de la page d'origine.

## Scénarios de Test

### 1. Depuis la Liste des Tâches (/tasks)
**Étapes:**
1. Aller sur http://localhost:8000/tasks
2. Cliquer sur n'importe quelle tâche
3. Vérifier que le bouton affiche "Retour aux Tâches"
4. Cliquer sur le bouton
5. **Résultat attendu:** Vous revenez sur /tasks

### 2. Depuis le Kanban Board (/kanban)
**Étapes:**
1. Aller sur http://localhost:8000/kanban
2. Cliquer sur une tâche dans le board
3. Vérifier que le bouton affiche "Retour au Kanban"
4. Cliquer sur le bouton
5. **Résultat attendu:** Vous revenez sur /kanban

### 3. Depuis une Page Projet (/projects/{id})
**Étapes:**
1. Aller sur http://localhost:8000/projects/1 (ou tout autre ID)
2. Cliquer sur une tâche du projet
3. Vérifier que le bouton affiche "Retour au Projet"
4. Cliquer sur le bouton
5. **Résultat attendu:** Vous revenez sur /projects/1

### 4. Depuis le Gantt (/gantt)
**Étapes:**
1. Aller sur http://localhost:8000/gantt
2. Cliquer sur une tâche
3. Vérifier que le bouton affiche "Retour au Gantt"
4. Cliquer sur le bouton
5. **Résultat attendu:** Vous revenez sur /gantt

### 5. Accès Direct (copier/coller URL)
**Étapes:**
1. Coller directement http://localhost:8000/tasks/5 dans la barre d'adresse
2. Vérifier que le bouton affiche "Retour aux Tâches" (comportement par défaut)
3. Cliquer sur le bouton
4. **Résultat attendu:** Vous allez sur /tasks

### 6. Avec Paramètre from Explicite
**Étapes:**
1. Aller sur http://localhost:8000/tasks/5?from=/kanban
2. Vérifier que le bouton affiche "Retour au Kanban"
3. Cliquer sur le bouton
4. **Résultat attendu:** Vous allez sur /kanban

## Implémentation Technique

### Logique de Détection (dans Show.tsx)
1. **Priorité 1:** Paramètre `from` dans l'URL (`?from=/kanban`)
2. **Priorité 2:** Document.referrer (page précédente)
3. **Priorité 3:** Défaut = `/tasks`

### Pages Modifiées
- ✅ `/resources/js/Pages/ProjectTasks/Show.tsx` - Ajout de la logique intelligente
- ✅ `/resources/js/Pages/ProjectTasks/Index.tsx` - Ajout du paramètre `from=/tasks`

### Pages à Modifier (optionnel pour amélioration)
- `/resources/js/Pages/Projects/Show.tsx` - Ajouter `from=/projects/{id}`
- `/resources/js/Pages/Gantt.tsx` - Ajouter `from=/gantt`
- Composant Kanban - Ajouter `from=/kanban` si cliquable

## Code Clé

```typescript
const getBackUrl = () => {
    // 1. Vérifier paramètre 'from'
    const urlParams = new URLSearchParams(window.location.search);
    const fromParam = urlParams.get('from');
    if (fromParam) return fromParam;

    // 2. Vérifier document.referrer
    const referrer = document.referrer;
    if (referrer) {
        const referrerPath = new URL(referrer).pathname;
        if (referrerPath.match(/^\/projects\/\d+$/)) return referrerPath;
        if (referrerPath === '/kanban') return '/kanban';
        if (referrerPath === '/tasks') return '/tasks';
        if (referrerPath === '/gantt') return '/gantt';
    }

    // 3. Défaut
    return '/tasks';
};
```
