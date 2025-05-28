<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
?>

<div class="sx-contant-page" id="sx-page-legal-cookie">
    <div class="container sx-container">

        <div class="row">
            <div class="order-md-1 col-md-3 col-12" style="padding-top: 2rem;">
                <ul class="list-unstyled sx-col-menu">
                    <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/privacy-policy']); ?>">Политика конфиденциальности</a></li>
                    <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/cookie']); ?>">Политика обработки файлов cookie</a></li>
                    <li><a href="<?php echo \yii\helpers\Url::to(['/cms/legal/personal-data']); ?>">Политика в отношении обработки персональных данных</a></li>
                </ul>
            </div>

            <div class="order-md-2 col-md-9 col-12" style="padding-top: 2rem;">

                <?= $this->render('@app/views/breadcrumbs', [
                    'title' => "Политика обработки файлов cookie",
                    'model' => null,
                ]); ?>

                <?php echo \Yii::$app->legal->textCookie; ?>
            </div>
        </div>
    </div>
</div>
