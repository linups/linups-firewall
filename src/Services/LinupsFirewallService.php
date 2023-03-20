<?php

namespace Linups\LinupsFirewall\Services;
use Illuminate\Support\Facades\Http;
use Linups\LinupsFirewall\Models\Keyword;
use Linups\LinupsFirewall\Models\BannedIp;

class LinupsFirewallService {
    private $endpoint;
    private $authEmail;
    private $authKey;
    private $listID;
    private $accountID;

    public function __construct() {
        $this->endpoint = 'https://api.cloudflare.com/client/v4/accounts';
        $this->authKey = config('linups-config.cloudflare_auth_key');
        $this->authEmail = config('linups-config.cloudflare_auth_email');
        $this->listID = config('linups-config.cloudflare_list_id');
        $this->accountID = config('linups-config.cloudflare_account_id');
    }
    public function getListsFromCloudflare() {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey,
        ])->get($this->endpoint.'/'.$this->accountID.'/rules/lists');

        return $response->body();
    }

    public function BanIpOnCloudflare(string $ip) {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey
        ])->withBody('[{"ip":"'.$ip.'"}]')->post($this->endpoint.'/'.$this->accountID.'/rules/lists/'.$this->listID.'/items');

        return $response->body();
    }

    public function checkIfRequestMadeByWebSpider() {
        $keywords = cache()->remember('keywords', 60*60, function() {
            return Keyword::all();
        });

        if($keywords->isNotEmpty()) {
            foreach($keywords as $keyword) {
                if(stristr(request()->fullUrl(), $keyword->keyword)) {
                    //--- Saving banned ip in DB
                    BannedIp::firstOrCreate(['ip' => \Request::ip()],['ip' => \Request::ip()]);
                    //--- Banning on cloudflare
                    return $this->BanIpOnCloudflare(\Request::ip());
                }
            }
        }
    }

    public function updateIpListOnCloudflare(array $ipList) {
        if(count($ipList)>0) {
            $ipString = '[';
            $i = 0;
            foreach ($ipList as $ip) {
                if($i != 0) $ipString .= ',';
                $ipString .= '{"ip":"'.$ip.'"}';
                $i++;
            }
            $ipString .= ']';
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey
        ])->withBody($ipString)->put($this->endpoint.'/'.$this->accountID.'/rules/lists/'.$this->listID.'/items');

        return $response->body();
    }



    /**
    public function BanIpOnCloudflare(string $ip) {
    $response = Http::withHeaders([
    'Content-Type' => 'application/json',
    'X-Auth-Email' => 'linas.gutauskas@gmail.com',
    'X-Auth-Key' => 'a3df829e4babba5a76c8ac9b341f836ea6b91'
    ])->post('https://api.cloudflare.com/client/v4/accounts/d963634ba6774d19fd56547c2b8138a2/rules/lists/5d5a2a8816a1439dbd7ca59713c2a7e2/items', [
    json_encode($ip),
    ]);

    dd($response->body());
    }
     ***/
}