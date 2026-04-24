<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rule_set',
    ];

    protected $casts = [
        'rule_set' => 'array',
    ];

    public function campaigns()
    {
        return $this->hasMany(EmailCampaign::class, 'segment_id');
    }

    /**
     * Get users matching the segment rules.
     */
    public function getMatchingUsers()
    {
        $query = User::query();

        $rules = $this->rule_set;

        // Example rules processing
        if (isset($rules['spent_min'])) {
            $query->whereHas('orders', function ($q) use ($rules) {
                $q->select('customer_id')
                  ->groupBy('customer_id')
                  ->havingRaw('SUM(grand_total) >= ?', [$rules['spent_min']]);
            });
        }

        if (isset($rules['last_purchase_days'])) {
            $query->whereHas('orders', function ($q) use ($rules) {
                $q->where('created_at', '>=', now()->subDays($rules['last_purchase_days']));
            });
        }

        if (isset($rules['newsletter_only']) && $rules['newsletter_only']) {
            $query->where('subscribed_to_newsletter', true);
        }

        if (isset($rules['role'])) {
            $query->where('role', $rules['role']);
        }

        return $query->get();
    }
}
