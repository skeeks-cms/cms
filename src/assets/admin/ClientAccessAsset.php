<?php

namespace skeeks\cms\assets\admin;

use yii\web\AssetBundle;

/**
 * "Сделка как услуга" in CRM cards: access of client people in the deal and
 * company cards, the deal composition and the channels of a client person.
 * Registered only by the views that render these blocks.
 */
class ClientAccessAsset extends AssetBundle
{
    public $sourcePath = '@skeeks/cms/assets/admin/src';
    public $css = ['client-access.css'];
    public $depends = [CrmContactsAsset::class];
}
