<?php

namespace App\Transfer\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;
use PSX\Http\Exception as StatusCode;

class Update extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('Default-Connection');

        $body = $request->getBody();
        $now  = new \DateTime();

        $affected = $connection->update('transfer', [
            'user_code' => $body->user_code,
            'currency_code' => $body->currency_code,
            'wallet_code' => $body->wallet_code,
            'address_from' => $body->address_from,
            'address_to' => $body->address_to,
            'amount' => $body->amount,
            'updated' => $now->format('Y-m-d H:i:s'),
        ], [
            'code' => $request->getUriFragment('transfer_code')
        ]);

        if (empty($affected)) {
            throw new StatusCode\NotFoundException('Entry not available');
        }

        return $this->response->build(200, [], [
            'success' => true,
            'message' => 'Update successful',
        ]);
    }
}
