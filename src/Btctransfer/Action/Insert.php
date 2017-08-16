<?php

namespace App\Btctransfer\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;

class Insert extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('Default-Connection');
        $api = $this->connector->getConnection('API-Connection');

        $body = $request->getBody();
        $now  = new \DateTime();

        $authorizationHeader = $request->getHeader('Authorization');
        $token = str_replace('Bearer ','',$request->getHeader('Authorization'));
        $row = $api->fetchAssoc('SELECT appId, userId, status, token, scope, expire, date FROM fusio_app_token WHERE token = :token', ['token' => $token]);
        if (!empty($row)) {
            $activeUserId = $row['userId'];
        }

        $connection->insert('btctransfer', [
            'flag' => 1,
            'user_code' => $activeUserId,
            'btcaddress' => $body->btcaddress,
            'amount' => $body->amount,
            'currency' => $body->currency,
            'txhash' => $body->txhash,
            'rate' => $body->rate,
            'ntc' => $body->ntc,
            'claimed' => 0,
            'created' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Insert successful',
        ]);
    }
}
