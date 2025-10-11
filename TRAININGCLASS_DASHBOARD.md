# TrainingClass Dashboard - Documentation

## Vue d'ensemble

Le TrainingClass Dashboard est une interface complète de gestion des classes de formation, incluant la gestion des étudiants, des présences, des horaires et des statistiques.

## Fonctionnalités principales

### 1. **Gestion des Classes**
- ✅ Affichage de toutes les classes avec informations détaillées
- ✅ Création de nouvelles classes
- ✅ Modification des classes existantes
- ✅ Suppression de classes
- ✅ Visualisation du statut (À venir / Passée)
- ✅ Affichage du nombre d'étudiants et de la capacité maximale

### 2. **Gestion des Étudiants**
- ✅ Liste des étudiants par classe
- ✅ Affichage des notes avec code couleur
  - Vert: ≥ 85/100
  - Jaune: ≥ 70/100
  - Rouge: < 70/100
- ✅ Taux de présence par étudiant
- ✅ Filtrage par classe

### 3. **Emploi du Temps**
- ✅ Affichage des horaires groupés par formation
- ✅ Classes futures uniquement
- ✅ Informations complètes (date, jour, horaire, salle, enseignant)
- ✅ Organisation claire par formation

### 4. **Contrôle des Présences**
- ✅ Interface de prise de présences par classe
- ✅ 3 statuts: Présent, Absent, Excusé
- ✅ Champ pour noter la raison des absences
- ✅ Mise à jour automatique du taux de présence
- ✅ Enregistrement en masse

### 5. **Statistiques**
- ✅ Métriques globales:
  - Nombre total de classes
  - Nombre de classes à venir
  - Total d'étudiants
  - Moyenne générale
  - Taux de présence global
- ✅ Graphiques de répartition des notes par formation
- ✅ Top 5 des meilleurs étudiants
- ✅ Barres de progression visuelles

## Structure des fichiers

```
resources/js/Pages/TrainingClass/
├── Dashboard.tsx                    # Composant principal
├── types.ts                         # Interfaces TypeScript
└── Components/
    ├── ClassesView.tsx             # Vue gestion des classes
    ├── StudentsView.tsx            # Vue gestion des étudiants
    ├── ScheduleView.tsx            # Vue emploi du temps
    ├── AttendanceView.tsx          # Vue contrôle des présences
    ├── StatsView.tsx               # Vue statistiques
    ├── AddClassModal.tsx           # Modal création de classe
    └── EditClassModal.tsx          # Modal édition de classe
```

## Routes API

### Classes
- `GET /training-classes` - Afficher le dashboard
- `POST /training-classes` - Créer une nouvelle classe
- `PUT /training-classes/{id}` - Mettre à jour une classe
- `DELETE /training-classes/{id}` - Supprimer une classe

### Étudiants et Présences
- `GET /training-classes/{id}/students` - Obtenir les étudiants d'une classe
- `POST /training-classes/{id}/attendance` - Enregistrer les présences

### Données
- `GET /training-classes/schedules` - Obtenir les horaires
- `GET /training-classes/statistics` - Obtenir les statistiques

## Utilisation

### Accès au Dashboard
1. Se connecter à l'application
2. Cliquer sur "Gestion Classes" dans le menu latéral
3. Le dashboard s'affiche avec l'onglet "Classes" actif

### Créer une nouvelle classe
1. Cliquer sur "Nouvelle Classe" en haut à droite
2. Remplir le formulaire:
   - Formation (obligatoire)
   - Enseignant (optionnel)
   - Date (obligatoire)
   - Horaire de début et fin (obligatoire)
   - Salle (optionnel)
   - Nombre max d'étudiants (optionnel)
   - Notes (optionnel)
3. Cliquer sur "Créer la classe"

### Modifier une classe
1. Dans l'onglet "Classes", cliquer sur "Modifier" sur une carte de classe
2. Modifier les informations souhaitées
3. Cliquer sur "Mettre à jour"

### Marquer les présences
1. Aller dans l'onglet "Présences"
2. Sélectionner une classe dans la liste déroulante
3. Pour chaque étudiant:
   - Cocher le statut (Présent/Absent/Excusé)
   - Si absent ou excusé, renseigner la raison
4. Cliquer sur "Enregistrer les présences"

### Consulter les statistiques
1. Aller dans l'onglet "Statistiques"
2. Visualiser:
   - Les 4 métriques principales en haut
   - Le graphique de répartition des notes à gauche
   - Le top 5 des étudiants à droite

## Technologies utilisées

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: React 18 avec TypeScript
- **Styling**: TailwindCSS + shadcn/ui
- **Icons**: Lucide React
- **Routing**: Inertia.js

## Fonctionnalités avancées

### Calcul automatique du taux de présence
Le système calcule automatiquement le taux de présence de chaque étudiant après chaque enregistrement:
- Compte le nombre total de classes passées
- Compte le nombre de présences
- Calcule le pourcentage: (présences / total) × 100

### Code couleur adaptatif
- **Classes**: Vert (À venir) / Gris (Passée)
- **Notes**: Vert (≥85) / Jaune (≥70) / Rouge (<70)
- **Statistiques**: Couleurs personnalisées par métrique

### Mode sombre
Tous les composants supportent le mode sombre de l'application.

## Améliorations futures possibles

1. **Export de données**
   - Export Excel des listes de présences
   - Export PDF des statistiques
   - Export CSV des notes

2. **Notifications**
   - Rappel de classe pour les enseignants
   - Alerte absence pour les étudiants
   - Rapport hebdomadaire automatique

3. **Filtres avancés**
   - Filtrer par enseignant
   - Filtrer par période
   - Filtrer par taux de présence

4. **Visualisations**
   - Graphiques de présence dans le temps
   - Courbes d'évolution des notes
   - Calendrier visuel des classes

5. **Intégration**
   - Synchronisation avec Google Calendar
   - Envoi d'emails automatiques
   - Génération de certificats

## Support

Pour toute question ou problème:
1. Vérifier les logs Laravel: `storage/logs/laravel.log`
2. Vérifier la console du navigateur (F12)
3. Vérifier que toutes les migrations sont exécutées
4. Vérifier que les seeders ont créé les données de test

## Auteur

Développé avec Laravel 12, React 18, TypeScript et TailwindCSS pour une expérience utilisateur moderne et performante.
