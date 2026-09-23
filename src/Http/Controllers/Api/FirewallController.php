<?php

namespace Linups\LinupsFirewall\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Linups\LinupsFirewall\Models\Keyword;

class FirewallController
{
    public function getKeywordList(Request $request): JsonResponse
    {
        $token = (string) config('linups-config.keyword_list_token');

        if ($token !== '' && ! hash_equals($token, (string) $request->bearerToken())) {
            abort(403);
        }

        return response()->json(Keyword::query()->select('keyword')->get());
    }
}