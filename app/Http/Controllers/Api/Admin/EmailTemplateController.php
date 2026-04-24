<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    protected $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => \App\Models\EmailTemplate::all()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|unique:email_templates,name',
            'subject'   => 'required|string',
            'content'   => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->createTemplate($data);

        return response()->json(['success' => true, 'data' => $template], 201);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'name'      => 'string|unique:email_templates,name,' . $id,
            'subject'   => 'string',
            'content'   => 'string',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->updateTemplate($id, $data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    public function destroy($id)
    {
        $this->templateService->deleteTemplate($id);
        return response()->json(['success' => true, 'message' => 'Template deleted']);
    }
}
