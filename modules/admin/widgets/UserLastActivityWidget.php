<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;

/**
 * Class UserLastActivityWidget
 * @package skeeks\cms\modules\admin\widgets
 */
class UserLastActivityWidget extends Widget
{
    /**
     * Runs the widget.
     */
    public function run()
    {
        $userLastActivity =
        [
            'blockedAfterTime'      => (\Yii::$app->admin->blockedTime - \Yii::$app->user->identity->lastAdminActivityAgo),
            'startTime'             => \Yii::$app->formatter->asTimestamp(time()),
        ];

        $userLastActivity           = \yii\helpers\Json::encode($userLastActivity);

        $this->view->registerJs(<<<JS
        new sx.classes.UserLastActivity({$userLastActivity});
JS
);
    }

}