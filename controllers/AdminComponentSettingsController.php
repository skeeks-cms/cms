<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */
namespace skeeks\cms\controllers;
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
class AdminComponentSettingsController extends AdminController
{
    /**
     * @return string
     */
    public function getPermissionName()
    {
        return "cms/admin-settings";
    }

    public function init()
    {
        $this->name                   = "Управление настройками компонентов";
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

            $componentClassName         = \Yii::$app->request->get('componentClassName');
            $namespace                  = \Yii::$app->request->get('componentNamespace');

            if ($namespace)
            {
                $component                  = new $componentClassName([
                    'namespace' => $namespace
                ]);
            } else
            {
                $component                  = new $componentClassName();
            }

            if (!$component || !$component instanceof Component)
            {
                throw new UserException("Указан некорректный компонент");
            }

            $this->_component       = $component;
            $this->_callableData    = $this->_getCallableData($component);

            //TODO: Добавить возможность настройки
            \Yii::$app->view->theme = new Theme([
                'pathMap' =>
                [
                    '@app/views' =>
                    [
                        '@skeeks/cms/modules/admin/views',
                    ]
                ]
            ]);

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

        if (\Yii::$app->request->get('attributes') && !$settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentDefault($component))
        {
            $attributes                 = \Yii::$app->request->get('attributes');
            $component->attributes      = $attributes;
        } else
        {
            $component->loadDefaultSettings();
        }

        if (!\Yii::$app->request->get('callableId'))
        {
            return $this->redirect(\yii\helpers\Url::to('index') . "?" . http_build_query(\Yii::$app->request->get()));
        }


        return $this->render($this->action->id, [
            'component'         => $component,
            'callableId'         => \Yii::$app->request->get('callableId'),
        ]);
    }

    public function actionSaveCallable()
    {
        $rr = new RequestResponse();

        //Callable дата (с этими настройками разработчик вызвал этот компонент в коде + еще какие то настройки окружения)
        if ($data = \Yii::$app->request->post('data'))
        {
            $component = $this->_component;
            $this->_saveCallableData($component, unserialize(base64_decode($data)));
        }

        return $rr;
    }

    public function actionIndex()
    {
        $component = $this->_component;

        $attibutes = (array) \Yii::$app->request->get('attributes');

        if ($attributesCallable = ArrayHelper::getValue($this->_callableData, 'attributes'))
        {
            $attibutes = ArrayHelper::merge($attibutes, $attributesCallable);
        }

        if ($attibutes && !\skeeks\cms\models\CmsComponentSettings::fetchByComponentDefault($component))
        {
            $attributes                 = $attibutes;
            $component->attributes      = $attributes;
        } else
        {
            $component->loadDefaultSettings();
        }


        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($component);
        }

        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax)
        {
            if ($component->load(\Yii::$app->request->post()) && $component->validate())
            {
                if ($component->saveDefaultSettings())
                {
                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                } else
                {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }

        return $this->render($this->action->id, [
            'component'         => $component
        ]);
    }

    public function actionSite()
    {
        $component = $this->_component;

        $site_id = \Yii::$app->request->get('site_id');
        if (!$site_id)
        {
            throw new UserException("Не передан параметр site_id");
        }

        $site = CmsSite::findOne($site_id);
        if (!$site)
        {
            throw new UserException("Не найден сайт");
        }

        $component->loadSettingsBySite($site);


        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($component);
        }


        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax)
        {
            if ($component->load(\Yii::$app->request->post()) && $component->validate())
            {
                if ($component->saveDefaultSettingsBySiteCode($site->code))
                {
                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                } else
                {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }


        return $this->render($this->action->id, [
            'component'         => $component,
            'site'              => $site
        ]);
    }

    public function actionUser()
    {
        $component = $this->_component;

        $user_id = \Yii::$app->request->get('user_id');
        if (!$user_id)
        {
            throw new UserException("Не передан параметр user_id");
        }

        $user = User::findOne($user_id);
        if (!$user)
        {
            throw new UserException("Не найден пользователь");
        }

        $component->loadSettingsByUser($user);

        $rr = new RequestResponse();
        if (\Yii::$app->request->isAjax && \Yii::$app->request->isPost && !\Yii::$app->request->isPjax)
        {
            return $rr->ajaxValidateForm($component);
        }

        if (\Yii::$app->request->isPost && \Yii::$app->request->isPjax)
        {
            if ($component->load(\Yii::$app->request->post()) && $component->validate())
            {
                if ($component->saveDefaultSettingsByUserId($user->id))
                {
                    \Yii::$app->getSession()->setFlash('success', 'Успешно сохранено');
                } else
                {
                    \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
                }

            } else
            {
                \Yii::$app->getSession()->setFlash('error', 'Не удалось сохранить');
            }
        }

        return $this->render($this->action->id, [
            'component'         => $component,
            'user'              => $user
        ]);
    }

    public function actionUsers()
    {
        $component = $this->_component;

        return $this->render($this->action->id, [
            'component'         => $component
        ]);
    }

    public function actionSites()
    {
        $component = $this->_component;

        return $this->render($this->action->id, [
            'component'         => $component
        ]);
    }

    public function actionCache()
    {
        $component = $this->_component;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            $component->invalidateCache();
            $rr->message = 'Кэш успешно очещен';
            $rr->success = true;
            return (array) $rr;
        }

        return $this->render($this->action->id, [
            'component'         => $component
        ]);
    }

    public function actionRemove()
    {
        $component = $this->_component;

        $rr = new RequestResponse();

        if ($rr->isRequestAjaxPost())
        {
            if (\Yii::$app->request->post('do') == 'all')
            {
                if ($settings = \skeeks\cms\models\CmsComponentSettings::baseQuery($component)->all())
                {
                    /**
                     * @var $setting CmsComponentSettings
                     */
                    foreach ($settings as $setting)
                    {
                        //TODO: добавить отладочную информацию.
                        if ($setting->delete())
                        {}
                    }

                    $component->invalidateCache();
                    $rr->message = 'Настройки успешно удалены';
                    $rr->success = true;
                };
            } else if (\Yii::$app->request->post('do') == 'default')
            {
                if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentDefault($component))
                {
                    $settings->delete();
                    $component->invalidateCache();
                    $rr->message = 'Настройки успешно удалены';
                    $rr->success = true;
                };
            } else if (\Yii::$app->request->post('do') == 'sites')
            {
                if ($settings = \skeeks\cms\models\CmsComponentSettings::baseQuerySites($component)->all())
                {
                    /**
                     * @var $setting CmsComponentSettings
                     */
                    foreach ($settings as $setting)
                    {
                        //TODO: добавить отладочную информацию.
                        if ($setting->delete())
                        {}
                    }

                    $component->invalidateCache();
                    $rr->message = 'Настройки успешно удалены';
                    $rr->success = true;
                };
            } else if (\Yii::$app->request->post('do') == 'users')
            {
                if ($settings = \skeeks\cms\models\CmsComponentSettings::baseQueryUsers($component)->all())
                {
                    /**
                     * @var $setting CmsComponentSettings
                     */
                    foreach ($settings as $setting)
                    {
                        //TODO: добавить отладочную информацию.
                        if ($setting->delete())
                        {}
                    }

                    $component->invalidateCache();
                    $rr->message = 'Настройки успешно удалены';
                    $rr->success = true;
                };
            }

            else if (\Yii::$app->request->post('do') == 'site')
            {
                $code = \Yii::$app->request->post('code');
                $site = CmsSite::find()->where(['code' => $code])->one();

                if ($site)
                {
                    if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentSite($component, $site))
                    {
                        $settings->delete();
                        $component->invalidateCache();
                        $rr->message = 'Настройки успешно удалены';
                        $rr->success = true;
                    };
                }

            }

            else if (\Yii::$app->request->post('do') == 'user')
            {
                $id = \Yii::$app->request->post('id');
                $user = User::find()->where(['id' => $id])->one();

                if ($user)
                {
                    if ($settings = \skeeks\cms\models\CmsComponentSettings::fetchByComponentUser($component, $user))
                    {
                        $settings->delete();
                        $component->invalidateCache();
                        $rr->message = 'Настройки успешно удалены';
                        $rr->success = true;
                    };
                }

            }

            else
            {
                $rr->message = 'Все настройки удалены';
                $rr->success = true;
            }

            return (array) $rr;
        }

        return $this->render($this->action->id, [
            'component'         => $component
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
        return (array) \Yii::$app->cache->get($key);
    }
}
