<?php

return [
    'statuses' => [
        'epic' => [
            'draft' => 'Entwurf',
            'ready' => 'Bereit',
            'in_progress' => 'In Bearbeitung',
            'done' => 'Erledigt',
            'archived' => 'Archiviert',
        ],
        'user_story' => [
            'backlog' => 'Backlog',
            'ready' => 'Bereit',
            'in_progress' => 'In Bearbeitung',
            'review' => 'In Review',
            'done' => 'Erledigt',
        ],
        'acceptance_criterion' => [
            'pending' => 'Ausstehend',
            'in_review' => 'In Review',
            'validated' => 'Validiert',
            'rejected' => 'Abgelehnt',
        ],
        'test_scenario' => [
            'not_run' => 'Nicht ausgeführt',
            'passed' => 'Bestanden',
            'failed' => 'Fehlgeschlagen',
            'blocked' => 'Blockiert',
        ],
    ],

    'work_types' => [
        'dev' => 'Entwicklung',
        'test' => 'Test',
        'devops' => 'DevOps',
        'design' => 'Design',
        'doc' => 'Dokumentation',
    ],

    'link_types' => [
        'blocks' => 'Blockiert',
        'relates_to' => 'Bezieht sich auf',
        'duplicates' => 'Dupliziert',
        'parent_of' => 'Übergeordnet von',
    ],

    'validation' => [
        'user_story' => [
            'status_done_forbidden_on_store' => "Der Status 'done' ist bei der Erstellung nicht zulässig. Verwenden Sie /complete, sobald alle Akzeptanzkriterien validiert sind.",
            'use_complete_endpoint' => 'Zum Abschließen einer Story verwenden Sie /complete — dieser Endpunkt prüft die Validierung aller Akzeptanzkriterien.',
        ],
        'test_scenario' => [
            'shape_required' => 'Ein Szenario muss entweder einen Gherkin-Block (given/when/then) oder eine freie Beschreibung (free_form) enthalten.',
            'shape_mutually_exclusive' => 'Ein Szenario nutzt entweder Gherkin oder eine freie Beschreibung, aber nicht beides gleichzeitig.',
        ],
    ],

    'errors' => [
        'cannot_complete_story' => 'Story kann nicht abgeschlossen werden: :count Akzeptanzkriterium/kriterien sind noch nicht validiert.',
        'active_sprint_already_exists' => 'Ein aktiver Sprint (:name) existiert bereits für dieses Projekt. Schließen Sie ihn, bevor Sie einen neuen starten.',
        'closed_sprint_cannot_accept_stories' => 'Sprint :name ist abgeschlossen und kann keine neuen Stories aufnehmen.',
        'acceptance_criterion_has_passed_tests' => 'Dieses Akzeptanzkriterium kann nicht gelöscht werden: :count Testszenario(en) sind erfolgreich durchgelaufen.',
    ],

    'labels' => [
        'epics' => 'Epics',
        'user_stories' => 'User Stories',
        'acceptance_criteria' => 'Akzeptanzkriterien',
        'test_scenarios' => 'Testszenarien',
        'story_tasks' => 'Technische Aufgaben',
    ],

    'roles' => [
        'product_owner' => 'Product Owner',
    ],
];
