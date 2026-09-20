<?php
use Illuminate\Support\Facades\Route;
use Linups\LinupsFirewall\Http\Controllers\Api\FirewallController;

Route::prefix('v1')->group(function () {
    Route::get('/get-keyword-list', [FirewallController::class, 'getKeywordList']);
});
