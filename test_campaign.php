<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    $campaign = App\Models\EmailCampaign::first();
    if (!$campaign) {
        echo 'No campaigns found';
    } else {
        $response = app()->make('App\Http\Controllers\Api\Admin\CampaignController')->send($campaign->id);
        echo json_encode($response->getData());
    }
} catch (\Exception $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString();
}
