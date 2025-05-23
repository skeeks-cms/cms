<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
 * @var $this yii\web\View
 * @var $models \skeeks\cms\models\CmsUser[]
 */
?>
<?php if ($models) : ?>
    <?php foreach ($models as $model) : ?>
        <div class="col-12">
            <div class="card g-pa-15 g-mb-10 sx-shadow--hover sx-company-card">
                <?php echo \skeeks\cms\widgets\admin\CmsUserViewWidget::widget([
                    'cmsUser' => $model,
                ]); ?>
                <?php echo \yii\helpers\Html::tag("div", "Добавить", [
                    'class' => 'btn btn-sm btn-primary sx-btn-select',
                    'style' => '    position: absolute;
    right: 20px;
    top: calc(50% - 15px);',
                    'data'  => $model->toArray(),
                ]); ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>