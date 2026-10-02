<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo view('poh-passwords-alert', ['passwords' => [['name' => 'Test', 'jabatan' => 'Test', 'password' => 'Test']]])->render();
} catch (Throwable $e) {
    echo $e->getMessage();
}
