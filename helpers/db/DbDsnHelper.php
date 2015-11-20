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
use yii\base\Component;
use yii\base\Object;
use yii\db\Connection;
use yii\helpers\ArrayHelper;
use yii\helpers\BaseUrl;
use yii\helpers\Url;

/**
 * @property string $dsnString
 *
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
    public $charset     = "utf8";

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
     * @return string
     */
    public function getDsnString()
    {
        return "mysql:host={$this->host};dbname={$this->dbname}";
    }

    /**
     * @return Connection
     */
    public function createConnection()
    {
        return new Connection([
            'dsn' => $this->getDsnString(),
            'username' => $this->username,
            'password' => $this->password,
            'charset' => $this->charset,
        ]);
    }
    /**
     * @return $this
     */
    protected function _parse()
    {
        $this->username = $this->connection->username;
        $this->password = $this->connection->password;
        $this->charset  = $this->connection->charset;

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