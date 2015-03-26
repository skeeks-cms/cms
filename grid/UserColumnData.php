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
use skeeks\widget\chosen\Chosen;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class UserColumnData
 * @package skeeks\cms\grid
 */
class UserColumnData extends DataColumn
{
    public function init()
    {
        parent::init();

        $this->filter = ArrayHelper::map(
            \Yii::$app->cms->findUser()->all(),
            'id',
            'name'
        );
        /*
        $this->filter = Chosen::begin([
            'model' => $this->m
            'items' => ArrayHelper::map(
                \Yii::$app->cms->findUser()->all(),
                'id',
                'name'
            )
        ])->run();*/
    }
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