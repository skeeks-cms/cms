<?php
/**
 * Меню правовых документов сайта.
 *
 * @var $this yii\web\View
 * @var $class string
 */
use yii\helpers\Url;

$items = [
    '/cms/legal/privacy-policy' => "Политика конфиденциальности",
    '/cms/legal/cookie'         => "Политика обработки файлов cookie",
    '/cms/legal/personal-data'  => "Политика в отношении обработки персональных данных",
    '/cms/legal/consent'        => "Согласие на обработку персональных данных",
];
?>
<ul class="<?php echo isset($class) ? $class : 'list-unstyled sx-col-menu'; ?>">
    <?php foreach ($items as $route => $name) : ?>
        <li><a href="<?php echo Url::to([$route]); ?>"><?php echo $name; ?></a></li>
    <?php endforeach; ?>
</ul>
