<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 08.06.2015
 */
/* @var $this yii\web\View */
/* @var $data array */
echo <<<HTML
<?xml version="1.0" encoding="UTF-8"?>\n
HTML;
?>
<!--	Created by <?= \Yii::$app->cms->descriptor->copyright; ?> -->
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<? foreach($data as $item) : ?>
<url>
    <loc><?= $item['loc']; ?></loc>
    <? if (isset($item['lastmod'])) : ?><lastmod><?= $item['lastmod']; ?></lastmod><? endif; ?>

</url>
<? endforeach; ?>
</urlset>