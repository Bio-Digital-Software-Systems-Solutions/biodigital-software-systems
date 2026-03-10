<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    \Spatie\Permission\Models\Permission::create(['name' => 'view programs']);
    \Spatie\Permission\Models\Permission::create(['name' => 'manage programs']);

    $role = \Spatie\Permission\Models\Role::create(['name' => 'project-manager']);
    $role->givePermissionTo('view programs');
    $role->givePermissionTo('manage programs');

    $this->user = User::factory()->create();
    $this->user->assignRole('project-manager');

    $this->project = Project::factory()->create();
    $this->task = Task::factory()->create([
        'project_id' => $this->project->id,
    ]);

    TaskParticipant::create([
        'task_id' => $this->task->id,
        'user_id' => $this->user->id,
        'role' => 'member',
    ]);
});

describe('Comments with URLs', function (): void {
    it('stores a comment containing a URL', function (): void {
        Notification::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => 'Check this link: https://docs.google.com/spreadsheets/d/1WeT0kxNg9054eclo-VVk/edit?gid=300551873',
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->content)->toContain('https://docs.google.com/spreadsheets/d/1WeT0kxNg9054eclo-VVk/edit?gid=300551873');
    });

    it('stores a comment with multiple URLs', function (): void {
        Notification::fake();

        $content = 'See https://example.com and also https://google.com/search?q=test for more info';

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => $content,
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->content)->toBe($content);
    });

    it('stores a comment with both URLs and mentions', function (): void {
        Notification::fake();

        $mentionedUser = User::factory()->create();
        TaskParticipant::create([
            'task_id' => $this->task->id,
            'user_id' => $mentionedUser->id,
            'role' => 'member',
        ]);

        $content = 'Hey @[Test User]('.$mentionedUser->id.') check https://example.com/important-doc please';

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => $content,
                'mentions' => [$mentionedUser->id],
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->content)->toBe($content);
        expect($comment->content)->toContain('https://example.com/important-doc');
        expect($comment->mentions)->toContain($mentionedUser->id);
    });

    it('returns the URL in the comment response', function (): void {
        Notification::fake();

        $url = 'https://docs.google.com/spreadsheets/d/1WeT0kxNg9054eclo-VVk/edit';

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => "Structure du fichier excel: {$url}",
            ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'content' => "Structure du fichier excel: {$url}",
            ]);
    });

    it('accepts URLs with special characters in query strings', function (): void {
        Notification::fake();

        $url = 'https://example.com/path?param1=value1&param2=value2#anchor';

        $response = $this->actingAs($this->user)
            ->postJson("/api/tasks/{$this->task->uuid}/comments", [
                'content' => "Check {$url}",
            ]);

        $response->assertCreated();

        $comment = TaskComment::where('task_id', $this->task->id)->first();
        expect($comment->content)->toContain($url);
    });
});
