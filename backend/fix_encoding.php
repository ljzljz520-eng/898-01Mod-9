<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating building names with proper UTF-8 encoding...\n";

$buildings = [
    ['id' => 1, 'name' => '1号楼', 'community' => '阳光花园社区'],
    ['id' => 2, 'name' => '2号楼', 'community' => '阳光花园社区'],
    ['id' => 3, 'name' => '3号楼', 'community' => '阳光花园社区'],
    ['id' => 4, 'name' => '5号楼', 'community' => '阳光花园社区'],
    ['id' => 5, 'name' => '8号楼', 'community' => '阳光花园社区'],
    ['id' => 6, 'name' => '10号楼', 'community' => '阳光花园社区'],
    ['id' => 7, 'name' => '12号楼', 'community' => '阳光花园社区'],
    ['id' => 8, 'name' => '15号楼', 'community' => '阳光花园社区'],
    ['id' => 9, 'name' => '18号楼', 'community' => '阳光花园社区'],
    ['id' => 10, 'name' => '20号楼', 'community' => '阳光花园社区'],
];

foreach ($buildings as $b) {
    DB::table('buildings')->where('id', $b['id'])->update([
        'name' => $b['name'],
        'community_name' => $b['community'],
    ]);
    echo "Updated building {$b['id']}: {$b['name']}\n";
}

echo "\nVerifying data from database:\n";
$rows = DB::table('buildings')->get(['id', 'name', 'community_name']);
foreach ($rows as $row) {
    echo "ID: {$row->id}, Name: {$row->name}, Community: {$row->community_name}\n";
}

echo "\nDone!\n";
