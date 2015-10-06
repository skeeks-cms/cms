<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 30.09.2015
 */
namespace skeeks\cms\modules\admin\widgets;
use yii\base\Widget;
use yii\helpers\Url;

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
        $userLastActivity           = \yii\helpers\Json::encode($this->getOptions());

        $this->view->registerJs(<<<JS
        new sx.classes.UserLastActivity({$userLastActivity});
JS
);
    }

    public function getOptions()
    {
        return [
            'blockedAfterTime'      => (\Yii::$app->admin->blockedTime - \Yii::$app->user->identity->lastAdminActivityAgo),
            'startTime'             => \Yii::$app->formatter->asTimestamp(time()),
            'leftTimeInfo'          => 30,
            'isGuest'               => (bool) \Yii::$app->user->isGuest,
            'ajaxLeftTimeInfo'      => 300,
            'interval'              => 5,
            'backendGetUser'        => Url::to(['/cms/tools/admin-last-activity']),
        ];
    }

}