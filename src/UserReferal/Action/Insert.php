<?php

namespace App\UserReferal\Action;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;
use Firebase\JWT\JWT;
use PSX\Http\Exception as StatusCode;

class Insert extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('Default-Connection');
        $api = $this->connector->getConnection('API-Connection');

        $body = $request->getBody();
        $now  = new \DateTime();

        $object = $connection->fetchAssoc('SELECT * FROM userreferal WHERE user_code = :code', [
            'code' => $body->user_code
        ]);

        $authorizationHeader = $request->getHeader('Authorization');
        $token = str_replace('Bearer ','',$request->getHeader('Authorization'));
        $row = $api->fetchAssoc('SELECT appId, userId, status, token, scope, expire, date FROM fusio_app_token WHERE token = :token', ['token' => $token]);
        if (!empty($row)) {
            $activeUserId = $row['userId'];
        }

        if (empty($object)) {
            if ($body->user_code != $activeUserId) {
                throw new StatusCode\NotFoundException('Cant request other user.');
            }

            $payload = [
                'user_code' => $body->user_code,
            ];
            $referal_code = JWT::encode($payload, 'referal_code');
            $connection->insert('userreferal', [
                'flag' => 1,
                'user_code' => $body->user_code,
                'referal_code' => $referal_code,
                'created' => $now->format('Y-m-d H:i:s'),
            ]);
        } else {
            /**
            $affected = $connection->update('userreferal', [
                'referal_code' => $referal_code,
                'updated' => $now->format('Y-m-d H:i:s'),
            ], [
                'code' => $body->user_code
            ]);
             * */

            return $this->response->build(201, [], [
                'success' => true,
                'message' => 'You cant generate more than one',
            ]);
        }

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Insert successful',
        ]);
    }
}
