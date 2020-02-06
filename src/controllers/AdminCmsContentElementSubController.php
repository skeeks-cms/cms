<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\modules\admin\actions\modelEditor\AdminModelEditorAction;
use yii\helpers\ArrayHelper;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsContentElementSubController extends AdminCmsContentElementController
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        die;
        $result = ArrayHelper::merge(parent::actions(), [
            'index' => [
                'configKey'      => $this->uniqueId."-".($this->content ? $this->content->id : ""),
                'on afterRender' => [$this, 'contentEdit'],
                //'url' => [$this->uniqueId, 'content_id' => $this->content->id],
                'on init'        => function ($e) {
                    $action = $e->sender;
                    /**
                     * @var $action BackendGridModelAction
                     */
                    if ($this->content) {
                        $action->url = ["/".$action->uniqueId, 'content_id' => $this->content->id];
                        $this->initGridData($action, $this->content);
                    }

                },

            ],
        ]);

        //Дополнительные свойства
        return $result;
    }
}
