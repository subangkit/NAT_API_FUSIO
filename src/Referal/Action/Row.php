<?php

namespace App\Referal\Action;

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

        $object = $connection->fetchAssoc('SELECT * FROM referal WHERE user_code = :code', [
            'code' => $request->getUriFragment('user_code')
        ]);

        $uplineCounter = 0;
        $upline = $this->get_recrusive_upline(
            $request->getUriFragment('user_code'), $uplineCounter);

        $currentLevel = $this->get_current_level($upline);

        $downlineCounter = 0;
        $totalCommision = [
            "root" => 0.0,
            "lvl1" => 0.0,
            "lvl2" => 0.0,
            "lvl3" => 0.0,
        ];
        $downline = $this->get_recrusive_downline(
            $request->getUriFragment('user_code'), $downlineCounter, $totalCommision, $currentLevel,$currentLevel);

        $topLevelId =$this->get_top_level($upline);
        $uplineFromTop = array();
        if ($topLevelId != 0) {
            $uplineFromTop = $this->get_recrusive_downline_until($topLevelId, $request->getUriFragment('user_code'));

        }

        $object = array(
            'currentLevel' => $currentLevel,
            'upline' => $uplineFromTop,
            'downline' => $downline,
            'uplineCounter' => $uplineCounter,
            'downlineCounter' => $downlineCounter,
            'totalCommission' => $totalCommision
        );

        if (empty($object)) {
            throw new StatusCode\NotFoundException('Entry not available');
        }

        return $this->response->build(200, [], $object);
    }

    protected function get_current_level($upline, $level = 1) {
        if (!empty($upline)) {
            if (!empty($upline[0]['parent']))
                return $this->get_current_level($upline[0]['parent'],$level+1);
            return $level+1;
        }
        return $level;
    }

    protected function get_top_level($upline) {
        if (!empty($upline)) {
            if (!empty($upline[0]['parent']))
                return $this->get_top_level($upline[0]['parent']);
            return $upline[0]['referal_code'];
        }
        return 0;
    }

    protected function get_recrusive_downline($objectId, &$downlineCounter, &$totalCommision, $currentLevel, $level=0) {
        $connection = $this->connector->getConnection('Default-Connection');
        $api = $this->connector->getConnection('API-Connection');
        $apiParams = $api->getParams();

        $childLevel = $level+1;
        $list = $connection->fetchAll(
            'SELECT a.user_code, b.`name`,b.email, c.firstname, c.lastname, COALESCE(trx.NTC,0) NTC, '.
            'CASE WHEN '.$childLevel.' = 2 AND '.$currentLevel.' <= 3 THEN 10/100*COALESCE(trx.NTC,0) '.
            'WHEN '.$childLevel.' = 3 AND '.$currentLevel.' <= 3 THEN 2/100*COALESCE(trx.NTC,0) '.
            'WHEN '.$childLevel.' >= 4 AND '.$currentLevel.' <= 3 THEN 1/100*COALESCE(trx.NTC,0) END commision FROM referal a '.
            'LEFT JOIN '.$apiParams['dbname'].'.fusio_user b ON a.user_code = b.id '.
            'LEFT JOIN profile c ON c.user_code = a.user_code '.
            'LEFT JOIN ( SELECT user_code, SUM(ntc) NTC FROM `btctransfer` '.
            'WHERE claimed = 1 '.
            'GROUP BY user_code) trx ON trx.user_code = a.user_code WHERE a.referal_code = :referal_code', [
            'referal_code' => $objectId
        ]);

        $data = array();

        if (!empty($list)) {
            $level ++;
            foreach ($list as $index => $row) {
                $downlineCounter += 1;
                $totalCommision["root"] += floatval($row['commision']);
                $totalCommision["lvl".($level-1)] += floatval($row['commision']);
                $row['level'] = $level;
                $row['children'] = $this->get_recrusive_downline($row['user_code'], $downlineCounter, $totalCommision, $currentLevel, $level+1);
                array_push($data,$row);
            }
        }

        return $data;
    }

    protected function get_recrusive_upline($objectId, &$uplineCounter, $level=0) {
        $connection = $this->connector->getConnection('Default-Connection');
        $api = $this->connector->getConnection('API-Connection');
        $apiParams = $api->getParams();

        $list = $connection->fetchAll(
            'SELECT a.referal_code, b.`name`,b.email, c.firstname, c.lastname FROM referal a '.
            'LEFT JOIN '.$apiParams['dbname'].'.fusio_user b ON a.referal_code = b.id '.
            'LEFT JOIN profile c ON c.user_code = a.user_code WHERE a.user_code = :user_code', [
            'user_code' => $objectId
        ]);

        $data = array();

        if (!empty($list)) {
            foreach ($list as $index => $row) {
                $uplineCounter += 1;

                $row['level'] = $level;
                $row['parent'] = $this->get_recrusive_upline($row['referal_code'], $uplineCounter, $level+1);
                array_push($data,$row);
            }
        }

        return $data;
    }

    protected function get_recrusive_downline_until($objectId, $stopId, $level=0, $stat = true, $init = true) {
        $connection = $this->connector->getConnection('Default-Connection');
        $api = $this->connector->getConnection('API-Connection');
        $apiParams = $api->getParams();

        $current = $connection->fetchAssoc(
            'SELECT b.id user_code, b.`name`,b.email, c.firstname, c.lastname FROM '.$apiParams['dbname'].'.fusio_user b LEFT JOIN profile c ON c.user_code = b.id  WHERE b.id = :user_code', [
            'user_code' => $objectId
        ]);

        $list = $connection->fetchAll(
            'SELECT a.user_code, b.`name`,b.email, c.firstname, c.lastname FROM referal a '.
            'LEFT JOIN '.$apiParams['dbname'].'.fusio_user b ON a.user_code = b.id '.
            'LEFT JOIN profile c ON c.user_code = a.user_code  WHERE a.referal_code = :referal_code', [
            'referal_code' => $objectId
        ]);

        $data = array();

        if ($init) {
            $data[0] = $current;
            $data[0]['children'] = array();
        }

        if (!empty($list)) {
            $level ++;
            foreach ($list as $index => $row) {
                $row['level'] = $level;
                if ($row['user_code'] == $stopId) $stat = false;
                if ($stat)
                    $row['children'] = $this->get_recrusive_downline_until($row['user_code'], $stopId, $level+1, $stat, false);
                else
                    $row['children'] = array();

                if ($init)
                    array_push($data[0]['children'],$row);
                else
                    array_push($data,$row);
            }
        }

        return $data;
    }
}
