
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
        <?php echo $this->render('_menu', ['class' => 'sx-menu list-unstyled']); ?>
    </div>
</div>
