<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id', 'user_name', 'user_role',
        'action', 'description',
        'model_type', 'model_id',
        'ip_address', 'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Static helper to log activity
    public static function log(
        string $action,
        string $description,
        $modelType = null,
        $modelId = null
    ): void {
        $user = auth()->user();
        self::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'user_role' => $user ? ($user->getRoleNames()->first() ?? 'user') : 'guest',
            'action' => $action,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->header('User-Agent', ''), 0, 255),
            'created_at' => now(),
        ]);
    }
}
