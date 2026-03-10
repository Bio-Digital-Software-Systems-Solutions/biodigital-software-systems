<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Department;
use App\Models\DepartmentNeed;
use App\Models\NeedComment;
use App\Enums\Need\NeedStatus;
use App\Enums\Need\NeedCategory;
use App\Enums\Need\NeedPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_needs_index(): void
    {
        DepartmentNeed::factory()
            ->count(5)
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Index')
            ->has('needs', 5)
        );
    }

    public function test_user_can_create_need(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('needs.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Create')
        );
    }

    public function test_user_can_store_need(): void
    {
        $needData = [
            'title' => 'New Laptop',
            'description' => 'Need a new laptop for development work',
            'justification' => 'Current laptop is too slow',
            'category' => NeedCategory::EQUIPMENT->value,
            'priority' => NeedPriority::HIGH->value,
            'department_id' => $this->department->id,
            'estimated_amount' => 1500.00,
            'quantity' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('needs.store'), $needData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_needs', [
            'title' => 'New Laptop',
            'category' => NeedCategory::EQUIPMENT->value,
            'status' => NeedStatus::DRAFT->value,
        ]);
    }

    public function test_user_can_view_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.show', $need));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Show')
            ->has('need')
        );
    }

    public function test_user_can_update_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $updateData = [
            'title' => 'Updated Need Title',
            'description' => 'Updated description',
            'category' => NeedCategory::SERVICES->value,
            'priority' => NeedPriority::CRITICAL->value,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('needs.update', $need), $updateData);

        $response->assertRedirect();

        $this->assertDatabaseHas('department_needs', [
            'id' => $need->id,
            'title' => 'Updated Need Title',
            'category' => NeedCategory::SERVICES->value,
        ]);
    }

    public function test_user_can_submit_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $response = $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $response->assertRedirect();

        $need->refresh();
        $this->assertEquals(NeedStatus::SUBMITTED, $need->status);
        $this->assertNotNull($need->submitted_at);
    }

    public function test_approver_can_approve_need(): void
    {
        $approver = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        $response = $this->actingAs($approver)
            ->post(route('needs.approve', $need), [
                'approved_amount' => 1000.00,
                'comment' => 'Approved for purchase',
            ]);

        $response->assertRedirect();

        $need->refresh();
        $this->assertEquals(NeedStatus::APPROVED, $need->status);
        $this->assertEquals($approver->id, $need->approved_by);
        $this->assertNotNull($need->approved_at);
    }

    public function test_approver_can_reject_need(): void
    {
        $approver = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        $response = $this->actingAs($approver)
            ->post(route('needs.reject', $need), [
                'reason' => 'Budget constraints',
            ]);

        $response->assertRedirect();

        $need->refresh();
        $this->assertEquals(NeedStatus::REJECTED, $need->status);
        $this->assertEquals($approver->id, $need->rejected_by);
        $this->assertEquals('Budget constraints', $need->rejection_reason);
    }

    public function test_user_can_add_comment_to_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        // Start session to get CSRF token
        $this->actingAs($this->user)
            ->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.comments.add', $need), [
                '_token' => csrf_token(),
                'content' => 'This is a test comment',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('need_comments', [
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_user_can_delete_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $response = $this->actingAs($this->user)
            ->delete(route('needs.destroy', $need));

        $response->assertRedirect();

        $this->assertSoftDeleted('department_needs', [
            'id' => $need->id,
        ]);
    }

    public function test_needs_can_be_filtered_by_status(): void
    {
        DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        DepartmentNeed::factory()
            ->count(2)
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::APPROVED,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.index', ['status' => NeedStatus::APPROVED->value]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Index')
            ->has('needs', 2)
        );
    }

    public function test_needs_can_be_filtered_by_category(): void
    {
        DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'category' => NeedCategory::EQUIPMENT,
            ]);

        DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'category' => NeedCategory::SERVICES,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.index', ['category' => NeedCategory::EQUIPMENT->value]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Index')
            ->has('needs', 1)
        );
    }

    public function test_needs_can_be_filtered_by_priority(): void
    {
        DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'priority' => NeedPriority::CRITICAL,
            ]);

        DepartmentNeed::factory()
            ->count(3)
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'priority' => NeedPriority::LOW,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.index', ['priority' => NeedPriority::CRITICAL->value]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Index')
            ->has('needs', 1)
        );
    }

    public function test_need_status_history_is_recorded(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Submit the need
        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $this->assertDatabaseHas('need_status_history', [
            'need_id' => $need->id,
            'from_status' => NeedStatus::DRAFT->value,
            'to_status' => NeedStatus::SUBMITTED->value,
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_history_endpoint_returns_empty_array_for_new_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'history' => [],
            ]);
    }

    public function test_history_endpoint_returns_status_changes(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Submit the need to create history
        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'history')
            ->assertJsonStructure([
                'success',
                'history' => [
                    '*' => [
                        'id',
                        'from_status',
                        'to_status',
                        'reason',
                        'metadata',
                        'created_at',
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'full_name',
                        ],
                    ],
                ],
            ]);
    }

    public function test_history_endpoint_returns_user_information(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Submit the need
        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200);

        $history = $response->json('history.0');
        $this->assertEquals($this->user->id, $history['user']['id']);
        $this->assertEquals($this->user->first_name, $history['user']['first_name']);
        $this->assertEquals($this->user->last_name, $history['user']['last_name']);
        $this->assertEquals(
            $this->user->first_name . ' ' . $this->user->last_name,
            $history['user']['full_name']
        );
    }

    public function test_history_endpoint_returns_multiple_status_changes_in_order(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $approver = User::factory()->create();

        // Submit the need
        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        // Approve the need
        $this->actingAs($approver)
            ->post(route('needs.approve', $need), [
                'approved_amount' => 1000.00,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200)
            ->assertJsonCount(2, 'history');

        $history = $response->json('history');

        // History should be in reverse chronological order (most recent first)
        $this->assertEquals(NeedStatus::APPROVED->value, $history[0]['to_status']);
        $this->assertEquals(NeedStatus::SUBMITTED->value, $history[0]['from_status']);
        $this->assertEquals(NeedStatus::SUBMITTED->value, $history[1]['to_status']);
        $this->assertEquals(NeedStatus::DRAFT->value, $history[1]['from_status']);
    }

    public function test_history_endpoint_requires_authentication(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->getJson(route('needs.history', $need));

        $response->assertStatus(401);
    }

    public function test_history_is_recorded_on_rejection(): void
    {
        $approver = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        $this->actingAs($approver)
            ->post(route('needs.reject', $need), [
                'reason' => 'Budget constraints',
            ]);

        $this->assertDatabaseHas('need_status_history', [
            'need_id' => $need->id,
            'from_status' => NeedStatus::UNDER_REVIEW->value,
            'to_status' => NeedStatus::REJECTED->value,
            'changed_by' => $approver->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200);
        $history = $response->json('history.0');
        $this->assertEquals($approver->id, $history['user']['id']);
    }

    public function test_history_tracks_different_users_for_each_change(): void
    {
        $submitter = User::factory()->create();
        $approver = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $submitter->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Submitter submits the need
        $this->actingAs($submitter)
            ->post(route('needs.submit', $need));

        // Approver approves the need
        $this->actingAs($approver)
            ->post(route('needs.approve', $need), [
                'approved_amount' => 500.00,
            ]);

        $response = $this->actingAs($submitter)
            ->getJson(route('needs.history', $need));

        $response->assertStatus(200);
        $history = $response->json('history');

        // Check that each history entry has the correct user
        $this->assertEquals($approver->id, $history[0]['user']['id']); // Approval (most recent)
        $this->assertEquals($submitter->id, $history[1]['user']['id']); // Submission
    }

    public function test_history_endpoint_returns_404_for_non_existent_need(): void
    {
        $fakeUuid = 'non-existent-uuid';

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $fakeUuid));

        $response->assertStatus(404);
    }

    public function test_history_includes_correct_status_values(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Submit the need
        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $history = $response->json('history.0');

        $this->assertEquals(NeedStatus::DRAFT->value, $history['from_status']);
        $this->assertEquals(NeedStatus::SUBMITTED->value, $history['to_status']);
    }

    public function test_history_created_at_is_iso_format(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $this->actingAs($this->user)
            ->post(route('needs.submit', $need));

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.history', $need));

        $history = $response->json('history.0');

        // Check that created_at is in ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $history['created_at']
        );
    }

    // =====================================================
    // Comments API Tests
    // =====================================================

    public function test_comments_endpoint_returns_empty_array_for_need_without_comments(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'comments' => [],
            ]);
    }

    public function test_comments_endpoint_returns_comments_list(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        NeedComment::factory()
            ->count(3)
            ->create([
                'need_id' => $need->id,
                'user_id' => $this->user->id,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'comments');
    }

    public function test_comments_endpoint_returns_correct_structure(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'Test comment content',
            'is_internal' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'comments' => [
                    '*' => [
                        'id',
                        'uuid',
                        'content',
                        'is_internal',
                        'created_at',
                        'user' => [
                            'id',
                            'first_name',
                            'last_name',
                            'full_name',
                        ],
                        'replies',
                    ],
                ],
            ]);
    }

    public function test_comments_endpoint_returns_user_information(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200);

        $comment = $response->json('comments.0');
        $this->assertEquals($this->user->id, $comment['user']['id']);
        $this->assertEquals($this->user->first_name, $comment['user']['first_name']);
        $this->assertEquals($this->user->last_name, $comment['user']['last_name']);
        $this->assertEquals(
            $this->user->first_name . ' ' . $this->user->last_name,
            $comment['user']['full_name']
        );
    }

    public function test_comments_endpoint_returns_replies(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $parentComment = NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'Parent comment',
        ]);

        NeedComment::factory()->count(2)->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
            'content' => 'Reply comment',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'comments') // Only top-level comments
            ->assertJsonCount(2, 'comments.0.replies'); // With 2 replies
    }

    public function test_comments_endpoint_excludes_reply_comments_from_top_level(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $parentComment = NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
        ]);

        // Create reply (should not appear in top-level)
        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
        ]);

        // Create another top-level comment
        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        // Should only return 2 top-level comments (not the reply)
        $response->assertStatus(200)
            ->assertJsonCount(2, 'comments');
    }

    public function test_comments_endpoint_returns_internal_flag(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'is_internal' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200);
        $this->assertTrue($response->json('comments.0.is_internal'));
    }

    public function test_comments_endpoint_requires_authentication(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $response = $this->getJson(route('needs.comments.list', $need));

        $response->assertStatus(401);
    }

    public function test_comments_endpoint_returns_404_for_non_existent_need(): void
    {
        $fakeUuid = 'non-existent-uuid';

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $fakeUuid));

        $response->assertStatus(404);
    }

    public function test_comments_are_ordered_by_created_at_descending(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $olderComment = NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'Older comment',
            'created_at' => now()->subHours(2),
        ]);

        $newerComment = NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'Newer comment',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200);
        $comments = $response->json('comments');

        // Newer should be first (descending order)
        $this->assertEquals($newerComment->id, $comments[0]['id']);
        $this->assertEquals($olderComment->id, $comments[1]['id']);
    }

    public function test_comments_created_at_is_iso_format(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $comment = $response->json('comments.0');

        // Check that created_at is in ISO 8601 format
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $comment['created_at']
        );
    }

    public function test_user_can_add_internal_comment(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.comments.add', $need), [
                '_token' => csrf_token(),
                'content' => 'This is an internal comment',
                'is_internal' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('need_comments', [
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'This is an internal comment',
            'is_internal' => true,
        ]);
    }

    public function test_user_can_add_reply_to_comment(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $parentComment = NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
        ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.comments.add', $need), [
                '_token' => csrf_token(),
                'content' => 'This is a reply',
                'parent_id' => $parentComment->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('need_comments', [
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'This is a reply',
            'parent_id' => $parentComment->id,
        ]);
    }

    public function test_add_comment_validates_content_is_required(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.comments.add', $need), [
                '_token' => csrf_token(),
                'content' => '',
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_add_comment_validates_content_max_length(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.comments.add', $need), [
                '_token' => csrf_token(),
                'content' => str_repeat('a', 2001), // Exceeds 2000 char limit
            ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_add_comment_requires_authentication(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        // Start session to get CSRF token
        $this->get(route('needs.index'));

        $response = $this->post(route('needs.comments.add', $need), [
            '_token' => csrf_token(),
            'content' => 'Test comment',
        ]);

        // The route requires auth middleware, so it redirects to login
        $response->assertRedirect(route('login'));
    }

    public function test_comments_from_different_users_have_correct_user_info(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create(['requester_id' => $this->user->id]);

        $anotherUser = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $this->user->id,
            'content' => 'Comment from user 1',
            'created_at' => now(),
        ]);

        NeedComment::factory()->create([
            'need_id' => $need->id,
            'user_id' => $anotherUser->id,
            'content' => 'Comment from user 2',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('needs.comments.list', $need));

        $response->assertStatus(200);
        $comments = $response->json('comments');

        // First comment (most recent) is from $this->user
        $this->assertEquals($this->user->id, $comments[0]['user']['id']);

        // Second comment is from $anotherUser
        $this->assertEquals($anotherUser->id, $comments[1]['user']['id']);
        $this->assertEquals('John', $comments[1]['user']['first_name']);
        $this->assertEquals('Doe', $comments[1]['user']['last_name']);
        $this->assertEquals('John Doe', $comments[1]['user']['full_name']);
    }

    // =====================================================
    // Date Validation Tests
    // =====================================================

    public function test_user_can_create_need_with_valid_date(): void
    {
        $futureDate = now()->addWeek()->format('Y-m-d');

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.store'), [
                '_token' => csrf_token(),
                'title' => 'Need with date',
                'description' => 'Test description',
                'category' => NeedCategory::EQUIPMENT->value,
                'priority' => NeedPriority::MEDIUM->value,
                'department_id' => $this->department->id,
                'needed_by' => $futureDate,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('department_needs', [
            'title' => 'Need with date',
            'needed_by' => $futureDate,
        ]);
    }

    public function test_user_can_create_need_without_date(): void
    {
        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.store'), [
                '_token' => csrf_token(),
                'title' => 'Need without date',
                'description' => 'Test description',
                'category' => NeedCategory::EQUIPMENT->value,
                'priority' => NeedPriority::MEDIUM->value,
                'department_id' => $this->department->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('department_needs', [
            'title' => 'Need without date',
            'needed_by' => null,
        ]);
    }

    public function test_create_need_validates_invalid_date_format(): void
    {
        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.store'), [
                '_token' => csrf_token(),
                'title' => 'Need with invalid date',
                'description' => 'Test description',
                'category' => NeedCategory::EQUIPMENT->value,
                'priority' => NeedPriority::MEDIUM->value,
                'department_id' => $this->department->id,
                'needed_by' => 'not-a-valid-date',
            ]);

        $response->assertSessionHasErrors('needed_by');
    }

    public function test_update_need_with_valid_date(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $newDate = now()->addMonth()->format('Y-m-d');

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->put(route('needs.update', $need), [
                '_token' => csrf_token(),
                'title' => $need->title,
                'description' => $need->description,
                'category' => $need->category->value,
                'priority' => $need->priority->value,
                'department_id' => $this->department->id,
                'needed_by' => $newDate,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $need->refresh();
        $this->assertEquals($newDate, $need->needed_by->format('Y-m-d'));
    }

    public function test_update_need_can_clear_date(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
                'needed_by' => now()->addWeek(),
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->put(route('needs.update', $need), [
                '_token' => csrf_token(),
                'title' => $need->title,
                'description' => $need->description,
                'category' => $need->category->value,
                'priority' => $need->priority->value,
                'department_id' => $this->department->id,
                'needed_by' => null,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $need->refresh();
        $this->assertNull($need->needed_by);
    }

    public function test_need_date_is_stored_correctly_in_database(): void
    {
        $specificDate = '2025-06-15';

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.store'), [
                '_token' => csrf_token(),
                'title' => 'Need with specific date',
                'description' => 'Test description',
                'category' => NeedCategory::EQUIPMENT->value,
                'priority' => NeedPriority::MEDIUM->value,
                'department_id' => $this->department->id,
                'needed_by' => $specificDate,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $need = DepartmentNeed::where('title', 'Need with specific date')->first();
        $this->assertNotNull($need);
        $this->assertEquals($specificDate, $need->needed_by->format('Y-m-d'));
    }

    public function test_need_date_is_properly_cast_to_carbon(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'needed_by' => '2025-06-15',
            ]);

        $need->refresh();

        // Verify the date is cast to Carbon
        $this->assertInstanceOf(\Carbon\Carbon::class, $need->needed_by);
        $this->assertEquals('2025-06-15', $need->needed_by->format('Y-m-d'));
        $this->assertEquals(2025, $need->needed_by->year);
        $this->assertEquals(6, $need->needed_by->month);
        $this->assertEquals(15, $need->needed_by->day);
    }

    // =====================================================
    // Show Page Tests
    // =====================================================

    public function test_show_page_renders_successfully(): void
    {
        // Assign head_of_department to the department to avoid null errors
        $this->department->update(['head_of_department' => $this->user->id]);

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.show', $need));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Show')
            ->has('need')
        );
    }

    public function test_rejected_need_has_rejection_reason_in_database(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::REJECTED,
                'rejection_reason' => 'Budget constraints for this quarter',
                'rejected_by' => $this->user->id,
                'rejected_at' => now(),
            ]);

        $this->assertDatabaseHas('department_needs', [
            'id' => $need->id,
            'status' => NeedStatus::REJECTED->value,
            'rejection_reason' => 'Budget constraints for this quarter',
        ]);
    }

    // =====================================================
    // Edit Page Tests
    // =====================================================

    public function test_user_can_access_edit_page_for_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.edit', $need));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Needs/Edit')
            ->has('need')
            ->has('departments')
            ->has('users')
        );
    }

    public function test_user_cannot_edit_submitted_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        $response = $this->actingAs($this->user)
            ->get(route('needs.edit', $need));

        // Should redirect back with error
        $response->assertRedirect();
    }

    public function test_other_user_cannot_edit_need(): void
    {
        $otherUser = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $response = $this->actingAs($otherUser)
            ->get(route('needs.edit', $need));

        // Should redirect back with error
        $response->assertRedirect();
    }

    // =====================================================
    // Approval Authorization Tests
    // =====================================================

    public function test_requester_cannot_approve_own_need(): void
    {
        // User creates a need and tries to approve it themselves
        // Need must be in UNDER_REVIEW status to be approved (per workflow rules)
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.approve', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied - user cannot approve their own need
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::UNDER_REVIEW, $need->status);
    }

    public function test_requester_cannot_reject_own_need(): void
    {
        // Need must be in UNDER_REVIEW status to be rejected (per workflow rules)
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.reject', $need), [
                '_token' => csrf_token(),
                'reason' => 'I want to reject my own need',
            ]);

        // Should be denied
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::UNDER_REVIEW, $need->status);
    }

    public function test_department_head_can_approve_need(): void
    {
        // Create a department head who is NOT the requester
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        // Need must be in UNDER_REVIEW status to be approved (per workflow rules)
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id, // Different user is the requester
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->post(route('needs.approve', $need), [
                '_token' => csrf_token(),
            ]);

        // Should succeed
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::APPROVED, $need->status);
        $this->assertEquals($departmentHead->id, $need->approved_by);
    }

    public function test_department_head_can_reject_need(): void
    {
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        // Need must be in UNDER_REVIEW status to be rejected (per workflow rules)
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->post(route('needs.reject', $need), [
                '_token' => csrf_token(),
                'reason' => 'Budget exceeded for this quarter',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::REJECTED, $need->status);
        $this->assertEquals('Budget exceeded for this quarter', $need->rejection_reason);
    }

    public function test_random_user_cannot_approve_need(): void
    {
        // Create a random user who is NOT department head and has no permission
        $randomUser = User::factory()->create();

        // Need must be in UNDER_REVIEW status to be approved (per workflow rules)
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($randomUser)->get(route('needs.index'));

        $this->actingAs($randomUser)
            ->post(route('needs.approve', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied - check that status remains unchanged
        // (authorization check happens before validation, returns error)
        $need->refresh();
        $this->assertEquals(NeedStatus::UNDER_REVIEW, $need->status);
        // If approved, the status would be APPROVED, so this assertion proves
        // that the random user was denied access
    }

    public function test_requester_cannot_change_status_to_approved_via_kanban(): void
    {
        // Need must be in UNDER_REVIEW to transition to APPROVED
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->patch(route('needs.update-status', $need), [
                '_token' => csrf_token(),
                'status' => NeedStatus::APPROVED->value,
            ]);

        // Should be denied
        $response->assertSessionHas('error');

        $need->refresh();
        $this->assertEquals(NeedStatus::UNDER_REVIEW, $need->status);
    }

    // =====================================================
    // Rejection Tests with Reason
    // =====================================================

    public function test_reject_requires_reason(): void
    {
        // Use department head to test rejection reason requirement
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->post(route('needs.reject', $need), [
                '_token' => csrf_token(),
                // No reason provided
            ]);

        // The reason is required in the controller, so it should fail validation
        $response->assertSessionHasErrors('reason');
    }

    public function test_reject_stores_rejection_reason(): void
    {
        // Use department head to test rejection
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->post(route('needs.reject', $need), [
                '_token' => csrf_token(),
                'reason' => 'Insufficient justification provided',
            ]);

        $response->assertRedirect();

        $need->refresh();
        $this->assertEquals(NeedStatus::REJECTED, $need->status);
        $this->assertEquals('Insufficient justification provided', $need->rejection_reason);
        $this->assertEquals($departmentHead->id, $need->rejected_by);
        $this->assertNotNull($need->rejected_at);
    }

    // =====================================================
    // Status Update Tests
    // =====================================================

    public function test_status_can_be_updated_via_patch(): void
    {
        // Use department head (who is not the requester) to test status update
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::APPROVED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->patch(route('needs.update-status', $need), [
                '_token' => csrf_token(),
                'status' => NeedStatus::IN_PROGRESS->value,
            ]);

        $response->assertRedirect();

        $need->refresh();
        $this->assertEquals(NeedStatus::IN_PROGRESS, $need->status);
    }

    public function test_status_update_handles_invalid_status(): void
    {
        // Use department head for status update test
        $departmentHead = User::factory()->create();
        $this->department->update(['head_of_department' => $departmentHead->id]);

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::APPROVED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($departmentHead)->get(route('needs.index'));

        $response = $this->actingAs($departmentHead)
            ->patch(route('needs.update-status', $need), [
                '_token' => csrf_token(),
                'status' => 'invalid_status',
            ]);

        // The controller throws exception for invalid status and returns error via session
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::APPROVED, $need->status);
    }

    // =====================================================
    // Delete Tests
    // =====================================================

    public function test_user_cannot_delete_submitted_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        $this->actingAs($this->user)
            ->delete(route('needs.destroy', $need));

        // Should redirect with error or fail
        $this->assertDatabaseHas('department_needs', [
            'id' => $need->id,
            'deleted_at' => null,
        ]);
    }

    public function test_other_user_cannot_delete_draft_need(): void
    {
        $otherUser = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        $this->actingAs($otherUser)
            ->delete(route('needs.destroy', $need));

        // Should not be deleted
        $this->assertDatabaseHas('department_needs', [
            'id' => $need->id,
            'deleted_at' => null,
        ]);
    }

    // =====================================================
    // Withdraw Tests
    // =====================================================

    public function test_requester_can_withdraw_submitted_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::DRAFT, $need->status);
        $this->assertNull($need->submitted_at);
    }

    public function test_other_user_cannot_withdraw_submitted_need(): void
    {
        $otherUser = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

        // Start session to get CSRF token
        $this->actingAs($otherUser)->get(route('needs.index'));

        $response = $this->actingAs($otherUser)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::SUBMITTED, $need->status);
    }

    public function test_cannot_withdraw_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        // Should fail - can only withdraw submitted needs
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::DRAFT, $need->status);
    }

    public function test_cannot_withdraw_under_review_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::UNDER_REVIEW,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        // Should fail - can only withdraw submitted needs
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::UNDER_REVIEW, $need->status);
    }

    public function test_cannot_withdraw_approved_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::APPROVED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        // Should fail - can only withdraw submitted needs
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::APPROVED, $need->status);
    }

    public function test_withdraw_records_status_history(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.withdraw', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('need_status_history', [
            'need_id' => $need->id,
            'from_status' => NeedStatus::SUBMITTED->value,
            'to_status' => NeedStatus::DRAFT->value,
            'changed_by' => $this->user->id,
        ]);
    }

    // =====================================================
    // Cancel Tests
    // =====================================================

    public function test_requester_can_cancel_submitted_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::CANCELLED, $need->status);
    }

    public function test_requester_can_cancel_draft_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::DRAFT,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::CANCELLED, $need->status);
    }

    public function test_requester_can_cancel_approved_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::APPROVED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $need->refresh();
        $this->assertEquals(NeedStatus::CANCELLED, $need->status);
    }

    public function test_other_user_cannot_cancel_need(): void
    {
        $otherUser = User::factory()->create();

        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($otherUser)->get(route('needs.index'));

        $response = $this->actingAs($otherUser)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied
        $response->assertSessionHas('error');

        // Status should remain unchanged
        $need->refresh();
        $this->assertEquals(NeedStatus::SUBMITTED, $need->status);
    }

    public function test_cannot_cancel_already_cancelled_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::CANCELLED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied by canCancel check
        $response->assertSessionHas('error');
    }

    public function test_cannot_cancel_completed_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::COMPLETED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied by canCancel check
        $response->assertSessionHas('error');
    }

    public function test_cannot_cancel_rejected_need(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::REJECTED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        // Should be denied by canCancel check
        $response->assertSessionHas('error');
    }

    public function test_cancel_records_status_history(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        // Start session to get CSRF token
        $this->actingAs($this->user)->get(route('needs.index'));

        $response = $this->actingAs($this->user)
            ->post(route('needs.cancel', $need), [
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('need_status_history', [
            'need_id' => $need->id,
            'from_status' => NeedStatus::SUBMITTED->value,
            'to_status' => NeedStatus::CANCELLED->value,
            'changed_by' => $this->user->id,
        ]);
    }

    public function test_withdraw_requires_authentication(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        $response = $this->post(route('needs.withdraw', $need));

        // Should redirect to login or home page (depending on auth configuration)
        $response->assertRedirect();
    }

    public function test_cancel_requires_authentication(): void
    {
        $need = DepartmentNeed::factory()
            ->for($this->department)
            ->create([
                'requester_id' => $this->user->id,
                'status' => NeedStatus::SUBMITTED,
            ]);

        $response = $this->post(route('needs.cancel', $need));

        // Should redirect to login or home page (depending on auth configuration)
        $response->assertRedirect();
    }
}
