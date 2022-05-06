<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendModelUpdateAction;
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\ViewBackendAction;
use skeeks\cms\backend\widgets\SelectModelDialogTreeWidget;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsAgent;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsTheme;
use skeeks\cms\shop\models\ShopContent;
use skeeks\cms\shop\models\ShopSite;
use skeeks\cms\widgets\formInputs\ckeditor\Ckeditor;
use skeeks\yii2\config\ConfigModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\web\Application;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsThemeController extends BackendController
{
    public function init()
    {
        $this->name = "Дизайн сайта";
        //$this->generateAccessActions = false;
        parent::init();
    }

    public function actions()
    {
        return [
            /*'index' => [
                'class' => ViewBackendAction::class,
            ],
            /*'update' => [
                'class' => ViewBackendAction::class,
            ],*/
        ];
    }

    public function actionIndex() {

        //Актуализировать шаблоны в базе данных
        $themes = \Yii::$app->view->availableThemes;
        $themeCodes = [];
        //Добавить шаблоны которых нет в базе данных
        foreach ($themes as $id => $themeData)
        {
            $themeCodes[$id] = $id;

            if (!CmsTheme::find()->cmsSite()->andWhere(['code' => $id])->one()) {
                $cmsTheme = new CmsTheme();
                $cmsTheme->code = $id;
                if (!$cmsTheme->save()) {
                    throw new Exception(print_r($cmsTheme->errors, true));
                }
            }
        }

        //Удалить шаблоны из базы, которые больше недоступны
        $themesForDelete = CmsTheme::find()->cmsSite()->andWhere(['not in', 'code', $themeCodes])->all();
        if ($themesForDelete) {
            foreach ($themesForDelete as $themesForDelete)
            {
                $themesForDelete->delete();
            }
        }

        //Проверить и назначить шаблон по умолчанию
        if (!CmsTheme::find()->cmsSite()->active()->one()) {
            //Если тема еще не выбрана, то нужно выбрать активную
            $cmsTheme = CmsTheme::find()->cmsSite()->andWhere(['code' => \Yii::$app->view->defaultThemeId])->one();
            if ($cmsTheme) {
                $cmsTheme->is_active = 1;
                $cmsTheme->update(true, ['is_active']);
            }
        }

        return $this->render($this->action->id);
    }


    public function actionEnable()
    {
        $code = trim(\Yii::$app->request->get("code"));
        /**
         * @var $cmsTheme CmsTheme
         */
        $cmsTheme = CmsTheme::find()->cmsSite()->andWhere(['code' => $code])->one();
        $cmsTheme->is_active = 1;
        if (!$cmsTheme->save()) {
            throw new Exception(print_r($cmsTheme->errors, true));
        }
        
        return $this->redirect(['update', 'code' => $code]);
        
    }
    
    
    public function actionUpdate()
    {
        $rr = new RequestResponse();

        $code = trim(\Yii::$app->request->get("code"));

        $themes = \Yii::$app->view->availableThemes;

        /**
         * @var $cmsTheme CmsTheme
         */
        $cmsTheme = CmsTheme::find()->cmsSite()->andWhere(['code' => $code])->one();

        $configModel = $cmsTheme->objectTheme->configFormModel;

        if ($rr->isRequestAjaxPost()) {
            try {
                if ($configModel->load(\Yii::$app->request->post()) && $configModel->validate()) {
                    $cmsTheme->config = $configModel->toArray();
                    if (!$cmsTheme->update(true, ['config'])) {
                        print_r($cmsTheme->errors, true);
                    }

                    $rr->message = "Настройки сохранены";
                    $rr->success = true;
                }
            } catch (\Exception $exception) {
                $rr->success = false;
                $rr->message = $exception->getMessage();
            }

            return $rr;
        }

        return $this->render($this->action->id, [
            'cmsTheme' => $cmsTheme,
            'configModel' => $configModel
        ]);
    }



    /**
     * Обновление настроек тем
     *
     * @return void
     */
    public function actionUpdateByUnify()
    {
        $setting1Default = CmsComponentSettings::find()
            ->andWhere(['like', 'component', 'UnifyThemeSettings'])
            ->andWhere(['cms_site_id' => null])
            ->one();

        $setting2Default = CmsComponentSettings::find()
            ->andWhere(['like', 'component', 'UnifyShopThemeSettingsComponent'])
            ->andWhere(['cms_site_id' => null])
            ->one();

        $defaultSettings = [];

        if ($setting1Default) {
            $defaultSettings = ArrayHelper::merge($defaultSettings, (array) $setting1Default->value);
        }
        if ($setting2Default) {
            $defaultSettings = ArrayHelper::merge($defaultSettings, (array) $setting2Default->value);
        }

        /*if (!$defaultSettings) {
            $this->stdout("Нет настроек!\n");
            return false;
        }*/

        /**
         * @var CmsSite $cmsSite
         */
        foreach (CmsSite::find()->each(10) as $cmsSite)
        {
            echo $cmsSite->id . "<br />";

            //Актуализировать шаблоны в базе данных
            $themes = \Yii::$app->view->getAvailableThemes($cmsSite);
            $themeCodes = [];
            //Добавить шаблоны которых нет в базе данных
            foreach ($themes as $id => $themeData)
            {
                $themeCodes[$id] = $id;

                if (!CmsTheme::find()->cmsSite($cmsSite)->andWhere(['code' => $id])->one()) {
                    $cmsTheme = new CmsTheme();
                    $cmsTheme->code = $id;
                    $cmsTheme->cms_site_id = $cmsSite->id;
                    if (!$cmsTheme->save()) {
                        throw new Exception(print_r($cmsTheme->errors, true));
                    }
                }
            }

            //Удалить шаблоны из базы, которые больше недоступны
            $themesForDelete = CmsTheme::find()->cmsSite($cmsSite)->andWhere(['not in', 'code', $themeCodes])->all();
            if ($themesForDelete) {
                foreach ($themesForDelete as $themesForDelete)
                {
                    $themesForDelete->delete();
                }
            }

            //Проверить и назначить шаблон по умолчанию
            if (!CmsTheme::find()->cmsSite($cmsSite)->active()->one()) {
                //Если тема еще не выбрана, то нужно выбрать активную
                $cmsTheme = CmsTheme::find()->cmsSite($cmsSite)->andWhere(['code' => \Yii::$app->view->defaultThemeId])->one();
                if ($cmsTheme) {
                    $cmsTheme->is_active = 1;
                    $cmsTheme->update(true, ['is_active']);
                }
            }


            //Далее нужно добавить настройки в активную тему
            /**
             * @var CmsTheme $cmsThemeActive
             */
            $cmsThemeActive = CmsTheme::find()->cmsSite($cmsSite)->active()->one();

            $siteSettings = $defaultSettings;

            $setting1 = CmsComponentSettings::find()
                ->andWhere(['like', 'component', 'UnifyThemeSettings'])
                ->andWhere(['cms_site_id' => $cmsSite->id])
                ->one();

            $setting2 = CmsComponentSettings::find()
                ->andWhere(['like', 'component', 'UnifyShopThemeSettingsComponent'])
                ->andWhere(['cms_site_id' => $cmsSite->id])
                ->one();

            if ($setting1) {
                $siteSettings = ArrayHelper::merge($siteSettings, (array) $setting1->value);
            }
            if ($setting2) {
                $siteSettings = ArrayHelper::merge($siteSettings, (array) $setting2->value);
            }

            if ($siteSettings) {
                
                $cmsThemeActive->config = $siteSettings;
                $cmsThemeActive->update(true, ['config']);
            }
        }

    }


}
