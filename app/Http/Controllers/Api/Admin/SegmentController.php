<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SegmentController extends Controller
{
    // Get all segments with REAL user counts
    public function index()
    {
        $segments = DB::table('user_segments')->get();

        $mapped = $segments->map(function ($segment) {
            $rules = is_string($segment->rule_set)
                ? json_decode($segment->rule_set, true)
                : (array)$segment->rule_set;

            // Normalize old object format to new array format
            if (!empty($rules) && !isset($rules[0])) {
                $normalized = [];
                foreach ($rules as $key => $value) {
                    if ($value === '' || $value === null || $value === false) continue;
                    
                    if ($key === 'spent_min') $normalized[] = ['type' => 'min_spent', 'value' => $value];
                    if ($key === 'last_purchase_days') $normalized[] = ['type' => 'inactive_days', 'value' => $value];
                    if ($key === 'newsletter_only' && $value) $normalized[] = ['type' => 'newsletter', 'value' => 'true'];
                    if ($key === 'role') $normalized[] = ['type' => 'role', 'value' => $value];
                }
                $rules = $normalized;
            }

            $userCount = $this->countUsersInSegment($rules ?? []);

            return [
                'id' => $segment->id,
                'name' => $segment->name,
                'rule_set' => $rules,
                'user_count' => $userCount,
                'updated_at' => $segment->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }

    // Create new segment
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rules' => 'nullable|array',
        ]);

        $rules = $request->rules ?? [];

        $id = DB::table('user_segments')->insertGetId([
            'name' => $request->name,
            'rule_set' => json_encode($rules),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userCount = $this->countUsersInSegment($rules);

        return response()->json([
            'success' => true,
            'message' => 'Segment created successfully',
            'data' => [
                'id' => $id,
                'name' => $request->name,
                'rule_set' => $rules,
                'user_count' => $userCount,
            ]
        ]);
    }

    // Update segment
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rules' => 'nullable|array',
        ]);

        $rules = $request->rules ?? [];

        DB::table('user_segments')->where('id', $id)->update([
            'name' => $request->name,
            'rule_set' => json_encode($rules),
            'updated_at' => now(),
        ]);

        $userCount = $this->countUsersInSegment($rules);

        return response()->json([
            'success' => true,
            'message' => 'Segment updated successfully',
            'data' => [
                'id' => $id,
                'name' => $request->name,
                'rule_set' => $rules,
                'user_count' => $userCount,
            ]
        ]);
    }

    // Delete segment
    public function destroy($id)
    {
        DB::table('user_segments')->where('id', $id)->delete();
        return response()->json([
            'success' => true,
            'message' => 'Segment deleted',
        ]);
    }

    // Get users IN this segment (for View Users)
    public function users($id)
    {
        $segment = DB::table('user_segments')
            ->where('id', $id)->first();

        if (!$segment) {
            return response()->json([
                'success' => false,
                'message' => 'Segment not found',
            ], 404);
        }

        $rules = is_string($segment->rule_set)
            ? json_decode($segment->rule_set, true)
            : (array)$segment->rule_set;

        // Normalize old object format to new array format
        if (!empty($rules) && !isset($rules[0])) {
            $normalized = [];
            foreach ($rules as $key => $value) {
                if ($value === '' || $value === null || $value === false) continue;
                
                if ($key === 'spent_min') $normalized[] = ['type' => 'min_spent', 'value' => $value];
                if ($key === 'last_purchase_days') $normalized[] = ['type' => 'inactive_days', 'value' => $value];
                if ($key === 'newsletter_only' && $value) $normalized[] = ['type' => 'newsletter', 'value' => 'true'];
                if ($key === 'role') $normalized[] = ['type' => 'role', 'value' => $value];
            }
            $rules = $normalized;
        }

        $users = $this->getUsersInSegment($rules ?? []);

        $mapped = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
                    ? asset('storage/' . $user->avatar)
                    : null,
                'role' => $user->getRoleNames()->first() ?? 'customer',
                'created_at' => $user->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'segment_name' => $segment->name,
                'users' => $mapped,
                'total' => $mapped->count(),
            ]
        ]);
    }

    // Count users matching segment rules
    private function countUsersInSegment(array $rules): int
    {
        return $this->buildUserQuery($rules)->count();
    }

    // Get users matching segment rules
    private function getUsersInSegment(array $rules)
    {
        return $this->buildUserQuery($rules)->get();
    }

    // Build query based on rules
    private function buildUserQuery(array $rules)
    {
        $query = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        });

        foreach ($rules as $rule) {
            $type = $rule['type'] ?? '';
            $operator = $rule['operator'] ?? '>=';
            $value = $rule['value'] ?? 0;

            switch ($type) {
                case 'role':
                    $query->whereHas('roles', function ($q) use ($value) {
                        $q->where('name', $value);
                    });
                    break;

                case 'min_orders':
                    $query->whereHas('orders', function ($q) {},
                        $operator, $value);
                    break;

                case 'min_spent':
                    $query->whereHas('orders', function ($q) {
                        $q->where('payment_status', 'paid');
                    }, '>=', 1)
                    ->where(function ($q) use ($value) {
                        $q->whereRaw(
                            '(SELECT COALESCE(SUM(grand_total), 0)
                            FROM orders
                            WHERE orders.user_id = users.id
                            AND orders.payment_status = "paid") >= ?',
                            [$value]
                        );
                    });
                    break;

                case 'registered_days':
                    $query->where(
                        'created_at', '>=',
                        now()->subDays((int)$value)
                    );
                    break;

                case 'inactive_days':
                    $query->whereDoesntHave('orders', function ($q)
                        use ($value) {
                        $q->where('created_at', '>=',
                            now()->subDays((int)$value));
                    });
                    break;

                case 'newsletter':
                    $query->where(
                        'subscribed_to_newsletter',
                        $value === 'true' || $value === true
                    );
                    break;
            }
        }

        return $query;
    }
}
