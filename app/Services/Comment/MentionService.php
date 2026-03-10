<?php

namespace App\Services\Comment;

use App\Models\Project;
use App\Models\ProjectParticipant;
use App\Models\Task;
use App\Models\TaskParticipant;
use App\Models\User;
use Illuminate\Support\Collection;

class MentionService
{
    /**
     * Parse mentions from content and return array of user IDs.
     *
     * Supports two patterns:
     * - @username (matches against first_name, last_name, or email)
     * - @[User Name](user_id) - explicit format used by mention UI
     *
     * @param  string  $content  The comment content
     * @param  Collection<int, User>|null  $validUsers  Optional collection of users that can be mentioned
     * @return array<int> Array of unique user IDs
     */
    public function parseMentions(string $content, ?Collection $validUsers = null): array
    {
        $mentionedUserIds = [];

        // Pattern 1: Explicit format @[Name](uuid) - matches UUIDs (alphanumeric with hyphens)
        preg_match_all('/@\[([^\]]+)\]\(([a-zA-Z0-9-]+)\)/', $content, $explicitMatches);
        // Convert UUIDs to user IDs by looking up each UUID
        foreach ($explicitMatches[2] as $identifier) {
            // Check if it's a numeric ID (for backward compatibility) or UUID
            if (ctype_digit($identifier)) {
                $mentionedUserIds[] = (int) $identifier;
            } else {
                $user = User::where('uuid', $identifier)->first();
                if ($user) {
                    $mentionedUserIds[] = $user->id;
                }
            }
        }

        // Pattern 2: Simple @mention format (find @word patterns not already matched)
        // This matches @firstName, @lastName, @firstName.lastName, @email
        preg_match_all('/@([a-zA-Z0-9._]+)(?!\])/', $content, $simpleMatches);

        foreach ($simpleMatches[1] as $mention) {
            $user = $this->findUserByMention($mention, $validUsers);
            if ($user instanceof \App\Models\User) {
                $mentionedUserIds[] = $user->id;
            }
        }

        return array_values(array_unique($mentionedUserIds));
    }

    /**
     * Find a user by mention string.
     *
     * @param  string  $mention  The mention text without @
     * @param  Collection<int, User>|null  $validUsers  Optional collection to search within
     */
    private function findUserByMention(string $mention, ?Collection $validUsers = null): ?User
    {
        $mention = strtolower($mention);

        // If we have a valid users collection, search within it
        if ($validUsers instanceof \Illuminate\Support\Collection) {
            return $validUsers->first(fn(User $user): bool => $this->matchesUser($user, $mention));
        }

        // Otherwise search the database
        return User::query()
            ->where(function ($query) use ($mention): void {
                $query->whereRaw('LOWER(first_name) = ?', [$mention])
                    ->orWhereRaw('LOWER(last_name) = ?', [$mention])
                    ->orWhereRaw('LOWER(CONCAT(first_name, ".", last_name)) = ?', [$mention])
                    ->orWhereRaw('LOWER(CONCAT(first_name, last_name)) = ?', [$mention])
                    ->orWhereRaw('LOWER(email) = ?', [$mention]);
            })
            ->first();
    }

    /**
     * Check if a user matches the mention string.
     */
    private function matchesUser(User $user, string $mention): bool
    {
        $mention = strtolower($mention);
        $firstName = strtolower($user->first_name ?? '');
        $lastName = strtolower($user->last_name ?? '');
        $email = strtolower($user->email ?? '');

        return $firstName === $mention
            || $lastName === $mention
            || ($firstName.'.'.$lastName) === $mention
            || ($firstName.$lastName) === $mention
            || $email === $mention;
    }

    /**
     * Get users that can be mentioned in a project context.
     *
     * @param  int  $projectId  The project ID
     * @return Collection<int, User>
     */
    public function getMentionableUsersForProject(int $projectId): Collection
    {
        $project = Project::find($projectId);
        if (! $project) {
            return collect();
        }

        $userIds = collect();

        // Project manager
        if ($project->project_manager_id) {
            $userIds->push($project->project_manager_id);
        }

        // Project members (from pivot table)
        $memberIds = $project->members()->pluck('users.id');
        $userIds = $userIds->merge($memberIds);

        // Project participants
        $participantIds = ProjectParticipant::where('project_id', $projectId)
            ->pluck('user_id');
        $userIds = $userIds->merge($participantIds);

        return User::whereIn('id', $userIds->unique())->get();
    }

    /**
     * Get users that can be mentioned in a task context.
     *
     * @param  int  $taskId  The task ID
     * @param  int|null  $projectId  The project ID if available
     * @return Collection<int, User>
     */
    public function getMentionableUsersForTask(int $taskId, ?int $projectId = null): Collection
    {
        $userIds = collect();

        // Task participants
        $participantIds = TaskParticipant::where('task_id', $taskId)
            ->pluck('user_id');
        $userIds = $userIds->merge($participantIds);

        // Task assignee
        $task = Task::find($taskId);
        if ($task && $task->assigned_to) {
            $userIds->push($task->assigned_to);
        }

        // If project context, include project members
        if ($projectId) {
            $projectUsers = $this->getMentionableUsersForProject($projectId);
            $userIds = $userIds->merge($projectUsers->pluck('id'));
        }

        return User::whereIn('id', $userIds->unique())->get();
    }

    /**
     * Validate that all mentioned user IDs are valid.
     *
     * @param  array<int>  $userIds
     * @param  Collection<int, User>|null  $validUsers
     * @return array<int> Only the valid user IDs
     */
    public function validateMentionedUsers(array $userIds, ?Collection $validUsers = null): array
    {
        if ($userIds === []) {
            return [];
        }

        if ($validUsers instanceof \Illuminate\Support\Collection) {
            $validIds = $validUsers->pluck('id')->toArray();

            return array_values(array_intersect($userIds, $validIds));
        }

        return User::whereIn('id', $userIds)->pluck('id')->toArray();
    }

    /**
     * Convert plain @mentions to rich format for display.
     * Transforms @mention to @[Full Name](user_uuid)
     *
     * @param  array<int>  $mentionedUserIds
     */
    public function enrichContent(string $content, array $mentionedUserIds): string
    {
        if ($mentionedUserIds === []) {
            return $content;
        }

        $users = User::whereIn('id', $mentionedUserIds)->get();

        foreach ($users as $user) {
            $fullName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
            $patterns = [
                '/@'.preg_quote($user->first_name ?? '', '/').'(?!\])/i',
                '/@'.preg_quote($user->last_name ?? '', '/').'(?!\])/i',
                '/@'.preg_quote(($user->first_name ?? '').'.'.($user->last_name ?? ''), '/').'(?!\])/i',
            ];

            $replacement = "@[{$fullName}]({$user->uuid})";

            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, $replacement, (string) $content, 1);
            }
        }

        return $content;
    }

    /**
     * Extract mentioned user IDs from an array (for validation).
     *
     * @return array<int>
     */
    public function extractUserIds(?array $mentions): array
    {
        if ($mentions === null || $mentions === []) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(intval(...), $mentions),
            fn ($id): bool => $id > 0
        )));
    }
}
