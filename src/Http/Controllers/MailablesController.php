<?php

namespace Qoraiche\MailEclipse\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Qoraiche\MailEclipse\Facades\MailEclipse;

class MailablesController extends Controller
{
    public function __construct()
    {
        abort_unless(
            App::environment(config('maileclipse.allowed_environments', ['local'])),
            403,
            'Environment Not Allowed'
        );
    }

    public function toMailablesList()
    {
        return redirect()->route('mailableList');
    }

    public function index()
    {
        $mailables = MailEclipse::getMailables();

        $mailables = (null !== $mailables) ? $mailables->sortBy('name') : collect([]);

        return view(MailEclipse::VIEW_NAMESPACE . '::sections.mailables', compact('mailables'));
    }

    public function generateMailable(Request $request)
    {
        return MailEclipse::generateMailable($request);
    }

    public function viewMailable($name)
    {
        $mailable = MailEclipse::getMailable('name', $name);

        if ($mailable->isEmpty()) {
            return redirect()->route('mailableList');
        }

        $resource = $mailable->first();

        return view(MailEclipse::VIEW_NAMESPACE . '::sections.view-mailable')->with(compact('resource'));
    }

    public function editMailable($name)
    {
        $templateData = MailEclipse::getMailableTemplateData($name);

        if (!$templateData) {
            return redirect()->route('viewMailable', ['name' => $name]);
        }

        return view(MailEclipse::VIEW_NAMESPACE . '::sections.edit-mailable-template', compact('templateData', 'name'));
    }

    public function parseTemplate(Request $request)
    {
        $template = $request->has('template') ? $request->template : false;

        $viewPath = $request->has('template') ? $request->viewpath : base64_decode($request->viewpath);

        // ref https://regexr.com/4dflu
        $bladeRenderable = preg_replace('/((?!{{.*?-)(&gt;)(?=.*?}}))/', '>', $request->markdown);

        if (MailEclipse::markdownedTemplateToView(true, $bladeRenderable, $viewPath, $template)) {
            return response()->json([
                'status' => 'ok',
            ]);
        }

        return response()->json([
            'status' => 'error',
        ]);
    }

    public function previewMarkdownView(Request $request)
    {
        return MailEclipse::previewMarkdownViewContent(false, $request->markdown, $request->name, false, $request->namespace);
    }

    public function previewMailable($name)
    {
        return MailEclipse::renderMailable($name);
    }

    public function delete(Request $request)
    {
        $mailableFile = config('maileclipse.mailables_dir') . '/' . $request->mailablename . '.php';

        if (file_exists($mailableFile)) {
            unlink($mailableFile);

            return response()->json([
                'status' => 'ok',
            ]);
        }

        return response()->json([
            'status' => 'error',
        ]);
    }

    public function sendTest(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'email|nullable',
            'name' => 'string|required',
        ]);

        $email = $request->get('email') ?? config('maileclipse.test_mail');

        MailEclipse::sendTest($request->get('name'), $email);
    }

    public function updateTemplateAssociation(Request $request, $name)
    {
        $templateSlug = $request->input('template');
        $template = \Qoraiche\MailEclipse\Facades\MailEclipse::getTemplates()->first(function ($tpl) use ($templateSlug) {
            return $tpl->template_slug === $templateSlug;
        });
        if (!$template) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not found.'
            ]);
        }
        // Find the mailable file
        $mailablesDir = config('maileclipse.mailables_dir');
        $mailableFile = $mailablesDir . '/' . $name . '.php';
        if (!file_exists($mailableFile)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mailable file not found.'
            ]);
        }
        // Read and update the content() method to use the new template
        $contents = file_get_contents($mailableFile);
        $pattern = '/public function content\(\): Content\s*\{[^}]*return new Content\(([^)]*)\);[^}]*\}/s';
        $replacement = "public function content(): Content\n    {\n        return new Content(\n            view: 'maileclipse::templates.{$template->template_slug}',\n        );\n    }";
        $newContents = preg_replace($pattern, $replacement, $contents);
        if ($newContents === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update mailable.'
            ]);
        }
        file_put_contents($mailableFile, $newContents);
        return response()->json([
            'status' => 'ok',
            'message' => 'Template association updated.'
        ]);
    }
}
