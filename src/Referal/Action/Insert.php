<?php

namespace App\Referal\Action;

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

        $user = $api->fetchAssoc('SELECT * FROM fusio_user WHERE email = :email', [
            'email' => $body->email
        ]);

        if (!empty($user)) {
            $userId = $user['id'];

            $object = $connection->fetchAssoc('SELECT * FROM referal WHERE user_code = :code', [
                'code' => $userId
            ]);

//        $authorizationHeader = $request->getHeader('Authorization');
//        $token = str_replace('Bearer ','',$request->getHeader('Authorization'));
//        $row = $api->fetchAssoc('SELECT appId, userId, status, token, scope, expire, date FROM fusio_app_token WHERE token = :token', ['token' => $token]);
//        if (!empty($row)) {
//            $activeUserId = $row['userId'];
//        }

            if (empty($object)) {
                $shortName = $body->shortName;

                if ($shortName != null) {
                    $referalUser = $api->fetchAssoc('SELECT * FROM fusio_user WHERE name = :name', [
                        'name' => $body->shortName
                    ]);
                    if (empty($referalUser)) {
                        throw new StatusCode\NotFoundException('Short Name not found.');
                    }
                    $referalId = $referalUser['id'];
                } else {
                    $token = $body->token;
                    $payload = JWT::decode($token, 'referal_code', ['HS256']);
                    $referalId  = isset($payload->user_code) ? $payload->user_code : null;
                }



//            if ($body->user_code != $activeUserId) {
//                throw new StatusCode\NotFoundException('Cant set referal other user.');
//            }
//
                if ($userId == $referalId) {
                    throw new StatusCode\NotFoundException('Cant use own referal code.');
                }

                $connection->insert('referal', [
                    'flag' => 1,
                    'user_code' => $userId,
                    'referal_code' => $referalId,
                    'created' => $now->format('Y-m-d H:i:s'),
                ]);
            } else {
                return $this->response->build(201, [], [
                    'success' => true,
                    'message' => 'Your Referal has been set.',
                ]);
            }
        } else {
            throw new StatusCode\NotFoundException('Unknown user.');
        }



        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Insert successful',
        ]);
    }
}
