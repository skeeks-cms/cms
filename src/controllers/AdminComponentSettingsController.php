<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\THasActiveForm;
use skeeks\cms\backend\BackendController;
use skeeks\cms\base\Component;
use skeeks\cms\components\Cms;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsComponentSettings;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\User;
use skeeks\cms\modules\admin\controllers\AdminController;
use yii\base\ActionEvent;
use yii\base\Theme;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;

/**
 * @property array $callableData;
 *
 * Class AdminComponentSettingsController
 * @package skeeks\cms\controllers
 */
class AdminComponentSettingsController extends BackendController
{
    use THasActiveForm;

    public function init()
    {
        $this->name = "Управление настройками компонентов";
        $this->generateAccessActions = false;
        $this->permissionName = "cms/admin-settings";
        parent::init();
    }

    /**
     * @var Component
     */
    protected $_component = null;

    /**
     * @var array
     */
    protected $_callableData = [];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {

            $componentClassName = \Yii::$app->request->get('componentClassName');
            $namespace = \Yii::$app->request->get('componentNamespace');

            if ($namespace) {
                $component = new $componentClassName([
                    'namespace' => $namespace
                ]);
            } else {
                $component = new $componentClassName();
            }

            if (!$component || !$component instanceof Component) {
                throw new UserException("Указан некорректный компонент");
            }

            $this->_component = $component;
            $this->_callableData = $this->_getCallableData($component);

            //TODO: Добавить возможность настройки
            \Yii::$app->language = \Yii::$app->admin->languageCode;

            return true;
        } else {
            return false;
        }
    }

    /**
     * @return array
     */
    public function getCallableData()
    {
        return $this->_callableData;
    }

    /**
     * Сохранение значений компонента с которыми он был вызван в коде.
     * И далее отправка на страницу редактирования. Где эти значения предзагрузятся в форму.
     *
     * @return string
     */
    public function actionCallEdit()
    {
        $component = $this->_component;

        if (\Yii::$app->request->get('attributes') && !$settings = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->one()) {
            $attributes = \Yii::$app->request->get('attributes');
            $component->setAttributes($attributes);
        } else {
            $component->overridePath = [Component::OVERRIDE_DEFAULT];
            $component->refresh();
        }

        if (!\Yii::$app->request->get('callableId')) {
            return $this->redirect(\yii\helpers\Url::to('index') . "?" . http_build_query(\Yii::$app->request->get()));
        }


        return $this->render($this->action->id, [
            'component' => $component,
            'callableId' => \Yii::$app->request->get('callableId'),
        ]);
    }

    public function actionSaveCallable()
    {
        $rr = new RequestResponse();

        //Callable дата (с этими настройками разработчик вызвал этот компонент в коде + еще какие то настройки окружения)
        if ($data = \Yii::$app->request->post('data')) {
            $component = $this->_component;
            $this->_saveCallableData($component, unserialize(base64_decode($data)));
        }

        return $rr;
    }

    /**
     * @return array|string
     */
    public function actionIndex()
    {
        /**
         * @var Component $component
         */
        $component = $this->_component;

        $attibutes = (array)\Yii::$app->request->get('attributes');

        if ($attributesCallable = ArrayHelper::getValue($this->_callableData, 'attributes')) {
            $attibutes = ArrayHelper::merge($attibutes, $attributesCallable);
        }

        if ($attibutes && !\skeeks\cms\models\CmsComponentSettings::findByComponent($component)->one()) {
            $attributes = $attibutes;
            $component->setAttributes($attributes);
        } else {
            $component->overridePath = [Component::OVERRIDE_DEFAULT];
            $component->refresh();
        }



        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax) {
            $component->load(\Yii::$app->request->post());
            return ActiveForm::validateMultiple(ArrayHelper::merge(
                [$component], $component->getConfigFormModels()
            ));
        }

        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax) {

            if (!\Yii::$app->request->post($this->reloadFormParam)) {
                if ($component->load(\Yii::$app->request->post()) && $component->validate()) {
                    $component->override = Component::OVERRIDE_DEFAULT;
                    if ($component->save()) {
                        \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                    } else {
                        \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                    }

                } else {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }
            } else {
                $component->load(\Yii::$app->request->post());
            }

        }

        return $this->render($this->action->id, [
            'component' => $component
        ]);
    }

    public function actionSite()
    {
        /**
         * @var Component $component
         */
        $component = $this->_component;

        $site_id = \Yii::$app->request->get('site_id');
        if (!$site_id) {
            throw new UserException("Не передан параметр site_id");
        }

        $site = CmsSite::findOne($site_id);
        if (!$site) {
            throw new UserException("Не найден сайт");
        }

        $component->overridePath = [Component::OVERRIDE_DEFAULT, Component::OVERRIDE_SITE];
        $component->cmsSite = $site;
        $component->refresh();


        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax) {
            return $rr->ajaxValidateForm($component);
        }


        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax) {
            if (!\Yii::$app->request->post($this->reloadFormParam)) {
                if ($component->load(\Yii::$app->request->post()) && $component->validate()) {
                    $component->override = Component::OVERRIDE_SITE;
                    $component->cmsSite = $site;
                    if ($component->save()) {
                        \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                    } else {
                        \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                    }

                } else {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }
            } else {
                $component->load(\Yii::$app->request->post());
            }
        }


        return $this->render($this->action->id, [
            'component' => $component,
            'site' => $site
        ]);
    }

    public function actionUser()
    {
        $component = $this->_component;

        $user_id = \Yii::$app->request->get('user_id');
        if (!$user_id) {
            throw new UserException("Не передан параметр user_id");
        }

        $user = User::findOne($user_id);
        if (!$user) {
            throw new UserException("Не найден пользователь");
        }

        $component->overridePath = [Component::OVERRIDE_DEFAULT, Component::OVERRIDE_USER];
        $component->cmsUser = $user;
        $component->refresh();

        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax) {
            return $rr->ajaxValidateForm($component);
        }

        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax) {
            if (!\Yii::$app->request->post($this->reloadFormParam)) {
                if ($component->load(\Yii::$app->request->post()) && $component->validate()) {
                    $component->override = Component::OVERRIDE_USER;
                    $component->cmsUser = $user;
                    if ($component->save()) {
                        \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                    } else {
                        \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                    }

                } else {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }
            } else {
                $component->load(\Yii::$app->request->post());
            }
        }

        return $this->render($this->action->id, [
            'component' => $component,
            'user' => $user
        ]);
    }

    public function actionUsers()
    {
        $component = $this->_component;

        return $this->render($this->action->id, [
            'component' => $component
        ]);
    }

    public function actionSites()
    {
        $component = $this->_component;

        return $this->render($this->action->id, [
            'component' => $component
        ]);
    }

    public function actionCache()
    {
        $component = $this->_component;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            $component->invalidateCache();
            $rr->message = 'Кэш успешно очещен';
            $rr->success = true;
            return (array)$rr;
        }

        return $this->render($this->action->id, [
            'component' => $component
        ]);
    }

    public function actionRemove()
    {
        $component = $this->_component;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost()) {
            if (\Yii::$app->request->post('do') == 'all') {
                if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->all()) {
                    /**
                     * @var $setting CmsComponentSettings
                     */
                    foreach ($settings as $setting) {
                        //TODO: добавить отладочную информацию.
                        if ($setting->delete()) {
                        }
                    }

                    $component->invalidateCache();
                    $rr->message = 'Настройки успешно удалены';
                    $rr->success = true;
                };
            } else {
                if (\Yii::$app->request->post('do') == 'default') {
                    if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->one()) {
                        $settings->delete();
                        $component->invalidateCache();
                        $rr->message = 'Настройки успешно удалены';
                        $rr->success = true;
                    };
                } else {
                    if (\Yii::$app->request->post('do') == 'sites') {
                        if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->andWhere([
                            '>',
                            'cms_site_id',
                            0
                        ])->all()) {
                            /**
                             * @var $setting CmsComponentSettings
                             */
                            foreach ($settings as $setting) {
                                //TODO: добавить отладочную информацию.
                                if ($setting->delete()) {
                                }
                            }

                            $component->invalidateCache();
                            $rr->message = 'Настройки успешно удалены';
                            $rr->success = true;
                        };
                    } else {
                        if (\Yii::$app->request->post('do') == 'users') {
                            if ($settings = \skeeks\cms\models\CmsComponentSettings::findByComponent($component)->andWhere([
                                '>',
                                'user_id',
                                0
                            ])->all()) {
                                /**
                                 * @var $setting CmsComponentSettings
                                 */
                                foreach ($settings as $setting) {
                                    //TODO: добавить отладочную информацию.
                                    if ($setting->delete()) {
                                    }
                                }

                                $component->invalidateCache();
                                $rr->message = 'Настройки успешно удалены';
                                $rr->success = true;
                            };
                        } else {
                            if (\Yii::$app->request->post('do') == 'site') {
                                $code = \Yii::$app->request->post('code');
                                $site = CmsSite::find()->where(['code' => $code])->one();

                                if ($site) {
                                    $component->setOverride(Component::OVERRIDE_SITE)->setCmsSite($site);
                                    if ($component->delete()) {
                                        $rr->message = 'Настройки успешно удалены';
                                        $rr->success = true;
                                    };
                                }

                            } else {
                                if (\Yii::$app->request->post('do') == 'user') {
                                    $id = \Yii::$app->request->post('id');
                                    $user = User::find()->where(['id' => $id])->one();

                                    if ($user) {
                                        $component->setOverride(Component::OVERRIDE_USER)->setCmsUser($user);
                                        if ($component->delete()) {
                                            $rr->message = 'Настройки успешно удалены';
                                            $rr->success = true;
                                        };
                                    }

                                } else {
                                    $rr->message = 'Все настройки удалены';
                                    $rr->success = true;
                                }
                            }
                        }
                    }
                }
            }

            return (array)$rr;
        }

        return $this->render($this->action->id, [
            'component' => $component
        ]);
    }


    /**
     * @param Component $component
     * @param array $data
     */
    protected function _saveCallableData($component, $data = [])
    {
        //TODO: переписать без использования кэша, вдруг он вообще отключен, переполнен или еще чего
        $key = md5($component::className() . $component->namespace);
        \Yii::$app->cache->set($key, $data);
    }

    /**
     * @param Component $component
     * @param array $data
     */
    protected function _getCallableData($component)
    {
        $key = md5($component::className() . $component->namespace);
        return (array)\Yii::$app->cache->get($key);
    }
}
