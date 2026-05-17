<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Barryvdh\DomPDF\Facade\Pdf;

try {
    $pdf = Pdf::loadHTML('<h1>Test PDF</h1>');
    $pdf->output();
    echo "PDF generated successfully\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
