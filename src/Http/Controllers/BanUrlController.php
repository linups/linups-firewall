<?php

namespace Linups\LinupsFirewall\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Linups\LinupsFirewall\Models\Keyword;

/**
 * Opened from the 404 e-mail. No login: both actions sit behind a signed, expiring URL.
 */
class BanUrlController
{
    public function create(Request $request): View
    {
        return view('linups-firewall::ban-url', [
            'keyword' => old('keyword', (string) $request->query('url')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // Short keywords match almost every URL and would ban regular visitors.
            'keyword' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $keyword = Keyword::firstOrCreate(['keyword' => trim($data['keyword'])]);

        return redirect()->to($request->fullUrl())->with(
            'linups-firewall.status',
            $keyword->wasRecentlyCreated
                ? "Keyword \"{$keyword->keyword}\" added to the ban list."
                : "Keyword \"{$keyword->keyword}\" is already in the ban list."
        );
    }
}