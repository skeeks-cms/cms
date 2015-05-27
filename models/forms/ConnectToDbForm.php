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
use skeeks\cms\models\User;
use Yii;
use yii\base\Model;

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

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password', 'dbname'], 'required'],
            // rememberMe must be a boolean value
            // password is validated by validatePassword()
            [['username', 'password', 'dbname', 'charset', 'enableSchemaCache'], 'string'],
            [['schemaCacheDuration'], 'integer'],
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function write()
    {
        if ($this->validate())
        {
            return true;
        } else
        {
            return false;
        }
    }

}

