<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $result = OpenAI\Laravel\Facades\OpenAI::chat()->create([
        'model' => 'gpt-5-nano',
        'messages' => [
            ['role' => 'user', 'content' => 'Hello'],
        ],
    ]);
    echo "SUCCESS: Connected to gpt-5-nano!\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}
