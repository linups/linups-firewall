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
        $this->endpoint = config('linups-config.cloudflare_endpoint');
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
        $requestData = new \stdClass();
        $requestData->ip = $ip;

        $responseJson = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey
        ])->post($this->endpoint.'/'.$this->accountID.'/rules/lists/'.$this->listID.'/items', [$requestData]);

        $response = json_decode($responseJson->body());
        if($response->success == true) {
            return $response;
        }

        throw new \Exception('Invalid response. Debug:'.print_r($response, true));
    }

    public function checkIfRequestMadeByWebSpider() {
        //--- Skipping IPv6 ips
        if(stristr($_SERVER['REMOTE_ADDR'], '::')) {
            return;
        }
        $keywords = cache()->remember('keywords', 60*60, function() {
            return Keyword::all();
        });

        if($keywords->isNotEmpty()) {
            foreach($keywords as $keyword) {
                if(stristr(request()->fullUrl(), $keyword->keyword)) {
                    //--- Saving banned ip in DB
                    BannedIp::firstOrCreate(['ip' => \Request::ip()],['ip' => \Request::ip()]);
                    //--- Logging banned think
                    \Log::debug('Banning: <pre>'.print_r([
                            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
                            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? '',
                            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        ], true).'</pre>');
                    //--- Banning on cloudflare
                    return $this->BanIpOnCloudflare(\Request::ip());
                }
            }
        }
    }

    public function updateIpListOnCloudflare(array $ipList) {
        if(count($ipList)>0) {
            $collection = collect();
            foreach ($ipList as $ip) {
                $tmpIP = new \stdClass();
                if(strlen($ip) > 15) {
                    $ipsarray = explode('::', $ip);
                    $tmpIP->ip = $ipsarray[0] .'::/64';
                } else {
                    $tmpIP->ip = $ip;
                }
                $collection->push($tmpIP);
            }
        }

        $responseJson = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Auth-Email' => $this->authEmail,
            'X-Auth-Key' => $this->authKey
        ])->put($this->endpoint.'/'.$this->accountID.'/rules/lists/'.$this->listID.'/items', $collection);

        $response = json_decode($responseJson->body());

        if($response->success !== true) {
            throw new \Exception('List not updated..'.print_r($response, true).print_r($collection, true));
        }

        return $response;
    }

}