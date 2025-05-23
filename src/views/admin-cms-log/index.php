<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/**
* @var $this yii\web\View
*/

echo \skeeks\cms\widgets\admin\CmsLogListWidget::widget([
    'query' => \skeeks\cms\models\CmsLog::find()
]);
?>

