<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 *
 * @var $loadedComponents
 * @var $component \skeeks\cms\base\Component
 * @var $loadedComponents \skeeks\cms\base\Component[]
 */
/* @var $this yii\web\View */

$this->registerJs(<<<JS
$("a:first", $(".sx-active-component")).click();
JS
);
$this->registerCss(<<<CSS
.sx-components .card:hover {
    box-shadow: 0px 0px 11px 0px #dcdcdc;
}
.sx-components .card img {
    margin-bottom: 20px;
}
.sx-components .card h3 {
    font-size: 18px;
}
.sx-components .card {
    cursor: pointer;
    padding: 15px;
    min-height: 100%;
    background: white;
}
.sx-components .card a:hover {
    text-decoration: none;
}
.g-height-100 {
    height: 100px !important;
}
.rounded-circle {
    border-radius: 50%!important;
}
.sx-component {
    padding-bottom: 30px;
}
.sx-main-col {
    background: var(--bg-gray);
}
CSS
);

?>

<div class="" style="padding: 15px;">
    <div class="row sx-components">
        <? foreach ($loadedComponents as $key => $loadedComponent) : ?>
            <?
            $url = (string)$loadedComponent->getEditUrl();

            $actionData = new \yii\web\JsExpression(\yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                "url"             => $url,
            ]));

            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 sx-component <?= $component && $key == $component->className() ? "sx-active-component" : ""; ?>">
                <div class="card sx-bg-secondary" onclick='new sx.classes.backend.widgets.Action(<?= $actionData; ?>).go(); return false;'>
                    <header class="text-center">
                        <a href="<?= $url; ?>" data-pjax="0">
                            <img class="img-fluid rounded-circle g-height-100 g-mb-14 g-mt-14"
                                 src="<?= $loadedComponent->descriptor->image; ?>"
                                 alt="<? /*= $loadedComponent->descriptor->name; */ ?>">
                        </a>

                        <h3 class="" style="">
                            <a href="<?= $url; ?>" data-pjax="0"><?= $loadedComponent->descriptor->name; ?></a>
                            <div style="font-size: 16px;"><?= $loadedComponent->descriptor->description; ?></div>
                        </h3>
                    </header>
                </div>
            </div>
        <? endforeach; ?>
    </div>
</div>

