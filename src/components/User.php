<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
namespace skeeks\cms\components;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class User extends \yii\web\User
{
    public $preLoginSessionName = '__preLogin';

    public function preLogin($user)
    {
        \Yii::$app->session->set($this->preLoginSessionName, $user->id);

        return $this;
    }

    public function getPreLoginIdentity()
    {

    }
}