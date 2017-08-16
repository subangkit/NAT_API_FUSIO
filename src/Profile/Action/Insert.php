<?php

namespace App\Profile\Action;

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

        $object = $connection->fetchAssoc('SELECT * FROM profile WHERE user_code = :code', [
            'code' => $body->user_code
        ]);

        if (empty($object)) {
            $connection->insert('profile', [
                'flag' => 1,
                'user_code' => $body->user_code,
                'firstname' => $body->firstname,
                'lastname' => $body->lastname,
                'created' => $now->format('Y-m-d H:i:s'),
            ]);
        } else {
            $affected = $connection->update('profile', [
                'firstname' => $body->firstname,
                'lastname' => $body->lastname,
                'updated' => $now->format('Y-m-d H:i:s'),
            ], [
                'code' => $body->user_code
            ]);
        }

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Insert successful',
        ]);
    }
}
