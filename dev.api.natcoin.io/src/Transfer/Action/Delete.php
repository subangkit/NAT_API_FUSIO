<?php

namespace App\Transfer\Action;

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use PSX\Framework\Util\Uuid;
use PSX\Http\Exception as StatusCode;

class Delete extends ActionAbstract
{
    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->connector->getConnection('Default-Connection');

        $now  = new \DateTime();

        $affected = $connection->update('transfer', [
            'flag' => 0,
            'deleted' => $now->format('Y-m-d H:i:s'),
        ], [
            'code' => $request->getUriFragment('transfer_code')
        ]);

        if (empty($affected)) {
            throw new StatusCode\NotFoundException('Entry not available');
        }

        return $this->response->build(200, [], [
            'success' => true,
            'message' => 'Delete successful',
        ]);
    }
}
