<?php

namespace Linups\LinupsFirewall\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Linups\LinupsFirewall\Services\LinupsFirewallService;

class Keyword extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        //--- Keywords are cached by the middleware; refresh them on every change
        $forget = fn () => Cache::forget(LinupsFirewallService::KEYWORDS_CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
    }
}