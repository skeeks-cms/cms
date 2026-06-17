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
    box-shadow: 0 8px 22px rgba(72, 84, 104, 0.12);
}
.sx-components .card img {
    display: block;
    width: 72px;
    height: 72px;
    max-width: 72px;
    max-height: 72px;
    object-fit: contain;
    margin: 14px auto 20px;
}
.sx-components .card h3 {
    font-size: 18px;
    line-height: 1.25;
    margin-bottom: 0;
}
.sx-components .card {
    cursor: pointer;
    padding: 18px 15px;
    min-height: 100%;
    background: white;
    border-radius: 18px;
    border-color: #e1e6ee;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}
.sx-components .card:hover {
    border-color: #d4dbe6;
}
.sx-components .card a:hover {
    text-decoration: none;
}
.sx-components .sx-component-description {
    color: #6f7d93;
    font-size: 13px;
    line-height: 1.25;
    margin: 5px auto 0;
    max-width: 260px;
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
            $image = $loadedComponent->descriptor->image ?: \skeeks\cms\assets\CmsAsset::getAssetUrl('images/icons/admin-menu/component.svg');

            $actionData = new \yii\web\JsExpression(\yii\helpers\Json::encode([
                "isOpenNewWindow" => true,
                "url"             => $url,
            ]));

            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 sx-component <?= $component && $key == $component->className() ? "sx-active-component" : ""; ?>">
                <div class="card sx-bg-secondary" onclick='new sx.classes.backend.widgets.Action(<?= $actionData; ?>).go(); return false;'>
                    <header class="text-center">
                        <a href="<?= $url; ?>" data-pjax="0">
                            <img class="sx-component-icon"
                                 src="<?= $image; ?>"
                                 alt="<? /*= $loadedComponent->descriptor->name; */ ?>">
                        </a>

                        <h3 class="" style="">
                            <a href="<?= $url; ?>" data-pjax="0"><?= $loadedComponent->descriptor->name; ?></a>
                            <div class="sx-component-description"><?= $loadedComponent->descriptor->description; ?></div>
                        </h3>
                    </header>
                </div>
            </div>
        <? endforeach; ?>
    </div>
</div>

