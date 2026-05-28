<?php
require __DIR__.'/magangquest-api/vendor/autoload.php';
$app = require __DIR__.'/magangquest-api/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = App\Models\User::all(['id','name','email','role','status_onboarding']);
foreach($users as $u) {
    echo "{$u->id} | {$u->email} | {$u->role} | {$u->status_onboarding}\n";
}
