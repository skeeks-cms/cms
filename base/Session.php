<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.07.2015
 */
namespace skeeks\cms\base;
use skeeks\cms\components\Cms;
use yii\web\DbSession;

/**
 * Class Session
 * @package skeeks\cms\base
 */
class Session extends \yii\web\Session
{
    /**
     * @var DbSession
     */
    public $dbSession = null;

    public function init()
    {
        if (\Yii::$app->cms->sessionType == Cms::SESSION_DB)
        {
            $this->dbSession = \Yii::$app->dbSession;
        } else
        {
            parent::init();
        }
    }


    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        if ($this->dbSession)
        {
            return $this->dbSession->getUseCustomStorage();
        } else
        {
            parent::getUseCustomStorage();
        }
    }

    /**
     * Updates the current session ID with a newly generated one .
     * Please refer to <http://php.net/session_regenerate_id> for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ($this->dbSession)
        {
            return $this->dbSession->regenerateID($deleteOldSession);
        } else
        {
            parent::regenerateID($deleteOldSession);
        }
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        if ($this->dbSession)
        {
            return $this->dbSession->readSession($id);
        } else
        {
            parent::readSession($id);
        }
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        if ($this->dbSession)
        {
            return $this->dbSession->writeSession($id, $data);
        } else
        {
            parent::writeSession($id, $data);
        }
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        if ($this->dbSession)
        {
            return $this->dbSession->destroySession($id);
        } else
        {
            parent::destroySession($id);
        }
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        if ($this->dbSession)
        {
            return $this->dbSession->gcSession($maxLifetime);
        } else
        {
            parent::gcSession($maxLifetime);
        }
    }
}