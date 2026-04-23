<?php

return [
    'statuses' => [
        'epic' => [
            'draft' => 'Brouillon',
            'ready' => 'Prêt',
            'in_progress' => 'En cours',
            'done' => 'Terminé',
            'archived' => 'Archivé',
        ],
        'user_story' => [
            'backlog' => 'Backlog',
            'ready' => 'Prête',
            'in_progress' => 'En cours',
            'review' => 'En revue',
            'done' => 'Terminée',
        ],
        'acceptance_criterion' => [
            'pending' => 'En attente',
            'in_review' => 'En revue',
            'validated' => 'Validé',
            'rejected' => 'Rejeté',
        ],
        'test_scenario' => [
            'not_run' => 'Non exécuté',
            'passed' => 'Réussi',
            'failed' => 'Échoué',
            'blocked' => 'Bloqué',
        ],
    ],

    'work_types' => [
        'dev' => 'Développement',
        'test' => 'Test',
        'devops' => 'DevOps',
        'design' => 'Design',
        'doc' => 'Documentation',
    ],

    'link_types' => [
        'blocks' => 'Bloque',
        'relates_to' => 'Est lié à',
        'duplicates' => 'Duplique',
        'parent_of' => 'Parent de',
    ],

    'validation' => [
        'user_story' => [
            'status_done_forbidden_on_store' => "Le statut 'done' n'est pas autorisé à la création. Utilisez l'endpoint /complete après validation des critères.",
            'use_complete_endpoint' => "Pour terminer une story, utilisez l'endpoint /complete (il vérifie que tous les critères d'acceptation sont validés).",
        ],
        'test_scenario' => [
            'shape_required' => 'Un scénario doit comporter soit un bloc Gherkin (given/when/then), soit une description libre (free_form).',
            'shape_mutually_exclusive' => 'Un scénario utilise soit Gherkin, soit une description libre, mais pas les deux à la fois.',
        ],
    ],

    'errors' => [
        'cannot_complete_story' => "Impossible de terminer la story : :count critère(s) d'acceptation ne sont pas encore validés.",
        'active_sprint_already_exists' => "Un sprint actif (:name) existe déjà pour ce projet. Fermez-le avant d'en démarrer un autre.",
        'closed_sprint_cannot_accept_stories' => "Le sprint :name est terminé et n'accepte plus de nouvelles stories.",
        'acceptance_criterion_has_passed_tests' => "Ce critère d'acceptation ne peut pas être supprimé : :count scénario(s) de test ont été exécutés avec succès.",
    ],

    'labels' => [
        'epics' => 'Epics',
        'user_stories' => 'User Stories',
        'acceptance_criteria' => "Critères d'acceptation",
        'test_scenarios' => 'Scénarios de test',
        'story_tasks' => 'Tâches techniques',
    ],

    'roles' => [
        'product_owner' => 'Product Owner',
    ],
];
