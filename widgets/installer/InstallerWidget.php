<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 29.06.2015
 */
namespace skeeks\cms\widgets\installer;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\UrlHelper;
use yii\base\Widget;
use yii\helpers\Json;

/**
 * @property string $clientOptionsJson
 *
 * Class InstallerWidget
 * @package skeeks\cms\widgets\ssh
 */
class InstallerWidget extends Widget
{
    public function init()
    {
        parent::init();
    }

    public function run()
    {
        InstallerWidgetAsset::register($this->view);

        return $this->render('installer', [
            'widget' => $this,
        ]);
    }

    /**
     * @return string
     */
    public function getClientOptionsJson()
    {
        $canSsh = (int) \Yii::$app->user->can('admin/ssh');

        return Json::encode([
            'id'                        => $this->id,
            'canSsh'                    => $canSsh,
            'permissionsUpdateBackend'  => UrlHelper::construct('/admin/admin-permission/update-data')->enableAbsolute()->toString(),
        ]);
    }
}