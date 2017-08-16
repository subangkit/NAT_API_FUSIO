<?php

namespace App\Referal\Action;

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

        $affected = $connection->update('referal', [
            'user_code' => $body->user_code,
            'firstname' => $body->firstname,
            'lastname' => $body->lastname,
            'updated' => $now->format('Y-m-d H:i:s'),
        ], [
            'code' => $request->getUriFragment('referal_code')
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
