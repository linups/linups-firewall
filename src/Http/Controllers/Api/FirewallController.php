<?php

namespace Linups\LinupsFirewall\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Linups\LinupsFirewall\Services\LinupsFirewallService;
use Linups\LinupsFirewall\Models\Keyword;

class FirewallController extends Controller
{
    public function getKeywordList() {

        return Keyword::select('keyword')->get()->toJson();

    }
}
