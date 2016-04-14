<?php
/**
 * Module
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin;

use skeeks\cms\base\Module as CmsModule;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\Site;
use skeeks\cms\modules\admin\assets\AdminAsset;
use skeeks\cms\modules\admin\components\UrlRule;
use yii\base\Event;
use yii\base\Object;

/**
 * Class Module
 * @package skeeks\modules\cms\user
 */
class Module extends CmsModule
{
    //Скрывать кнопки действи сущьности
    const SYSTEM_QUERY_NO_ACTIONS_MODEL = 'no-actions';
    const SYSTEM_QUERY_EMPTY_LAYOUT     = 'sx-empty-layout';

    public $controllerNamespace = 'skeeks\cms\modules\admin\controllers';

    public $noImage = '';

    public function init()
    {
        parent::init();

        \Yii::setAlias('@admin/views', $this->viewPath);

        if (\Yii::$app->admin->requestIsAdmin)
        {
            if (!$this->noImage)
            {
                $this->noImage = AdminAsset::getAssetUrl("images/no-photo.gif");
                //$this->noImage = \Yii::$app->getAssetManager()->getAssetUrl(AdminAsset::register(\Yii::$app->view), "images/no-photo.gif");
            }

            /*\Yii::beginProfile('admin loading');

            //Загрузка всех компонентов.
            $components = \Yii::$app->getComponents();
            foreach ($components as $id => $data)
            {
                try
                {
                    \Yii::$app->get($id);
                } catch(\Exception $e)
                {
                    continue;
                }
            }

            \Yii::$app->trigger(self::EVENT_READY, new Event([
                'name' => self::EVENT_READY
            ]));

            \Yii::endProfile('admin loading');*/
        }
    }

    /**
     * @param array $data
     * @return string
     */
    public function createUrl(array $data)
    {
        $data[UrlRule::ADMIN_PARAM_NAME] = UrlRule::ADMIN_PARAM_VALUE;
        return \Yii::$app->urlManager->createUrl($data);
    }
}