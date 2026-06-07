<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Topic model methods...\n";

try {
    $topic = \App\Models\Topic::find(68);
    if ($topic) {
        echo "Found topic: " . $topic->title . "\n";
    } else {
        echo "Topic not found, testing with query...\n";
    }
    
    $user = \App\Models\User::find(1);
    echo "User: " . ($user ? $user->email : 'not found') . "\n";
    
    echo "Testing byAccessibleCircles scope...\n";
    $query = \App\Models\Topic::byAccessibleCircles($user);
    $count = $query->count();
    echo "Query returned $count results\n";
    
    echo "Testing toArrayForUser...\n";
    if ($topic) {
        $arr = $topic->toArrayForUser($user);
        echo "toArrayForUser worked, keys: " . implode(', ', array_keys($arr)) . "\n";
    }
    
    echo "All methods work!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
