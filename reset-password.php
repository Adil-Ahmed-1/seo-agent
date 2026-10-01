<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('email', 'admin@example.com')->first();

if (! $user) {
    echo "❌ User not found\n";
    exit;
}

$user->password = Hash::make('password123');
$user->save();

echo "✅ Password reset!\n";
echo "Match check: " . (Hash::check('password123', $user->password) ? 'MATCH ✅' : 'FAIL ❌') . "\n";