<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 01.09.2015
 */
/* @var $this yii\web\View */
/* @var $searchModel \skeeks\cms\models\Search */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $model \skeeks\cms\models\CmsContentElement */
?>
<? \yii\bootstrap\Alert::begin([
    'options' => [
        'class' => 'alert-danger',
    ],
]); ?>
    Вам необходимо настроить компонент i18n:
<pre><code>'i18n' => [
    'class' => 'skeeks\cms\i18n\components\I18NDb',
]</code></pre>

<? \yii\bootstrap\Alert::end(); ?>