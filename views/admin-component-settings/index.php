<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $loadedComponents
 * @var $component \skeeks\cms\base\Component
 */
/* @var $this yii\web\View */

?>

<? if ($component && $component->existsConfigFormFile()) : ?>
    <h1>Настройки компонента: <?= $component->descriptor->name; ?></h1>
    <hr />
    <div class="row">
        <div class="col-lg-2">
            <ul class="nav nav-pills nav-stacked">
              <li role="presentation" class="active"><a href="#">Настройки по умолчанию</a></li>
              <li role="presentation"><a href="#">Настройки сайтов</a></li>
              <li role="presentation"><a href="#">Настройки пользователей</a></li>
              <!--<li role="presentation"><a href="#">Настройки языков</a></li>-->
            </ul>
        </div>

        <div class="col-lg-10">
            <p>
                <? if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponent($component)) : ?>
                    <button type="submit" class="btn btn-danger btn-xs">
                        <i class="glyphicon glyphicon-remove"></i> сбросить настройки
                    </button>
                <? endif; ?>
            </p>
            <?= $component->renderConfigForm(); ?>
        </div>
    </div>

<? else: ?>
    <p>У этого компонента нет настроек</p>
<? endif; ?>


<?/* \skeeks\cms\modules\admin\widgets\Pjax::end(); */?>
