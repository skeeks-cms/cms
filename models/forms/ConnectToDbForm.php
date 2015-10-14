<?php
/**
 * LoginForm
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

namespace skeeks\cms\models\forms;

use skeeks\cms\components\Cms;
use skeeks\cms\helpers\db\DbDsnHelper;
use skeeks\cms\models\User;
use skeeks\sx\File;
use Yii;
use yii\base\Model;
use yii\db\Connection;

/**
 * Class ConnectToDbForm
 * @package skeeks\cms\models\forms
 */
class ConnectToDbForm extends Model
{
    public $host = "localhost";
    public $dbname;
    public $username;
    public $password;
    public $charset = "utf8";
    public $enableSchemaCache = Cms::BOOL_Y;
    public $schemaCacheDuration = 3600; //1 час

    protected $_helperDsn = null;

    public function init()
    {
        $this->_helperDsn       = new DbDsnHelper(\Yii::$app->db);
        $this->host             = $this->_helperDsn->host;
        $this->dbname           = $this->_helperDsn->dbname;
        $this->username         = $this->_helperDsn->username;
        $this->password         = $this->_helperDsn->password;
        $this->charset          = $this->_helperDsn->charset;

        parent::init();
    }
    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'host'                  => 'Host',
            'dbname'                => 'DB Name',
            'username'              => 'Username',
            'password'              => 'Password',
            'charset'               => 'Charset',
            'enableSchemaCache'     => 'Enable Schema Cache',
            'schemaCacheDuration'   => 'Schema Cache Duration (sec)',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password', 'dbname', 'host', 'charset'], 'required'],
            // rememberMe must be a boolean value
            // password is validated by validatePassword()
            [['username', 'password', 'dbname', 'charset', 'enableSchemaCache', 'host', 'charset'], 'string'],
            [['schemaCacheDuration'], 'integer'],
        ];
    }

    /**
     * @return bool
     */
    public function hasConnect()
    {
        $connection = new Connection([
            'dsn' =>    "mysql:host={$this->host};dbname={$this->dbname}",
            'username'  => $this->username,
            'password'  => $this->password,
            'charset'   => $this->charset,
        ]);

        try
        {
            $connection->open();
            return true;
        } catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function write()
    {
        if ($this->validate() && $this->hasConnect())
        {

            $fileContent = <<<PHP
<?php
/**
 * @author Semenov Alexander <support@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.09.2015
 */
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host={$this->host};dbname={$this->dbname}',
    'username' => '{$this->username}',
    'password' => '{$this->password}',
    'charset' => '{$this->charset}',
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 3600,
];

PHP;

            $file = new File(Yii::getAlias('@common/config/db.php'));
            $file->write($fileContent);

            if ($file->isExist())
            {
                return true;
            }

            return false;
        } else
        {
            return false;
        }
    }

}

