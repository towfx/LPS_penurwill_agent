<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateEmail extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'messages' => 'array',
    ];

    public array $filled_messages = [];
    protected string $filled_title = '';

    /**
     * Get the registry config for this template.
     */
    public function getRegistry(): array
    {
        return config("mail_templates.{$this->ref}", []);
    }

    /**
     * Replaces [PLACEHOLDERS] in title and messages based on provided variables.
     */
    public function fillData(array $vars): self
    {
        $registry = $this->getRegistry();
        
        // Fill title
        $title = $this->title;
        foreach ($vars as $key => $value) {
            $title = str_replace("[{$key}]", (string) $value, $title);
        }
        $this->filled_title = $title;

        // Fill messages
        $filledMessages = [];
        $messages = $this->messages ?? [];
        foreach ($messages as $varName => $content) {
            $filledContent = $content;
            foreach ($vars as $key => $value) {
                $filledContent = str_replace("[{$key}]", (string) $value, $filledContent);
            }
            
            // If quill type, sanitize
            $type = $registry['messages'][$varName]['type'] ?? 'text';
            if ($type === 'quill') {
                $filledContent = self::sanitizeQuillHtml($filledContent);
            }

            $filledMessages[$varName] = $filledContent;
        }

        $this->filled_messages = $filledMessages;

        return $this;
    }

    /**
     * Returns the compiled subject.
     */
    public function getFilledTitle(string $default = ''): string
    {
        return $this->filled_title ?: ($this->title ?: $default);
    }

    /**
     * Returns a specific filled message section.
     */
    public function getFilled(string $varName, string $default = ''): string
    {
        $messages = !empty($this->filled_messages) ? $this->filled_messages : ($this->messages ?? []);
        return $messages[$varName] ?? $default;
    }

    /**
     * Reads required variables from config and compares against supplied ones.
     */
    public function getMissingVars(array $suppliedVars): array
    {
        $requiredVars = config("mail_templates.{$this->ref}.required_vars", []);
        return array_values(array_diff($requiredVars, array_keys($suppliedVars)));
    }

    /**
     * Sanitizes Quill HTML output to be email safe.
     */
    public static function sanitizeQuillHtml(string $html): string
    {
        // Add basic inline styling for quill paragraphs
        $html = str_replace('<p>', '<p style="margin: 0 0 1em 0;">', $html);
        return $html;
    }

    /**
     * Render the template or fallback to config defaults.
     */
    public static function render(string $ref, array $vars, ?string $fallbackSubject = null, ?array $fallbackMessages = null): self
    {
        $template = self::where('ref', $ref)->first();

        if (!$template) {
            $registry = config("mail_templates.{$ref}");
            
            $defaultMessages = [];
            if ($registry && isset($registry['messages'])) {
                foreach ($registry['messages'] as $key => $spec) {
                    $defaultMessages[$key] = $spec['default'] ?? '';
                }
            }

            $template = new self([
                'ref' => $ref,
                'title' => $registry['title'] ?? $fallbackSubject ?? '',
                'messages' => $fallbackMessages ?? $defaultMessages,
            ]);
        }

        return $template->fillData($vars);
    }
}
