<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user:id,name,avatar');

        if ($request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->user_role) {
            $query->where('user_role', $request->user_role);
        }

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->latest('created_at')->paginate(20);

        $mapped = $logs->through(function ($log) {
            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user_name,
                'user_role' => $log->user_role,
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('M d, Y H:i:s'),
                'time_ago' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }

    public function stats()
    {
        $today = ActivityLog::whereDate('created_at', today())->count();
        $thisWeek = ActivityLog::whereBetween('created_at',
            [now()->startOfWeek(), now()->endOfWeek()])->count();
        $logins = ActivityLog::where('action', 'auth.login')
            ->whereDate('created_at', today())->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today_activities' => $today,
                'week_activities' => $thisWeek,
                'today_logins' => $logins,
            ]
        ]);
    }
}
