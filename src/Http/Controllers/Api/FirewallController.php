<?php

namespace Linups\LinupsFirewall\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Keyword;

class FirewallController extends Controller
{
    public function getKeywordList() {
        return \App\Models\Keyword::get()->toJson();
    }
}
