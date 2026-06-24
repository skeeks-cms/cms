<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\widgets\user;

use common\models\User;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

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
        $isOnline = (bool)$user->isOnline;
        $widgetOptions = (array)$this->options;
        $size = ArrayHelper::remove($widgetOptions, 'height', '8px');
        $size = trim((string)$size, " ;\t\n\r\0\x0B");

        if (!$size) {
            $size = '8px';
        }

        $css = <<<CSS
.sx-user-online-indicator {
    --sx-user-online-size: 8px;
    display: inline-block;
    width: var(--sx-user-online-size);
    height: var(--sx-user-online-size);
    margin-left: 4px;
    border-radius: 50%;
    vertical-align: middle;
    background: #9aa3ad;
    box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(154, 163, 173, 0.22);
}
.sx-user-online-indicator.is-online {
    background: #18b957;
    box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(24, 185, 87, 0.22);
}
.sx-user-online-indicator.is-offline {
    background: #ef4444;
    box-shadow: 0 0 0 2px #fff, 0 0 0 3px rgba(239, 68, 68, 0.20);
}
.sx-user-online-indicator--header {
    --sx-user-online-size: 11px;
    margin-left: 8px;
    transform: translateY(-1px);
}
CSS;
        $this->view->registerCss($css, [], 'sx-user-online-indicator');

        $style = ArrayHelper::remove($widgetOptions, 'style', '');

        $options = ArrayHelper::merge($widgetOptions, [
            'title'       => $isOnline ? \Yii::t('skeeks/cms', 'Online') : \Yii::t('skeeks/cms', 'Offline'),
            'data-toggle' => 'tooltip',
            'style'       => "--sx-user-online-size: {$size}; {$style}",
        ]);
        Html::addCssClass($options, 'sx-user-online-indicator');
        Html::addCssClass($options, $isOnline ? 'is-online' : 'is-offline');

        return Html::tag('span', '', $options);
    }
}
