<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (ÑêèêÑ)
 * @date 19.06.2015
 */
namespace skeeks\cms\helpers\db;

use skeeks\cms\App;
use skeeks\cms\modules\admin\components\UrlRule;
use skeeks\sx\traits\Entity;
use skeeks\sx\traits\InstanceObject;
use yii\base\Component;
use yii\base\Object;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseUrl;
use yii\helpers\Url;

/**
 * Class DbHelper
 * @package skeeks\cms\helpers\db
 */
class DbDsnHelper
    extends Component
{
    /**
     * @var Connection
     */
    public $connection = null;


    public $username    = "";
    public $password    = "";
    public $host        = "";
    public $dbname      = "";

    public function __construct(Connection $connection, $data = [])
    {
        $data['connection'] = $connection;
        parent::__construct($data);
    }

    public function init()
    {
        parent::init();
        $this->_parse();
    }

    /**
     * @return $this
     */
    protected function _parse()
    {
        $this->username = $this->connection->username;
        $this->password = $this->connection->password;

        $dsn = $this->connection->dsn;
        if ($strpos = strpos($dsn, ':'))
        {
            $dsn = substr($dsn, ($strpos + 1), strlen($this->connection->dsn));
        };

        $dsnDataTmp = explode(';', $dsn);
        if ($dsnDataTmp)
        {
            foreach ($dsnDataTmp as $data)
            {
                $tmpData = explode("=", $data);
                if (count($tmpData) >=2 )
                {
                    $propertyName   = $tmpData[0];
                    $propertyValue  = $tmpData[1];
                    if ($this->canSetProperty($propertyName))
                    {
                        $this->{$propertyName} = $propertyValue;
                    }
                }

            }
        }

        return $this;
    }
}