<?php

use yii\helpers\Html;

use yii\widgets\ActiveForm;

/**
 * index
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 14.10.2014
 * @since 1.0.0
 */

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $personal bool */
$controller = $this->context;
$model = $controller->user;
$this->title = $model->getDisplayName();
\Yii::$app->breadcrumbs->createBase()->append($this->title);
?>


<div class="row">

    <aside id="sidebar-right" class="col-md-3">
        <div id="column-right" class="hidden-xs sidebar">
            <h1 class="heading_title"><span><?= $model->getDisplayName(); ?></span></h1>
        </div>
    </aside>

    <section id="sidebar-main" class="col-md-6">

        <div id="content">
            <h1 class="heading_title"><span><?= $model->name; ?></span></h1>
            <div class="category-info clearfix">
                <div class="category-description wrapper">

                </div>
            </div>

        </div>
    </section>

</div>
<!--
 $ospans: allow overrides width of columns base on thiers indexs. format array( column-index=>span number ), example array( 1=> 3 )[value from 1->12]
-->




