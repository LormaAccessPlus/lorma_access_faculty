<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    // Clear the students table
    $count = DB::table('students')->count();
    DB::table('students')->delete();
    
    echo "Successfully cleared {$count} student(s) from the students table.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
