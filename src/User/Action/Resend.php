<?php

namespace App\User\Action;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use Firebase\JWT\JWT;
use Fusio\Impl\Service\User;
use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;
use Fusio\Engine\Dependency;
use PSX\Framework\Oauth2\GrantTypeFactory;

class Resend extends ActionAbstract
{

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $payload = [
            'user_code' => 1,
        ];
        var_dump(JWT::encode($payload, 'referal_code'));
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('API-Connection');

        $body = $request->getBody();
        $now  = new \DateTime();

        $object = $connection->fetchAssoc('SELECT * FROM fusio_user WHERE email = :email', [
            'email' => $body->email
        ]);
        var_dump($object);
        if (empty($object)) {

        }

        return $this->response->build(201, [], [
            'success' => true,
            'message' => 'Resend email successful',
        ]);
    }

    public function send($subject, array $to, $body)
    {
        $message = \Swift_Message::newInstance();
        $message->setSubject($subject);

        $sender = $this->config->getValue('mail_sender');
        if (!empty($sender)) {
            $message->setFrom([$sender]);
        }

        $message->setTo($to);
        $message->setBody($body);

        $this->logger->info('Send registration mail', [
            'subject' => $subject,
            'body'    => $body,
        ]);

        $this->mailer->send($message);
    }
}
