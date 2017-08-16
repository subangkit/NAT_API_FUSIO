<?php

namespace App\External\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Http\Exception as StatusCode;

class Currency extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://min-api.cryptocompare.com/data/pricemulti?fsyms=BTC,ETH,DASH,USD&tsyms=BTC,ETH,DASH,IDR,USD,EUR');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);

        return $this->response->build(200, [], json_decode($result, true));
    }
}
