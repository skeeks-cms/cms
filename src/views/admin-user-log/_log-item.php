<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

/** 
* @var $this yii\web\View
* @var $model \skeeks\cms\models\CmsUserLog
*/

?>

<div class="sx-project-content">
    <div class="sx-small-info">
        <?php echo \Yii::$app->formatter->asDatetime($model->created_at); ?>
        <?php if($model->created_by) : ?>
            / <?php echo $model->createdBy->shortDisplayName; ?>
        <?php endif; ?>
    </div>
    
    <?php echo $model->action_type; ?> / <?php echo $model->model; ?>
    <pre>
        <?php echo print_r($model->action_data, true); ?>
    </pre>
    
</div>
