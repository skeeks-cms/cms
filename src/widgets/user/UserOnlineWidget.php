<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\user;

use common\models\User;
use skeeks\cms\widgets\user\assets\UserOnlineWidgetAsset;
use yii\base\Widget;
use yii\helpers\ArrayHelper;

/**
 * Виджет отображения индикатора онлайн пользователь или офлайн
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UserOnlineWidget extends Widget
{
    /**
     * @var User
     */
    public $user = null;

    /**
     * @var null
     */
    public $options = null;

    public function run()
    {
        if (!$this->user) {
            return '';
        }
        $user = $this->user;

        if ($user->isOnline) {
            $options = ArrayHelper::merge($this->options, [
                'title' => \Yii::t('skeeks/cms', 'Online'),
                'data-toggle' => 'tooltip',
            ]);

            $online = \yii\helpers\Html::img(UserOnlineWidgetAsset::getAssetUrl('icons/round_green.gif'), $options);
        } else {
            $options = ArrayHelper::merge($this->options, [
                'title' => \Yii::t('skeeks/cms', 'Offline'),
                'data-toggle' => 'tooltip',
            ]);

            $online = \yii\helpers\Html::img(UserOnlineWidgetAsset::getAssetUrl('icons/round_red.gif'), $options);
        }

        return $online;
    }
}