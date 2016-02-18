<?php
/**
 * Admin
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */
namespace skeeks\cms\modules\admin\controllers;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\helpers\UrlHelper;
use skeeks\cms\models\CmsDashboard;
use skeeks\cms\models\CmsDashboardWidget;
use skeeks\cms\modules\admin\actions\AdminAction;
use skeeks\cms\rbac\CmsManager;
use yii\base\Exception;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Class IndexController
 * @package skeeks\cms\modules\admin\controllers
 */
class IndexController extends AdminController
{
    public function init()
    {
        $this->name = \Yii::t('app', "Desktop");
        parent::init();
    }

    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'indexverbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'dashboard-save' => ['post', 'get'],
                ],
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getPermissionName()
    {
        return CmsManager::PERMISSION_ADMIN_ACCESS;
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $dashboard = null;
        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            $dashboard = CmsDashboard::find()->orderBy(['priority' => SORT_ASC])->one();

            if (!$dashboard)
            {
                $dashboard = new CmsDashboard();
                $dashboard->name = 'Стол по умолчанию';

                if (!$dashboard->save())
                {
                    throw new NotFoundHttpException("Рабочий стол не найден");
                }
            }
        }


        return $this->redirect(
            UrlHelper::construct(['/admin/index/dashboard', 'pk' => $dashboard->id])->enableAdmin()->toString()
        );
    }

    public function actionDashboard()
    {
        $dashboard = null;
        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            throw new NotFoundHttpException("Рабочий стол не найден");
        }

        $this->layout = '@admin/views/layouts/main-empty';

        return $this->render($this->action->id, [
            'dashboard' => $dashboard
        ]);
    }

    public function actionDashboardValidate()
    {
        $rr = new RequestResponse();
        $dashboard = null;

        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            $rr->message = "Рабочий стол не найден";
            $rr->success = false;
        }

        if ($rr->isRequestAjaxPost())
        {
            return $rr->ajaxValidateForm($dashboard);
        }

        return $rr;
    }


    public function actionDashboardRemove()
    {
        $rr = new RequestResponse();
        $rr->success = false;
        /**
         * @var $dashboard CmsDashboard
         */
        $dashboard = null;

        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            $rr->message = "Рабочий стол не найден";
            $rr->success = false;
        }

        try
        {
            $dashboard->delete();
            $rr->redirect   = UrlHelper::construct(['/admin/index'])->enableAdmin()->toString();
            $rr->success    = true;

        } catch (\Exception $e)
        {
            $rr->message = $e->getMessage();
            $rr->success = false;
        }

        return $rr;
    }

    public function actionDashboardSave()
    {
        $rr = new RequestResponse();
        $rr->success = false;

        /**
         * @var $dashboard CmsDashboard
         */
        $dashboard = null;

        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            $rr->message = "Рабочий стол не найден";
            $rr->success = false;
        }

        if ($rr->isRequestAjaxPost())
        {
            if ($dashboard->load(\Yii::$app->request->post()) && $dashboard->save())
            {
                $rr->success = true;
                $rr->message = 'Сохранено';
            } else
            {

                $rr->message = 'Не сохранено';
            }
        }

        return $rr;
    }


    public function actionDashboardWidgetCreateValidate()
    {
        $rr = new RequestResponse();
        $dashboardWidget = new CmsDashboardWidget();

        if ($rr->isRequestAjaxPost())
        {
            return $rr->ajaxValidateForm($dashboardWidget);
        }

        return $rr;
    }

    public function actionDashboardWidgetCreateSave()
    {
        $rr = new RequestResponse();
        $dashboardWidget = new CmsDashboardWidget();

        if ($rr->isRequestAjaxPost())
        {
            if ($dashboardWidget->load(\Yii::$app->request->post()) && $dashboardWidget->save())
            {
                $rr->success = true;
                $rr->message = 'Сохранено';
            } else
            {

                $rr->message = 'Не сохранено';
            }
        }

        return $rr;
    }


    public function actionDashboardCreateValidate()
    {
        $rr = new RequestResponse();
        $dashboard = new CmsDashboard();

        if ($rr->isRequestAjaxPost())
        {
            return $rr->ajaxValidateForm($dashboard);
        }

        return $rr;
    }


    public function actionDashboardCreateSave()
    {
        $rr = new RequestResponse();
        $dashboard = new CmsDashboard();

        if ($rr->isRequestAjaxPost())
        {
            if ($dashboard->load(\Yii::$app->request->post()) && $dashboard->save())
            {
                $rr->success = true;
                $rr->message = 'Сохранено';
                $rr->redirect = UrlHelper::construct(['/admin/index/dashboard', 'pk' => $dashboard->id])->enableAdmin()->toString();
            } else
            {

                $rr->message = 'Не сохранено';
            }
        }

        return $rr;
    }


    public function actionWidgetRemove()
    {
        $rr = new RequestResponse();
        $rr->success = false;

        /**
         * @var $dashboardWidget CmsDashboardWidget
         */
        $dashboardWidget = null;

        if ($pk = \Yii::$app->request->post('id'))
        {
            $dashboardWidget = CmsDashboardWidget::findOne($pk);
        }

        if (!$dashboardWidget)
        {
            $rr->message = "Виджет не найден";
            $rr->success = false;
        }

        if ($dashboardWidget->delete())
        {
            $rr->success = true;
        }

        return $rr;
    }


    public function actionWidgetPrioritySave()
    {
        $rr = new RequestResponse();
        $rr->success = false;

        /**
         * @var $dashboard CmsDashboard
         */
        $dashboard = null;

        if ($pk = \Yii::$app->request->get('pk'))
        {
            $dashboard = CmsDashboard::findOne($pk);
        }

        if (!$dashboard)
        {
            $rr->message = "Рабочий стол не найден";
            $rr->success = false;
        }

        $widgets = $dashboard->cmsDashboardWidgets;
        $widgets = ArrayHelper::map($dashboard->cmsDashboardWidgets, 'id', function($model)
        {
            return $model;
        });

        if ($rr->isRequestAjaxPost())
        {
            if ($data = \Yii::$app->request->post())
            {
                foreach ($data as $columnId => $widgetIds)
                {
                    //Обновляем приоритеты виджетов в этой колонке
                    if ($widgetIds)
                    {
                        $priority = 100;
                        foreach ($widgetIds as $widgetId)
                        {
                            if (isset($widgets[$widgetId]))
                            {
                                /**
                                 * @var $widget CmsDashboardWidget
                                 */
                                $widget = $widgets[$widgetId];
                                $widget->cms_dashboard_column = $columnId;
                                $widget->priority = $priority;
                                $widget->save();

                                $priority = $priority + 100;

                                unset($widgets[$widgetId]);
                            }
                        }
                    }
                }

                //еще остались виджеты, суем их в конец
                if ($widgets)
                {
                    foreach ($widgets as $widget)
                    {
                        $widget->cms_dashboard_column   = $columnId;
                        $widget->priority               = $priority;
                        $widget->save();

                        $priority = $priority + 100;
                    }
                }
            }
        }

        $rr->success = true;

        return $rr;
    }
}