<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\grid;

use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\User;
use yii\grid\DataColumn;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UserColumnData extends DataColumn
{
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $userId = (int)$model->{$this->attribute};
        $user = User::findOne($userId);

        if ($user) {
            return \Yii::$app->view->render('@skeeks/cms/grid/views/user-column', [
                'user' => $user
            ]);
        } else {
            return null;
        }
    }


}