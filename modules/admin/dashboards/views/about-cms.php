<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 17.02.2016
 */
/* @var $this yii\web\View */
$this->registerCss(<<<CSS
.sx-about-cms
{
    padding: 15px;
    font-size: 15px;
}

.sx-about-cms ul li
{
    margin-left: 15px;
}
.sx-about-cms img
{
    max-width: 200px;
    float: left;
    margin: 10px;
}
CSS
)
?>

<div class="row sx-about-cms">
    <div class="col-md-12 col-lg-12">

        <a href="http://cms.skeeks.com" target="_blank">
            <img src="http://cms.skeeks.com/img/box_cms.svg" />
        </a>

        <p>
            <b>«SkeekS CMS»</b> - профессиональная система управления веб-проектами, универсальный программный продукт для создания, поддержки и успешного развития.
        </p>

        <p>
            <ul>
                <li><a href="http://cms.skeeks.com" target="_blank">cms.skeeks.com</a> — Веб сайт.</li>
                <li><a href="http://dev.cms.skeeks.com/docs/dev" target="_blank">dev.cms.skeeks.com/docs/dev</a> — Документация.</li>
                <li><a href="http://marketplace.cms.skeeks.com/" target="_blank">marketplace.cms.skeeks.com</a> — Готовые решения.</li>
            </ul>
        </p>

    </div>
</div>


