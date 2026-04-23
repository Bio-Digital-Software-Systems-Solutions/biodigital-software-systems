<?php

return [
    'statuses' => [
        'epic' => [
            'draft' => 'Draft',
            'ready' => 'Ready',
            'in_progress' => 'In progress',
            'done' => 'Done',
            'archived' => 'Archived',
        ],
        'user_story' => [
            'backlog' => 'Backlog',
            'ready' => 'Ready',
            'in_progress' => 'In progress',
            'review' => 'In review',
            'done' => 'Done',
        ],
        'acceptance_criterion' => [
            'pending' => 'Pending',
            'in_review' => 'In review',
            'validated' => 'Validated',
            'rejected' => 'Rejected',
        ],
        'test_scenario' => [
            'not_run' => 'Not run',
            'passed' => 'Passed',
            'failed' => 'Failed',
            'blocked' => 'Blocked',
        ],
    ],

    'work_types' => [
        'dev' => 'Development',
        'test' => 'Testing',
        'devops' => 'DevOps',
        'design' => 'Design',
        'doc' => 'Documentation',
    ],

    'link_types' => [
        'blocks' => 'Blocks',
        'relates_to' => 'Relates to',
        'duplicates' => 'Duplicates',
        'parent_of' => 'Parent of',
    ],

    'validation' => [
        'user_story' => [
            'status_done_forbidden_on_store' => "Status 'done' is not allowed on create. Use the /complete endpoint once acceptance criteria are validated.",
            'use_complete_endpoint' => 'To complete a story, call the /complete endpoint — it enforces that every acceptance criterion is validated.',
        ],
        'test_scenario' => [
            'shape_required' => 'A scenario must include either a Gherkin block (given/when/then) or a free-form description.',
            'shape_mutually_exclusive' => 'A scenario uses either Gherkin or free-form, not both.',
        ],
    ],

    'errors' => [
        'cannot_complete_story' => 'Cannot complete the story: :count acceptance criterion/criteria are not validated yet.',
        'active_sprint_already_exists' => 'An active sprint (:name) already exists for this project. Close it before starting a new one.',
        'closed_sprint_cannot_accept_stories' => 'Sprint :name is completed and cannot accept new stories.',
        'acceptance_criterion_has_passed_tests' => 'This acceptance criterion cannot be deleted: :count test scenario(s) have passed against it.',
    ],

    'labels' => [
        'epics' => 'Epics',
        'user_stories' => 'User Stories',
        'acceptance_criteria' => 'Acceptance Criteria',
        'test_scenarios' => 'Test Scenarios',
        'story_tasks' => 'Technical Tasks',
    ],

    'roles' => [
        'product_owner' => 'Product Owner',
    ],
];
