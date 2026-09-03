<?php

use skeeks\cms\backend\widgets\ActiveFormAjaxBackend;
use skeeks\cms\forms\CmsWorkerAddForm;
use skeeks\cms\models\CmsUser;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $model CmsWorkerAddForm */

?>

<?php $pjax = Pjax::begin(); ?>
<?php $form = ActiveFormAjaxBackend::begin(); ?>

<?= $form->errorSummary($model); ?>

<?= $form->field($model, 'user_id')->widget(AjaxSelectModel::class, [
    'modelClass'         => CmsUser::class,
    'modelShowAttribute' => 'shortDisplayNameWithAlias',
    'searchQuery'        => function ($word = '') {
        $query = CmsUser::find()
            ->cmsSite()
            ->isWorker(false)
            ->andWhere([CmsUser::tableName().'.is_company' => 0]);

        if ($word !== '') {
            $query->search($word);
        }

        return $query;
    },
]); ?>

<?= $form->buttonsStandart($model, ['save']); ?>

<?php ActiveFormAjaxBackend::end(); ?>
<?php Pjax::end(); ?>
