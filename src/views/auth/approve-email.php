<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 25.03.2015
 */

/* @var $this yii\web\View */

use yii\helpers\Html;
use skeeks\cms\base\widgets\ActiveFormAjaxSubmit as ActiveForm;
use \skeeks\cms\helpers\UrlHelper;

$this->title = \Yii::t('skeeks/cms', 'Getting a new password');
\Yii::$app->breadcrumbs->createBase()->append($this->title);
?>
<div class="row">
    <section id="sidebar-main" class="col-md-12">
        <div id="content">
            <div class="row">
                <div class="col-lg-3"></div>
                <div class="col-lg-6">
                    <h1><?= $message; ?></h1>
                    <?= Html::a(\Yii::t('skeeks/cms', 'Authorization'),
                        UrlHelper::constructCurrent()->setRoute('cms/auth/login')->toString()) ?> |
                    <?= Html::a(\Yii::t('skeeks/cms', 'Registration'),
                        UrlHelper::constructCurrent()->setRoute('cms/auth/register')->toString()) ?>
                </div>
                <div class="col-lg-3"></div>
            </div>
        </div>
    </section>
</div>
