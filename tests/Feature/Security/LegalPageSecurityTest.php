<?php

namespace Tests\Feature\Security;

use App\Models\LegalPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stevebauman\Purify\Facades\Purify;
use Tests\TestCase;

/**
 * SEC-001: XSS Prevention Tests for Legal Pages
 *
 * Tests that the HTML Purifier correctly sanitizes dangerous content
 * while preserving safe HTML elements. The security is implemented at two levels:
 * 1. Model Cast (PurifyHtmlOnGet) - sanitizes on retrieval
 * 2. View level (Purify::clean()) - double sanitization
 */
class LegalPageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mark app as installed for tests
        config(['app.installed' => true]);
    }

    /** @test */
    public function purify_removes_script_tags(): void
    {
        $maliciousContent = '<p>Safe content</p><script>alert("XSS")</script><p>More safe content</p>';

        $sanitized = Purify::clean($maliciousContent);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert("XSS")', $sanitized);
        $this->assertStringContainsString('Safe content', $sanitized);
    }

    /** @test */
    public function purify_removes_event_handlers(): void
    {
        $maliciousContent = '<p onclick="alert(\'XSS\')">Click me</p><img src="x" onerror="alert(\'XSS\')">';

        $sanitized = Purify::clean($maliciousContent);

        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('onerror', $sanitized);
        $this->assertStringNotContainsString("alert('XSS')", $sanitized);
    }

    /** @test */
    public function purify_removes_javascript_protocol(): void
    {
        $maliciousContent = '<a href="javascript:alert(\'XSS\')">Click here</a>';

        $sanitized = Purify::clean($maliciousContent);

        $this->assertStringNotContainsString('javascript:', $sanitized);
    }

    /** @test */
    public function purify_preserves_safe_html_elements(): void
    {
        $safeContent = '<h2>Heading</h2><p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p><ul><li>Item 1</li><li>Item 2</li></ul><a href="https://example.com">Safe link</a>';

        $sanitized = Purify::clean($safeContent);

        // Safe HTML elements should be preserved
        $this->assertStringContainsString('<h2>', $sanitized);
        $this->assertStringContainsString('<p>', $sanitized);
        $this->assertStringContainsString('<strong>', $sanitized);
        $this->assertStringContainsString('<em>', $sanitized);
        $this->assertStringContainsString('<ul>', $sanitized);
        $this->assertStringContainsString('<li>', $sanitized);
        $this->assertStringContainsString('https://example.com', $sanitized);
    }

    /** @test */
    public function legal_page_view_renders_without_xss(): void
    {
        $maliciousContent = '<p>Safe content</p><script>document.cookie</script><img src=x onerror=alert(1)>';

        // Use a slug that maps to a localized slug in LegalPageController
        // Map: 'datenschutz' => 'privacy'
        LegalPage::create([
            'slug' => 'privacy',
            'title' => 'Datenschutz Test',
            'content' => $maliciousContent,
            'is_published' => true,
        ]);

        // Access via the localized slug 'datenschutz'
        // Note: withoutMiddleware needed to bypass installation/tenant checks in test env
        $response = $this->withoutMiddleware()->get('/datenschutz');

        $response->assertStatus(200);
        // The view uses Purify::clean() which should sanitize the output
        // Note: We check for specific malicious content, not generic <script> (page has Tailwind scripts)
        $response->assertDontSee('document.cookie', false);
        $response->assertDontSee('onerror=alert', false);
        $response->assertDontSee('<script>document', false);
        $response->assertSee('Safe content');
    }

    /** @test */
    public function purify_removes_style_based_xss(): void
    {
        $maliciousContent = '<div style="background-image: url(javascript:alert(\'XSS\'))">Content</div>';

        $sanitized = Purify::clean($maliciousContent);

        $this->assertStringNotContainsString('javascript:', $sanitized);
    }

    /** @test */
    public function purify_removes_data_protocol(): void
    {
        $maliciousContent = '<a href="data:text/html,<script>alert(\'XSS\')</script>">Click</a>';

        $sanitized = Purify::clean($maliciousContent);

        // data: protocol should be removed or neutralized
        $this->assertStringNotContainsString('data:text/html', $sanitized);
    }

    /** @test */
    public function purify_removes_svg_event_handlers(): void
    {
        $maliciousContent = '<svg onload="alert(\'XSS\')"><circle cx="50" cy="50" r="40"/></svg>';

        $sanitized = Purify::clean($maliciousContent);

        $this->assertStringNotContainsString('onload', $sanitized);
    }

    /** @test */
    public function legal_page_model_cast_sanitizes_content(): void
    {
        // Create page with malicious content
        LegalPage::create([
            'slug' => 'test-cast',
            'title' => 'Test Cast',
            'content' => '<p>Safe</p><script>alert(1)</script>',
            'is_published' => true,
        ]);

        // Retrieve fresh from database - this triggers PurifyHtmlOnGet cast
        $page = LegalPage::where('slug', 'test-cast')->first();

        // If PurifyHtmlOnGet is working, script should be removed
        // Note: This test verifies the cast is properly configured
        $this->assertNotNull($page);
        $this->assertStringContainsString('Safe', $page->content);
        // The cast should sanitize on get
        $this->assertStringNotContainsString('<script>', $page->content);
    }

    /** @test */
    public function unpublished_legal_page_returns_404(): void
    {
        // Use a mapped slug for consistency
        LegalPage::create([
            'slug' => 'terms',
            'title' => 'Unpublished AGB',
            'content' => '<p>Content</p>',
            'is_published' => false,
        ]);

        // Access via localized slug 'agb' which maps to 'terms'
        $response = $this->get('/agb');

        $response->assertStatus(404);
    }
}
