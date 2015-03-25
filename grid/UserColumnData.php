<?php
/**
 * UserColumnData
 *
 * TODO: доработать фильтр
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 30.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\grid;

use skeeks\cms\models\User;
use yii\grid\DataColumn;
use yii\helpers\Html;

/**
 * Class UserColumnData
 * @package skeeks\cms\grid
 */
class UserColumnData extends DataColumn
{
    /**
     * @inheritdoc
     */
    protected function renderDataCellContent($model, $key, $index)
    {
        $userId = (int) $model->{$this->attribute};
        $user = User::findOne($userId);

        if ($user)
        {
            if (!$srcImage = $user->getAvatarSrc())
            {
                $srcImage = \Yii::$app->cms->moduleAdmin()->noImage;
            }

            return Html::img($srcImage, [
                'width' => 25,
                'style' => 'margin-right: 5px;'
            ]) . $user->getDisplayName();
        } else
        {
            return null;
        }
    }
}