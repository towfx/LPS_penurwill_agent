<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TemplateEmail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateEmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = TemplateEmail::all()->map(function ($template) {
            $registry = config("mail_templates.{$template->ref}");
            return [
                'id' => $template->id,
                'ref' => $template->ref,
                'title' => $template->title,
                'updated_at' => $template->updated_at,
                'registry_title' => $registry['title'] ?? $template->ref,
            ];
        });

        return Inertia::render('Admin/EmailTemplatesIndex', [
            'templates' => $templates,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $template = TemplateEmail::findOrFail($id);
        $registry = config("mail_templates.{$template->ref}");

        if (!$registry) {
            abort(404, 'Template registry not found');
        }

        return Inertia::render('Admin/EmailTemplateEdit', [
            'template' => $template,
            'registry' => $registry,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $template = TemplateEmail::findOrFail($id);
        $registry = config("mail_templates.{$template->ref}");

        if (!$registry) {
            abort(404, 'Template registry not found');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'messages' => 'required|array',
        ]);

        // Only allow keys defined in config
        $validKeys = array_keys($registry['messages']);
        $cleanMessages = [];
        foreach ($validated['messages'] as $key => $value) {
            if (in_array($key, $validKeys)) {
                $cleanMessages[$key] = $value;
            }
        }

        $template->update([
            'title' => $validated['title'],
            'messages' => $cleanMessages,
            'updated_by' => request()->user()->id ?? null,
        ]);

        return redirect()->back()->with('success', 'Template updated successfully.');
    }

    /**
     * Preview the HTML structure of the template.
     */
    public function preview(Request $request, string $id)
    {
        $template = TemplateEmail::findOrFail($id);
        $registry = config("mail_templates.{$template->ref}");

        if (!$registry) {
            return response()->json(['error' => 'Registry not found'], 404);
        }

        // Apply any live changes sent from the form if provided
        if ($request->has('title')) {
            $template->title = $request->input('title');
        }
        if ($request->has('messages')) {
            $template->messages = $request->input('messages');
        }

        $previewVars = $registry['preview_vars'] ?? [];
        $template->fillData($previewVars);
        $missingVars = $template->getMissingVars($previewVars);
        $requiredVars = $registry['required_vars'] ?? [];
        $filledVars = array_values(array_intersect($requiredVars, array_keys($previewVars)));

        // To generate HTML, we should render the actual blade file or equivalent
        // In Phase 1 we only have payout-paid-notification
        // We can use a generic approach if possible, but mailables do this.
        // For now, let's render the view directly.
        $viewName = "emails.{$template->ref}";
        
        $html = '';
        if (view()->exists($viewName)) {
            // we pass $template to the view
            $html = view($viewName, ['template' => $template])->render();
        } else {
            // Stub just to show preview
            $html = "<div><p>View <b>{$viewName}</b> not found.</p></div>";
            foreach($template->filled_messages as $key => $msg) {
                $html .= "<div style='margin-bottom:10px; border:1px solid #ccc; padding:10px;'><h4>{$key}</h4><div>{$msg}</div></div>";
            }
        }

        // After replacement, scan the compiled HTML for any remaining [UPPERCASE_PLACEHOLDER]
        preg_match_all('/\[[A-Z0-9_]+\]/', $html, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $varName = trim($match, '[]');
                if (!in_array($varName, $missingVars)) {
                    $missingVars[] = $varName;
                }
            }
        }

        return response()->json([
            'html' => $html,
            'missing_vars' => $missingVars,
            'all_vars' => $requiredVars,
            'filled_vars' => $filledVars,
        ]);
    }
}
