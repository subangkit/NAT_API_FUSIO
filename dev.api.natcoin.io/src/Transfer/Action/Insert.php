<?php

namespace App\Transfer\Action;

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

        $body = $request->getBody();
        $now  = new \DateTime();

        $connection->insert('transfer', [
            'flag' => 1,
            'user_code' => $body->user_code,
            'currency_code' => $body->currency_code,
            'wallet_code' => $body->wallet_code,
            'address_from' => $body->address_from,
            'address_to' => $body->address_to,
            'amount' => $body->amount,
            'created' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Insert successful',
        ]);
    }
}
