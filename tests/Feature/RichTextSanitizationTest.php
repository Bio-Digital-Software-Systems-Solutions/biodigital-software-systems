<?php

namespace Tests\Feature;

use App\Enums\Form\FormStatus;
use App\Enums\Form\SubmissionStatus;
use App\Models\Department;
use App\Models\DepartmentForm;
use App\Models\DepartmentFormSubmission;
use App\Models\FormField;
use App\Models\User;
use App\Services\Form\FormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mews\Purifier\Facades\Purifier;
use Tests\TestCase;

class RichTextSanitizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $department;
    private DepartmentForm $form;
    private FormField $richTextField;
    private FormService $formService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();

        $this->form = DepartmentForm::factory()->create([
            'department_id' => $this->department->id,
            'status' => FormStatus::PUBLISHED,
            'created_by' => $this->user->id,
        ]);

        $this->richTextField = FormField::create([
            'form_id' => $this->form->id,
            'name' => 'rich_content',
            'label' => 'Rich Content',
            'type' => 'rich_text',
            'order' => 0,
            'is_required' => false,
        ]);

        $this->formService = app(FormService::class);
    }

    /** @test */
    public function it_removes_script_tags_from_rich_text(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<p>Hello</p><script>alert("XSS")</script>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<p>Hello</p>', $content);
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringNotContainsString('alert', $content);
    }

    /** @test */
    public function it_removes_event_handlers_from_rich_text(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<img src="test.jpg" onerror="alert(\'XSS\')" alt="test">';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringContainsString('alt="test"', $content);
    }

    /** @test */
    public function it_removes_javascript_urls_from_links(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<a href="javascript:alert(\'XSS\')">Click me</a>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('javascript:', $content);
    }

    /** @test */
    public function it_removes_vbscript_urls(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<a href="vbscript:msgbox(\'XSS\')">Click</a>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('vbscript:', $content);
    }

    /** @test */
    public function it_removes_style_with_expression(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        // IE expression() attack
        $maliciousHtml = '<div style="width: expression(alert(\'XSS\'))">Content</div>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('expression', $content);
    }

    /** @test */
    public function it_removes_iframe_tags(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<iframe src="https://evil.com"></iframe><p>Content</p>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('<iframe', $content);
        $this->assertStringContainsString('<p>Content</p>', $content);
    }

    /** @test */
    public function it_removes_object_and_embed_tags(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<object data="evil.swf"></object><embed src="evil.swf"><p>Safe</p>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('<object', $content);
        $this->assertStringNotContainsString('<embed', $content);
        $this->assertStringContainsString('<p>Safe</p>', $content);
    }

    /** @test */
    public function it_allows_safe_html_elements(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<p><strong>Bold</strong> and <em>italic</em> text</p>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<p>', $content);
        $this->assertStringContainsString('<strong>Bold</strong>', $content);
        $this->assertStringContainsString('<em>italic</em>', $content);
    }

    /** @test */
    public function it_allows_headings(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<h1>Title</h1><h2>Subtitle</h2><h3>Section</h3>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<h1>Title</h1>', $content);
        $this->assertStringContainsString('<h2>Subtitle</h2>', $content);
        $this->assertStringContainsString('<h3>Section</h3>', $content);
    }

    /** @test */
    public function it_allows_lists(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<ul><li>Item 1</li><li>Item 2</li></ul><ol><li>First</li></ol>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<ul>', $content);
        $this->assertStringContainsString('<ol>', $content);
        $this->assertStringContainsString('<li>', $content);
    }

    /** @test */
    public function it_allows_safe_links(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<a href="https://example.com" title="Example">Link</a>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<a', $content);
        $this->assertStringContainsString('href="https://example.com"', $content);
        $this->assertStringContainsString('title="Example"', $content);
    }

    /** @test */
    public function it_allows_safe_images(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<img src="https://example.com/image.jpg" alt="Test Image" width="100">';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<img', $content);
        $this->assertStringContainsString('alt="Test Image"', $content);
    }

    /** @test */
    public function it_allows_tables(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<table><thead><tr><th>Header</th></tr></thead><tbody><tr><td>Cell</td></tr></tbody></table>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<table>', $content);
        $this->assertStringContainsString('<thead>', $content);
        $this->assertStringContainsString('<tbody>', $content);
        $this->assertStringContainsString('<tr>', $content);
        $this->assertStringContainsString('<th>', $content);
        $this->assertStringContainsString('<td>', $content);
    }

    /** @test */
    public function it_allows_blockquotes(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $safeHtml = '<blockquote>Quote text</blockquote>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $safeHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringContainsString('<blockquote>Quote text</blockquote>', $content);
    }

    /** @test */
    public function it_does_not_sanitize_non_rich_text_fields(): void
    {
        // Create a regular text field
        FormField::create([
            'form_id' => $this->form->id,
            'name' => 'regular_text',
            'label' => 'Regular Text',
            'type' => 'text',
            'order' => 1,
            'is_required' => false,
        ]);

        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        // HTML in a regular text field should remain untouched
        $htmlInTextField = '<script>alert("XSS")</script>';

        $this->formService->updateSubmission($submission, [
            'regular_text' => $htmlInTextField,
        ]);

        $submission->refresh();

        // Regular text fields are not sanitized - they're escaped on display instead
        $this->assertEquals($htmlInTextField, $submission->data['regular_text']);
    }

    /** @test */
    public function it_handles_empty_rich_text_gracefully(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $this->formService->updateSubmission($submission, [
            'rich_content' => '',
        ]);

        $submission->refresh();

        $this->assertEquals('', $submission->data['rich_content']);
    }

    /** @test */
    public function it_handles_null_rich_text_gracefully(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $this->formService->updateSubmission($submission, [
            'rich_content' => null,
        ]);

        $submission->refresh();

        $this->assertNull($submission->data['rich_content']);
    }

    /** @test */
    public function it_removes_svg_with_onload(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<svg onload="alert(\'XSS\')"><circle cx="50" cy="50" r="40"/></svg><p>Text</p>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('<svg', $content);
        $this->assertStringNotContainsString('onload', $content);
        $this->assertStringContainsString('<p>Text</p>', $content);
    }

    /** @test */
    public function it_removes_data_urls_in_images(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<img src="data:text/html,<script>alert(\'XSS\')</script>" alt="test">';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('data:text/html', $content);
    }

    /** @test */
    public function it_removes_form_elements(): void
    {
        $submission = DepartmentFormSubmission::create([
            'form_id' => $this->form->id,
            'user_id' => $this->user->id,
            'status' => SubmissionStatus::DRAFT,
            'data' => [],
        ]);

        $maliciousHtml = '<form action="https://evil.com"><input type="text"><button>Submit</button></form><p>Safe</p>';

        $this->formService->updateSubmission($submission, [
            'rich_content' => $maliciousHtml,
        ]);

        $submission->refresh();
        $content = $submission->data['rich_content'];

        $this->assertStringNotContainsString('<form', $content);
        $this->assertStringNotContainsString('<input', $content);
        $this->assertStringNotContainsString('<button', $content);
        $this->assertStringContainsString('<p>Safe</p>', $content);
    }
}
