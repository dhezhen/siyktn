<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    echo "Value of PASSWORD_RESET: " . Illuminate\Support\Facades\Password::PASSWORD_RESET . "\n";
    echo "Value of PasswordReset: " . Illuminate\Support\Facades\Password::PasswordReset . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
