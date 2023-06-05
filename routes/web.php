<?php
use Illuminate\Support\Facades\Route;
use Linups\LinupsFirewall\Services\LinupsFirewallService;
use Linups\LinupsFirewall\Http\Controllers\Api\FirewallController;

Route::prefix('v1')->group(function () {
    Route::get('/get-keyword-list', [FirewallController::class, 'getKeywordList']);
});


Route::get('/test', function () {
    $LinupsFirewallService = new LinupsFirewallService();

    $x1 = $LinupsFirewallService->BanIpOnCloudflare('3.4.5.6', 'https://webhook.site/14948490-9a71-4982-b0e7-84299f39231b', 'ABC');
    $x2 = $LinupsFirewallService->BanIpOnCloudflare('3.4.5.6', 'https://api.cloudflare.com/client/v4/accounts/d963634ba6774d19fd56547c2b8138a2/rules/lists/5d5a2a8816a1439dbd7ca59713c2a7e2/items', 'a3df829e4babba5a76c8ac9b341f836ea6b91');

    dd($x1, $x2);
    return 'Hello World Test';
});
