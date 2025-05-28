
<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

?>

<div class="sx-contant-page" id="sx-page-legal-cookie" style="min-height: 50vh; padding-top: 2rem;">
    <div class="container sx-container">
        <?= $this->render('@app/views/breadcrumbs', [
            'title' => "Правовая информация",
            'model' => null,
        ]); ?>
        <ul class="sx-menu list-unstyled">
            <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/privacy-policy']); ?>">Политика конфиденциальности</a></li>
            <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/cookie']); ?>">Политика обработки файлов cookie</a></li>
            <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/personal-data']); ?>">Политика в отношении обработки персональных данных</a></li>
        </ul>
    </div>
</div>
