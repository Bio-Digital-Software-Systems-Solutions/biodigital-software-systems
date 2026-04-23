import { User } from './index';

// ---- Enums (mirror of PHP App\Enums\Agile\*) ----

export enum EpicStatus {
    DRAFT = 'draft',
    READY = 'ready',
    IN_PROGRESS = 'in_progress',
    DONE = 'done',
    ARCHIVED = 'archived',
}

export enum UserStoryStatus {
    BACKLOG = 'backlog',
    READY = 'ready',
    IN_PROGRESS = 'in_progress',
    REVIEW = 'review',
    DONE = 'done',
}

export enum AcceptanceCriterionStatus {
    PENDING = 'pending',
    IN_REVIEW = 'in_review',
    VALIDATED = 'validated',
    REJECTED = 'rejected',
}

export enum TestScenarioExecutionStatus {
    NOT_RUN = 'not_run',
    PASSED = 'passed',
    FAILED = 'failed',
    BLOCKED = 'blocked',
}

export enum StoryTaskType {
    DEV = 'dev',
    TEST = 'test',
    DEVOPS = 'devops',
    DESIGN = 'design',
    DOC = 'doc',
}

export enum WorkItemLinkType {
    BLOCKS = 'blocks',
    RELATES_TO = 'relates_to',
    DUPLICATES = 'duplicates',
    PARENT_OF = 'parent_of',
}

// ---- Resource interfaces (shape mirrors App\Http\Resources\Agile\*) ----

export interface AgileUserLite {
    id: number;
    name: string;
}

export interface Epic {
    id: number;
    uuid: string;
    project_id: number;
    owner_id: number;
    title: string;
    description: string | null;
    business_value: string | null;
    status: EpicStatus;
    status_label: string;
    priority: number;
    target_date: string | null;
    labels: string[];
    user_stories_count?: number;
    completion_percentage: number;
    owner?: AgileUserLite;
    created_at: string;
    updated_at: string;
}

export interface UserStory {
    id: number;
    uuid: string;
    epic_id: number | null;
    sprint_id: number | null;
    assignee_id: number | null;
    reporter_id: number;
    title: string;
    as_a: string;
    i_want: string;
    so_that: string;
    story_points: number | null;
    priority: number;
    status: UserStoryStatus;
    status_label: string;
    completed_at: string | null;
    can_be_completed?: boolean;
    acceptance_criteria?: AcceptanceCriterion[];
    acceptance_criteria_count?: number;
    story_tasks_count?: number;
    created_at: string;
    updated_at: string;
}

export interface AcceptanceCriterion {
    id: number;
    user_story_id: number;
    position: number;
    title: string;
    description: string;
    status: AcceptanceCriterionStatus;
    status_label: string;
    validated_by: number | null;
    validated_at: string | null;
    validation_notes: string | null;
    test_scenarios_count?: number;
    validator?: AgileUserLite | null;
    created_at: string;
    updated_at: string;
}

export interface TestScenario {
    id: number;
    acceptance_criterion_id: number;
    title: string;
    given: string | null;
    when: string | null;
    then: string | null;
    free_form: string | null;
    automated_test_ref: string | null;
    execution_status: TestScenarioExecutionStatus;
    execution_status_label: string;
    last_executed_by: number | null;
    last_executed_at: string | null;
    failure_notes: string | null;
    is_gherkin: boolean;
    created_at: string;
    updated_at: string;
}

export interface StoryTask {
    id: number;
    uuid: string;
    user_story_id: number;
    title: string;
    description: string | null;
    work_type: StoryTaskType | null;
    work_type_label: string | null;
    status_id: number | null;
    priority: string | null;
    assigned_to: number | null;
    estimated_hours: number | null;
    actual_hours: number | null;
    created_at: string;
    updated_at: string;
}

export interface WorkItemLink {
    id: number;
    source_type: string;
    source_id: number;
    target_type: string;
    target_id: number;
    link_type: WorkItemLinkType;
    created_by: number;
}

export interface WorkItemComment {
    id: number;
    commentable_type: string;
    commentable_id: number;
    user_id: number;
    parent_id: number | null;
    body: string;
    user?: User;
    replies?: WorkItemComment[];
    created_at: string;
    updated_at: string;
}

// ---- Filter param shapes ----

export interface EpicFilters {
    project_id?: number | string;
    status?: EpicStatus | string;
    owner_id?: number | string;
}

export interface UserStoryFilters {
    epic_id?: number | string;
    sprint_id?: number | string;
    assignee_id?: number | string;
    status?: UserStoryStatus | string;
}

// ---- Display helpers ----

export const epicStatusColor: Record<EpicStatus, string> = {
    [EpicStatus.DRAFT]: 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    [EpicStatus.READY]: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    [EpicStatus.IN_PROGRESS]: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    [EpicStatus.DONE]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
    [EpicStatus.ARCHIVED]: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200',
};

export const userStoryStatusColor: Record<UserStoryStatus, string> = {
    [UserStoryStatus.BACKLOG]: 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    [UserStoryStatus.READY]: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
    [UserStoryStatus.IN_PROGRESS]: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-100',
    [UserStoryStatus.REVIEW]: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    [UserStoryStatus.DONE]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
};

export const acceptanceCriterionStatusColor: Record<AcceptanceCriterionStatus, string> = {
    [AcceptanceCriterionStatus.PENDING]: 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    [AcceptanceCriterionStatus.IN_REVIEW]: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
    [AcceptanceCriterionStatus.VALIDATED]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
    [AcceptanceCriterionStatus.REJECTED]: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
};

export const testScenarioStatusColor: Record<TestScenarioExecutionStatus, string> = {
    [TestScenarioExecutionStatus.NOT_RUN]: 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100',
    [TestScenarioExecutionStatus.PASSED]: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
    [TestScenarioExecutionStatus.FAILED]: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
    [TestScenarioExecutionStatus.BLOCKED]: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-100',
};
