<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = DB::table('email_templates')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'subject' => $t->subject,
                    'content' => $t->content,
                    'is_active' => $t->is_active ?? true,
                    'created_at' => $t->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Check unique key
        $existing = DB::table('email_templates')
            ->where('name', $request->name)->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Template key already exists',
            ], 422);
        }

        $id = DB::table('email_templates')->insertGetId([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template saved successfully',
            'data' => ['id' => $id],
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template = DB::table('email_templates')
            ->where('id', $id)->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        DB::table('email_templates')->where('id', $id)->update([
            'name' => $request->name,
            'subject' => $request->subject,
            'content' => $request->content,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $template = DB::table('email_templates')
            ->where('id', $id)->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        DB::table('email_templates')->where('id', $id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }

    public function show($id)
    {
        $template = DB::table('email_templates')
            ->where('id', $id)->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $template,
        ]);
    }

    public function toggleActive($id)
    {
        $template = DB::table('email_templates')
            ->where('id', $id)->first();

        $newStatus = !($template->is_active ?? true);

        DB::table('email_templates')->where('id', $id)->update([
            'is_active' => $newStatus,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $newStatus
                ? 'Template activated'
                : 'Template deactivated',
        ]);
    }
}
