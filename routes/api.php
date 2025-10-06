<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BlobController;
use App\Http\Middleware\ApiTokenAuth;

Route::middleware([ApiTokenAuth::class])->prefix('v1')->group(function () {
    Route::post('/blobs', [BlobController::class, 'store']);
    Route::get('/blobs/{id}', [BlobController::class, 'show']);
});
