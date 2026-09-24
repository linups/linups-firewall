<?php
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\Route;
use Linups\LinupsFirewall\Http\Controllers\Api\FirewallController;
use Linups\LinupsFirewall\Http\Controllers\BanUrlController;

Route::prefix('v1')->group(function () {
    Route::get('/get-keyword-list', [FirewallController::class, 'getKeywordList']);
});

//--- Link from the 404 e-mail; the signature replaces authentication
Route::middleware(ValidateSignature::class)->group(function () {
    Route::get('/ban-url', [BanUrlController::class, 'create'])->name('linups-firewall.ban-url');
    Route::post('/ban-url', [BanUrlController::class, 'store']);
});