<?php

namespace App\User\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;

class Remember extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('API-Connection');

        $body = $request->getBody();
        $now  = new \DateTime();

        $object = $connection->fetchAssoc('SELECT * FROM fusio_user WHERE email = :email', [
            'email' => $body->email
        ]);

        if (empty($object)) {

        }

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'New password has send to your inbox.',
        ]);
    }
}
