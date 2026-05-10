<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $user = App\Models\User::firstOrCreate(
        ['email' => 'customer@test.com'],
        [
            'name' => 'Test Customer',
            'password' => bcrypt('password'),
            'role' => 'customer'
        ]
    );
    if (!$user->hasRole('customer')) {
        $user->assignRole('customer');
    }
    
    $campaign = App\Models\EmailCampaign::first();
    if (!$campaign) {
        $campaign = App\Models\EmailCampaign::create([
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'status' => 'draft',
            'sent_count' => 0,
            'open_count' => 0,
            'click_count' => 0,
            'revenue' => 0,
            'results' => ['builtin_segment' => 'all_customers', 'builtin_segment_name' => 'All Customers']
        ]);
    }
    
    $response = app()->make('App\Http\Controllers\Api\Admin\CampaignController')->send($campaign->id);
    echo json_encode($response->getData());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
