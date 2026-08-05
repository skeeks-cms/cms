<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 *
 * @var \skeeks\cms\models\CmsTree $model
 */

use skeeks\cms\backend\widgets\BackendEntityLink;

/* @var $this yii\web\View */
/* @var $widget \skeeks\cms\widgets\tree\CmsTreeWidget */
/* @var $model \skeeks\cms\models\CmsDepartment */
/*   */

$widget = $this->context;

$result = $model->name;
$additionalName = '';
/*if ($model->level == 0) {
    $site = \skeeks\cms\models\CmsSite::findOne(['id' => $model->cms_site_id]);
    if ($site) {
        $additionalName = $site->name;
    }
} else {
    if ($model->name_hidden) {
        $additionalName = $model->name_hidden;
    }
}*/

if ($additionalName) {
    $result .= " [{$additionalName}]";
}


?>

<div class="sx-department sx-surface sx-surface--padded">
    <div class="sx-department__header">
        <div class="sx-label-node sx-department__identity">
            <?php echo BackendEntityLink::widget([
                'controllerId' => '/cms/admin-cms-department',
                'modelId'      => $model->id,
                'label'        => $result,
                'options'      => [
                    'class'      => 'sx-department__title sx-collection-cell__primary',
                    'aria-label' => $result,
                ],
            ]); ?>
        </div>

        <div class="sx-department__actions">
            <button type="button" class="btn btn-default btn-sm add-tree-child"
                    title="<?= \Yii::t('skeeks/cms', 'Create subsection'); ?>"
                    data-id="<?= (int) $model->id; ?>">
                <span class="fa fa-plus" aria-hidden="true"></span>
                Добавить отдел
            </button>

            <?php if ($model->pid > 0) : ?>
                <button type="button" class="btn btn-default btn-sm sx-tree-move"
                        title="<?= \Yii::t('skeeks/cms', 'Change sorting'); ?>"
                        aria-label="<?= \Yii::t('skeeks/cms', 'Change sorting'); ?>">
                    <span class="fas fa-arrows-alt-v" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($model->supervisor) : ?>
        <div class="sx-department__meta sx-collection-cell__secondary">
            <span class="sx-department__meta-label">Руководитель:</span>
            <?php echo BackendEntityLink::widget([
                'controllerId' => '/cms/admin-user',
                'modelId'      => $model->supervisor->id,
                'label'        => $model->supervisor->shortDisplayName,
                'options'      => ['class' => 'sx-preview-card__related'],
            ]); ?>
        </div>
    <?php endif; ?>

    <?php if ($model->workers) : ?>
        <div class="sx-department__meta sx-collection-cell__secondary">
            <span class="sx-department__meta-label">Сотрудники:</span>
            <span class="sx-department__people">
                <?php foreach ($model->workers as $worker) : ?>
                    <?php echo BackendEntityLink::widget([
                        'controllerId' => '/cms/admin-user',
                        'modelId'      => $worker->id,
                        'label'        => $worker->shortDisplayName,
                        'options'      => ['class' => 'sx-preview-card__related'],
                    ]); ?>
                <?php endforeach; ?>
            </span>
        </div>
    <?php endif; ?>
</div>

