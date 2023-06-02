<?php

namespace Linups\LinupsFirewall\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Keyword;

class FirewallController extends Controller
{
    public function getKeywordList() {
        return Keyword::get()->toJson();
    }
}
