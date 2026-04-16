<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

$app = app();
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/admin/contrats/validate?numero=1', 'GET');
$response = $app->handle($request);

echo $response->getContent();