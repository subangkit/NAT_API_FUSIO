<?php

namespace App\Ethaccount\Action;

require_once(__DIR__."/../../../vendor/devextreme/LoadHelper.php");

use Fusio\Engine\ActionAbstract;
use Fusio\Engine\ContextInterface;
use Fusio\Engine\ParametersInterface;
use Fusio\Engine\RequestInterface;
use DevExtreme\DbSet;
use DevExtreme\DataSourceLoader;
use Doctrine\DBAL\Driver\Mysqli\MysqliConnection;

class Collection extends ActionAbstract
{
    private $dbSet;

    function _json_decode($str) {
        if(is_string($str)) {
            if (json_decode($str,true) != NULL) {
                $str = json_decode($str,true);
                return $this->_json_decode($str);
            }
            return $str;
        } else if (is_array($str)) {
            foreach ($str as $index => $value) {
                $str[$index] = $this->_json_decode($value);
            }
            return $str;
        }
    }

    public function handle(RequestInterface $request, ParametersInterface $configuration, ContextInterface $context)
    {
        $connection = $this->connector->getConnection('Default-Connection');
        $connectionParams = $connection->getParams();
        $mySQL = new MysqliConnection(['dbname' => $connectionParams['dbname'], 'host' => $connectionParams['host']], $connectionParams['user'], $connectionParams['password']);
        $this->dbSet = new DbSet($mySQL->getWrappedResourceHandle(), "profile");
        $param = $_GET;

        $param['requireTotalCount'] = true;
        if (isset($param['skip']))
            $param['skip'] = (int) $param['skip'];

        if (isset($param['take']))
        $param['take'] = (int) $param['take'];

        if (isset($param['filter'])) {
            $param['filter'] = $this->_json_decode($param['filter']);
        }


        if (isset($param['sort'])){
            $sortArray = json_decode($param['sort'],true);
            if (isset($sortArray['selector']))
                $param['sort'] = [json_decode($param['sort'])];
            else {
                $param['sort'] = $sortArray;
            }
        }

        if (isset($param['group'])){
            $sortArray = json_decode($param['group'],true);
            if (isset($sortArray['selector']))
                $param['group'] = [json_decode($param['group'])];
            else {
                $param['group'] = $sortArray;
            }
        }

        $result = DataSourceLoader::Load($this->dbSet, $param);
        if (!isset($result)) {
            $result = $this->dbSet->GetLastError();
        }
        return $this->response->build(200, [], $result);
    }
}
