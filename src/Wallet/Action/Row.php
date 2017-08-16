<?php

namespace App\Wallet\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Http\Exception as StatusCode;

class Row extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('Default-Connection');

        $akun = $connection->fetchAssoc('SELECT * FROM wallet WHERE code = :code', [
            'code' => $request->getUriFragment('wallet_code')
        ]);

        if (empty($akun)) {
            throw new StatusCode\NotFoundException('Entry not available');
        }

        return $this->response->build(200, [], $akun);
    }
}
