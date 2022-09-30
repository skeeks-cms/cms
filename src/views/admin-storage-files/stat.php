<?php
/* @var $model \skeeks\cms\models\CmsUser */
/* @var $this yii\web\View */
/* @var $controller \skeeks\cms\controllers\AdminCmsContentElementController
 * /* @var $action \skeeks\cms\backend\actions\BackendModelCreateAction|\skeeks\cms\backend\actions\IHasActiveForm
 */
/* @var $model \common\models\User */
$controller = $this->context;
$action = $controller->action;

$size = \skeeks\cms\models\CmsStorageFile::find()->cmsSite()->select([
    "size" => new \yii\db\Expression("sum(size)"),
])->asArray()->one();
$sizeByExtebsions = \skeeks\cms\models\CmsStorageFile::find()->cmsSite()
    ->select([
        "size"      => new \yii\db\Expression("sum(size)"),
        "extension" => "extension",
    ])
    ->groupBy(['extension'])
    ->orderBy(['size' => SORT_DESC])
    ->asArray()->all();
$totalSize = \Yii::$app->formatter->asShortSize($size['size']);
?>
<div class="row" style="margin-top: 20px;">
    <div class="col-12" style="max-width: 500px;">
        <table class="table table-bordered">
            <tr>
                <td>
                    <b>Суммарный размер всех файлов</b>
                </td>
                <td>
                    <b><?php echo $totalSize; ?></b>
                </td>
            </tr>

            <? foreach ($sizeByExtebsions as $data) : ?>
                <tr>
                    <td>
                        <?php echo \yii\helpers\ArrayHelper::getValue($data, "extension"); ?>
                    </td>
                    <td>
                        <?php echo \Yii::$app->formatter->asShortSize($data['size']); ?>
                    </td>
                </tr>
            <? endforeach; ?>
        </table>
    </div>
</div>
