<?php

namespace Tests\Unit;

use App\Models\TemplateEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TemplateEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('mail_templates.test-notification', [
            'title' => 'Subject - [VAR_1]',
            'required_vars' => ['VAR_1', 'VAR_2'],
            'messages' => [
                'body_main' => [
                    'label' => 'Main Body',
                    'type' => 'quill',
                    'default' => '<p>Hello [VAR_1], welcome to [VAR_2].</p>',
                ],
                'body_text' => [
                    'label' => 'Text Body',
                    'type' => 'text',
                    'default' => 'Footer [VAR_1].',
                ]
            ],
        ]);
    }

    public function test_can_fill_data()
    {
        $template = TemplateEmail::create([
            'ref' => 'test-notification',
            'title' => 'Subject - [VAR_1]',
            'messages' => [
                'body_main' => '<p>Hello [VAR_1], welcome to [VAR_2].</p>',
                'body_text' => 'Footer [VAR_1].',
            ],
        ]);

        $template->fillData([
            'VAR_1' => 'John',
            'VAR_2' => 'Acme Corp'
        ]);

        $this->assertEquals('Subject - John', $template->getFilledTitle());
        // quill sanitization adds inline style to p tags
        $this->assertEquals('<p style="margin: 0 0 1em 0;">Hello John, welcome to Acme Corp.</p>', $template->getFilled('body_main'));
        $this->assertEquals('Footer John.', $template->getFilled('body_text'));
    }

    public function test_can_detect_missing_vars()
    {
        $template = TemplateEmail::create([
            'ref' => 'test-notification',
            'title' => 'Subject - [VAR_1]',
            'messages' => [
                'body_main' => 'Hello [VAR_1], welcome to [VAR_2].',
            ],
        ]);

        $missing = $template->getMissingVars(['VAR_1' => 'John']);

        $this->assertContains('VAR_2', $missing);
        $this->assertNotContains('VAR_1', $missing);
    }

    public function test_render_creates_fallback_from_config()
    {
        $template = TemplateEmail::render('test-notification', ['VAR_1' => 'Jane', 'VAR_2' => 'Global']);

        $this->assertEquals('Subject - Jane', $template->getFilledTitle());
        $this->assertEquals('<p style="margin: 0 0 1em 0;">Hello Jane, welcome to Global.</p>', $template->getFilled('body_main'));
        // Doesn't exist in DB yet
        $this->assertDatabaseMissing('template_emails', ['ref' => 'test-notification']);
    }
}
