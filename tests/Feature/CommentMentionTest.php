<?php

use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use App\Notifications\UserMentionedInComment;
use App\Services\Comment\MentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'view programs']);
    \Spatie\Permission\Models\Permission::create(['name' => 'manage programs']);

    // Create role
    $role = \Spatie\Permission\Models\Role::create(['name' => 'ProjectManager']);
    $role->givePermissionTo('view programs');
    $role->givePermissionTo('manage programs');

    // Create test user
    $this->user = User::factory()->create();
    $this->user->assignRole('ProjectManager');

    $this->mentionService = new MentionService;
});

// ============================================
// MentionService Tests
// ============================================

describe('MentionService - parseMentions', function () {
    it('parses explicit format mentions @[Name](id)', function () {
        $content = 'Hello @[John Doe](123) and @[Jane Smith](456)!';

        $mentions = $this->mentionService->parseMentions($content);

        expect($mentions)->toBe([123, 456]);
    });

    it('returns empty array when no mentions', function () {
        $content = 'Hello world, no mentions here!';

        $mentions = $this->mentionService->parseMentions($content);

        expect($mentions)->toBe([]);
    });

    it('returns unique user IDs', function () {
        $content = '@[John Doe](123) says hello to @[John Doe](123)';

        $mentions = $this->mentionService->parseMentions($content);

        expect($mentions)->toBe([123]);
    });

    it('parses simple @mention format with first name', function () {
        $user = User::factory()->create([
            'first_name' => 'UniqueJohn',
            'last_name' => 'Doe',
        ]);

        $content = 'Hello @UniqueJohn!';

        $mentions = $this->mentionService->parseMentions($content);

        expect($mentions)->toContain($user->id);
    });

    it('parses simple @mention format with full name', function () {
        $user = User::factory()->create([
            'first_name' => 'UniqueMark',
            'last_name' => 'Smith',
        ]);

        $content = 'Hello @UniqueMark.Smith!';

        $mentions = $this->mentionService->parseMentions($content);

        expect($mentions)->toContain($user->id);
    });

    it('validates mentions against provided user collection', function () {
        $validUser = User::factory()->create(['first_name' => 'ValidUser']);
        $invalidUser = User::factory()->create(['first_name' => 'InvalidUser']);

        $validUsers = collect([$validUser]);
        $content = '@ValidUser and @InvalidUser';

        $mentions = $this->mentionService->parseMentions($content, $validUsers);

        expect($mentions)->toContain($validUser->id)
            ->not->toContain($invalidUser->id);
    });
});

describe('MentionService - validateMentionedUsers', function () {
    it('returns valid user IDs', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $userIds = [$user1->id, $user2->id, 99999];
        $result = $this->mentionService->validateMentionedUsers($userIds);

        expect($result)->toHaveCount(2)
            ->toContain($user1->id)
            ->toContain($user2->id)
            ->not->toContain(99999);
    });

    it('returns empty array for empty input', function () {
        $result = $this->mentionService->validateMentionedUsers([]);

        expect($result)->toBe([]);
    });

    it('filters against provided user collection', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $validUsers = collect([$user1, $user2]);
        $result = $this->mentionService->validateMentionedUsers(
            [$user1->id, $user2->id, $user3->id],
            $validUsers
        );

        expect($result)->toHaveCount(2)
            ->toContain($user1->id)
            ->toContain($user2->id)
            ->not->toContain($user3->id);
    });
});

describe('MentionService - getMentionableUsersForProject', function () {
    it('returns project members', function () {
        $project = Project::factory()->create();
        $member = User::factory()->create();

        $project->members()->attach($member->id);

        $users = $this->mentionService->getMentionableUsersForProject($project->id);

        expect($users->pluck('id')->toArray())->toContain($member->id);
    });

    it('returns project manager', function () {
        $manager = User::factory()->create();
        $project = Project::factory()->create([
            'project_manager_id' => $manager->id,
        ]);

        $users = $this->mentionService->getMentionableUsersForProject($project->id);

        expect($users->pluck('id')->toArray())->toContain($manager->id);
    });

    it('returns project participants', function () {
        $project = Project::factory()->create();
        $participant = User::factory()->create();

        $project->participants()->create([
            'user_id' => $participant->id,
            'role' => 'member',
        ]);

        $users = $this->mentionService->getMentionableUsersForProject($project->id);

        expect($users->pluck('id')->toArray())->toContain($participant->id);
    });
});

describe('MentionService - getMentionableUsersForTask', function () {
    it('returns task participants', function () {
        $task = Task::factory()->create();
        $participant = User::factory()->create();

        TaskParticipant::create([
            'task_id' => $task->id,
            'user_id' => $participant->id,
            'role' => 'member',
        ]);

        $users = $this->mentionService->getMentionableUsersForTask($task->id);

        expect($users->pluck('id')->toArray())->toContain($participant->id);
    });

    it('returns task assignee', function () {
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $assignee->id,
        ]);

        $users = $this->mentionService->getMentionableUsersForTask($task->id);

        expect($users->pluck('id')->toArray())->toContain($assignee->id);
    });

    it('includes project members when project context provided', function () {
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);
        $member = User::factory()->create();

        $project->members()->attach($member->id);

        $users = $this->mentionService->getMentionableUsersForTask($task->id, $project->id);

        expect($users->pluck('id')->toArray())->toContain($member->id);
    });
});

describe('MentionService - enrichContent', function () {
    it('converts simple @mentions to rich format', function () {
        $user = User::factory()->create([
            'first_name' => 'RichJohn',
            'last_name' => 'Doe',
        ]);

        $content = 'Hello @RichJohn!';
        $enriched = $this->mentionService->enrichContent($content, [$user->id]);

        expect($enriched)->toContain("@[RichJohn Doe]({$user->uuid})");
    });

    it('returns original content when no mentions', function () {
        $content = 'Hello world!';
        $enriched = $this->mentionService->enrichContent($content, []);

        expect($enriched)->toBe($content);
    });
});

describe('MentionService - extractUserIds', function () {
    it('extracts valid user IDs from array', function () {
        $result = $this->mentionService->extractUserIds([1, 2, 3, '4', null, 0, -1]);

        expect($result)->toBe([1, 2, 3, 4]);
    });

    it('returns empty array for null input', function () {
        $result = $this->mentionService->extractUserIds(null);

        expect($result)->toBe([]);
    });

    it('removes duplicates', function () {
        $result = $this->mentionService->extractUserIds([1, 2, 1, 2, 3]);

        expect($result)->toBe([1, 2, 3]);
    });
});

// ============================================
// Task Comment Mention Integration Tests
// ============================================

describe('Task Comment Mentions', function () {
    beforeEach(function () {
        $this->project = Project::factory()->create();
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Add user as participant so they can be mentioned
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'role' => 'member',
        ]);
    });

    it('stores mentions when creating a comment with explicit mentions', function () {
        $mentionedUser = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Hello @[Test User]('.$mentionedUser->id.'), please review this',
                'mentions' => [$mentionedUser->id],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('task_comments', [
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
        ]);

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->mentions)->toContain($mentionedUser->id);
    });

    it('parses mentions from content without explicit mentions array', function () {
        $mentionedUser = User::factory()->create([
            'first_name' => 'TestMention',
            'last_name' => 'User',
        ]);
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Hello @[TestMention User]('.$mentionedUser->id.'), please check',
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->mentions)->toContain($mentionedUser->id);
    });

    it('sends notification to mentioned users', function () {
        $mentionedUser = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Hey @[Test User]('.$mentionedUser->id.'), check this',
                'mentions' => [$mentionedUser->id],
            ]);

        Notification::assertSentTo(
            $mentionedUser,
            UserMentionedInComment::class
        );
    });

    it('does not send notification to the commenter if they mention themselves', function () {
        Notification::fake();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Mentioning myself @[Self]('.$this->user->id.')',
                'mentions' => [$this->user->id],
            ]);

        Notification::assertNotSentTo($this->user, UserMentionedInComment::class);
    });

    it('validates mentions array contains valid user IDs', function () {
        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Test comment',
                'mentions' => ['invalid', 'not-numbers'],
            ]);

        $response->assertUnprocessable();
    });

    it('rejects mentions with non-existent user IDs', function () {
        $validUser = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $validUser->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Test comment @[Valid]('.$validUser->id.')',
                'mentions' => [$validUser->id, 99999], // 99999 doesn't exist
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['mentions.1']);
    });

    it('stores comment with valid mentions only', function () {
        $validUser = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $validUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Test comment @[Valid]('.$validUser->id.')',
                'mentions' => [$validUser->id],
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->mentions)->toContain($validUser->id);
    });

    it('returns mentionable users for a task', function () {
        $participant = User::factory()->create([
            'first_name' => 'Participant',
            'last_name' => 'User',
        ]);
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $participant->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/tasks/{$this->task->uuid}/mentionable-users");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $participant->id,
                'first_name' => 'Participant',
                'last_name' => 'User',
            ]);
    });
});

// ============================================
// Project Comment Mention Integration Tests
// ============================================

describe('Project Comment Mentions', function () {
    beforeEach(function () {
        $this->project = Project::factory()->create([
            'project_manager_id' => $this->user->id,
        ]);
    });

    it('stores mentions when creating a project comment', function () {
        $mentionedUser = User::factory()->create();
        $this->project->participants()->create([
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->uuid}/comments", [
                'content' => 'Hello @[Test]('.$mentionedUser->id.'), please review',
                'mentions' => [$mentionedUser->id],
            ]);

        $response->assertCreated();

        $comment = ProjectComment::where('project_id', $this->project->id)->first();
        expect($comment->mentions)->toContain($mentionedUser->id);
    });

    it('sends notification to mentioned users on project', function () {
        $mentionedUser = User::factory()->create();
        $this->project->participants()->create([
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$this->project->uuid}/comments", [
                'content' => 'Hey @[Test]('.$mentionedUser->id.'), check this',
                'mentions' => [$mentionedUser->id],
            ]);

        Notification::assertSentTo(
            $mentionedUser,
            UserMentionedInComment::class,
            function ($notification) {
                return $notification->contextType === 'project';
            }
        );
    });

    it('returns mentionable users for a project', function () {
        $participant = User::factory()->create([
            'first_name' => 'Project',
            'last_name' => 'Participant',
        ]);
        $this->project->participants()->create([
            'user_id' => $participant->id,
            'role' => 'member',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/projects/{$this->project->uuid}/mentionable-users");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $participant->id,
                'first_name' => 'Project',
                'last_name' => 'Participant',
            ]);
    });
});

// ============================================
// Comment Model Methods Tests
// ============================================

describe('Comment Model Methods', function () {
    it('can get mentioned users from TaskComment', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create();

        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'mentions' => [$user1->id, $user2->id],
        ]);

        $mentionedUsers = $comment->getMentionedUsers();

        expect($mentionedUsers)->toHaveCount(2);
        expect($mentionedUsers->pluck('id')->toArray())
            ->toContain($user1->id)
            ->toContain($user2->id);
    });

    it('can add mention to TaskComment', function () {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'mentions' => [],
        ]);

        $comment->addMention($user->id);

        expect($comment->fresh()->mentions)->toContain($user->id);
    });

    it('can remove mention from TaskComment', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $task = Task::factory()->create();

        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'mentions' => [$user1->id, $user2->id],
        ]);

        $comment->removeMention($user1->id);

        $mentions = $comment->fresh()->mentions;
        expect($mentions)->not->toContain($user1->id)
            ->toContain($user2->id);
    });

    it('can check if user is mentioned', function () {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'mentions' => [$user->id],
        ]);

        expect($comment->hasMention($user->id))->toBeTrue();
        expect($comment->hasMention(99999))->toBeFalse();
    });

    it('returns empty collection when no mentions', function () {
        $task = Task::factory()->create();

        $comment = TaskComment::factory()->create([
            'task_id' => $task->id,
            'mentions' => null,
        ]);

        expect($comment->getMentionedUsers())->toBeEmpty();
    });
});

// ============================================
// Notification Content Tests
// ============================================

describe('Notification Content', function () {
    it('notification contains correct context for task mention', function () {
        $mentionedUser = User::factory()->create();
        $task = Task::factory()->create();

        TaskParticipant::create([
            'task_id' => $task->id,
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        Notification::fake();

        $this->actingAs($this->user)
            ->postJson("/api/tasks/{$task->uuid}/comments", [
                'content' => 'Test @[User]('.$mentionedUser->id.')',
                'mentions' => [$mentionedUser->id],
            ]);

        Notification::assertSentTo(
            $mentionedUser,
            UserMentionedInComment::class,
            function ($notification) {
                return $notification->contextType === 'task';
            }
        );
    });
});
