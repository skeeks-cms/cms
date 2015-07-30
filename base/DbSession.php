<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\helpers\Request;
use yii\helpers\Json;

use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Class DbSession
 * @package skeeks\cms\base
 */
class DbSession extends \yii\web\DbSession
{
    public $sessionTable = '{{%cms_session}}';

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        try {
            $expire = time() + $this->getTimeout();
            $query = new Query();
            $exists = $query->select(['id'])
                ->from($this->sessionTable)
                ->where(['id' => $id])
                ->createCommand($this->db)
                ->queryScalar();
            if ($exists === false) {
                $this->db->createCommand()
                    ->insert($this->sessionTable, [
                        'id'                => $id,
                        'data'              => serialize($data),
                        'expire'            => $expire,
                        'created_at'        => \Yii::$app->formatter->asTimestamp(time()),
                        'updated_at'        => \Yii::$app->formatter->asTimestamp(time()),
                        'ip'                => Request::getRealUserIp(),
                        'data_server'       => Json::encode($_SERVER),
                        'data_cookie'       => Json::encode($_COOKIE),
                    ])->execute();
            } else {
                $this->db->createCommand()
                    ->update($this->sessionTable, [
                        'data'              => $data,
                        'updated_at'        => \Yii::$app->formatter->asTimestamp(time()),
                        'ip'                => Request::getRealUserIp(),
                        'data_server'       => Json::encode($_SERVER),
                        'data_cookie'       => Json::encode($_COOKIE),
                        'expire'            => $expire]
                        , ['id' => $id]
                    )
                    ->execute();
            }
        } catch (\Exception $e) {
            $exception = ErrorHandler::convertExceptionToString($e);
            // its too late to use Yii logging here
            error_log($exception);
            echo $exception;

            return false;
        }

        return true;
    }
}
