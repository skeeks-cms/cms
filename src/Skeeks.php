<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms;

use skeeks\cms\backend\BackendComponent;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompanyAddress;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsProject;
use skeeks\cms\models\CmsSite;
use skeeks\cms\models\CmsSiteDomain;
use skeeks\cms\models\CmsTask;
use skeeks\cms\models\CmsTree;
use skeeks\cms\models\CmsUser;
use skeeks\cms\models\CmsUserAddress;
use skeeks\cms\models\CmsUserEmail;
use skeeks\cms\models\CmsUserPhone;
use skeeks\cms\models\CmsWebNotify;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopBonusTransaction;
use skeeks\cms\shop\models\ShopCmsContentElement;
use skeeks\cms\shop\models\ShopPayment;
use skeeks\modules\cms\form2\cmsWidgets\form2\FormWidget;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\console\Application;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\View;

/**
 * @property array   $logTypes
 * @property CmsSite $site
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class Skeeks extends Component implements BootstrapInterface
{
    /**
     * @var CmsSite
     */
    protected $_site = null;

    /**
     * @var string
     */
    public $siteClass = CmsSite::class;

    /**
     * @var array
     */
    public $_logTypes = [];

    /**
     * @var string[]
     */
    public $defaultLogTypes = [
        CmsLog::LOG_TYPE_COMMENT => 'Комментарий',

        CmsLog::LOG_TYPE_DELETE => 'Удаление',
        CmsLog::LOG_TYPE_UPDATE => 'Обновление',
        CmsLog::LOG_TYPE_INSERT => 'Создание',
    ];

    public function bootstrap($application)
    {
        Event::on(View::class, View::EVENT_AFTER_RENDER, function (Event $event) {

            /*<!--форма обратная связь-->
            [sx]w=skeeks-modules-cms-form2-cmsWidgets-form2-FormWidget&form_id=2[/sx]*/

            if (BackendComponent::getCurrent()) {
                return false;
            }

            $content = $event->output;

            if (strpos($content, "[sx]") !== false) {

                $search = array(
                        '/\[sx\](.*?)\[\/sx\]/is',
                        );

                $replace = array(
                        '<strong>$1</strong>',
                        );

                /*$content = preg_replace ($search, $replace, $content);*/

                $content = preg_replace_callback ($search, function ($matches) {
                    $string = $matches[0];
                    $string = str_replace("[sx]", "", $string);
                    $string = str_replace("[/sx]", "", $string);
                    $string = html_entity_decode($string);
                    $data = [];
                    parse_str($string, $data);
                    
                    $wClass = ArrayHelper::getValue($data, "w");
                    if ($wClass == 'form') {
                        $wClass = FormWidget::class;
                    }
                    
                    if (!isset($data['namespace'])) {
                        $data['namespace'] = md5($string);
                    }
                    
                    if ($wClass) {
                        $wClass = "\\" . $wClass;
                        unset($data['w']);
                        $wClass = str_replace("-", '\\', $wClass);
                        if (class_exists($wClass)) {
                            return $wClass::widget($data);
                        } else {
                            return $wClass;
                        }

                    }
                    return "";

                }, $content);

                $event->output = $content;

            }


        });
        //Уведомления для пользователей
        Event::on(CmsLog::class, BaseActiveRecord::EVENT_AFTER_INSERT, function (Event $event) {
            /**
             * @var $sender CmsLog
             */
            $sender = $event->sender;

            //Поставлена задача
            if ($sender->log_type == CmsLog::LOG_TYPE_INSERT) {
                if ($sender->model_code == CmsTask::class) {
                    /**
                     * @var $model CmsTask
                     */
                    $model = $sender->model;
                    if ($model->executor_id && $model->executor_id != $model->created_by) {
                        $notify = new CmsWebNotify();
                        $notify->cms_user_id = $model->executor_id;
                        $notify->name = "Вам поставлена новая задача";
                        $notify->model_id = $sender->model_id;
                        $notify->model_code = $sender->model_code;
                        $notify->save();
                    }
                }
            }

            if ($sender->log_type == CmsLog::LOG_TYPE_UPDATE) {
                if ($sender->model_code == CmsTask::class) {
                    /**
                     * @var $model CmsTask
                     */
                    $model = $sender->model;
                    //Только если не сам себе ставил задачу
                    if ($model->executor_id != $model->created_by) {

                        $executor = ArrayHelper::getValue($sender->data, "executor_id.value");
                        $status = ArrayHelper::getValue($sender->data, "status.value");
                        $oldStatus = ArrayHelper::getValue($sender->data, "status.old_value");

                        //У задачи поменялся исполнитель
                        if ($executor) {
                            $notify = new CmsWebNotify();
                            $notify->cms_user_id = $executor;
                            $notify->name = "Вам передали задачу";
                            $notify->model_id = $sender->model_id;
                            $notify->model_code = $sender->model_code;
                            $notify->save();
                        }
                        //У задачи поменялся статус
                        if ($status) {
                            if ($status == CmsTask::STATUS_ON_CHECK) {
                                $notify = new CmsWebNotify();
                                $notify->cms_user_id = $model->created_by;
                                $notify->name = "Вам необходимо проверить задачу";
                                $notify->model_id = $sender->model_id;
                                $notify->model_code = $sender->model_code;
                                $notify->save();
                            }

                            /*if ($status == CmsTask::STATUS_READY) {
                                $notify = new CmsWebNotify();
                                $notify->cms_user_id = $model->created_by;
                                $notify->name = "Ваша задача проверена";
                                $notify->model_id = $sender->model_id;
                                $notify->model_code = $sender->model_code;
                                $notify->save();
                            }*/

                            if ($status == CmsTask::STATUS_CANCELED) {
                                $notify = new CmsWebNotify();
                                $notify->cms_user_id = $model->created_by;
                                $notify->name = "Задача отменена";
                                $notify->model_id = $sender->model_id;
                                $notify->model_code = $sender->model_code;
                                $notify->save();

                                $notify = new CmsWebNotify();
                                $notify->cms_user_id = $model->executor_id;
                                $notify->name = "Задача отменена";
                                $notify->model_id = $sender->model_id;
                                $notify->model_code = $sender->model_code;
                                $notify->save();
                            }

                            if ($status == CmsTask::STATUS_IN_WORK && $oldStatus == CmsTask::STATUS_ON_CHECK) {
                                $notify = new CmsWebNotify();
                                $notify->cms_user_id = $model->executor_id;
                                $notify->name = "Задача возобновлена";
                                $notify->model_id = $sender->model_id;
                                $notify->model_code = $sender->model_code;
                                $notify->save();
                            }

                        }

                    }
                }
            }


            //Добавлен комментарий
            if ($sender->log_type == CmsLog::LOG_TYPE_COMMENT) {
                //Комментарий к задаче
                if ($sender->model_code == CmsTask::class) {
                    /**
                     * @var $model CmsTask
                     */
                    $model = $sender->model;

                    $notify = new CmsWebNotify();

                    $notify->cms_user_id = $model->executor_id;
                    $notify->name = "Добавлен новый комментарий к задаче";
                    $notify->model_id = $sender->model_id;
                    $notify->model_code = $sender->model_code;

                    $notify2 = clone $notify;

                    $user_ids = [];

                    if ($model->executor_id) {
                        $user_ids[] = $model->executor_id;
                    }

                    if ($model->created_by) {
                        $user_ids[] = $model->created_by;
                    }

                    $user_ids = array_unique($user_ids);
                    if ($user_ids) {
                        foreach ($user_ids as $id)
                        {
                            if ($id != \Yii::$app->user->id) {
                                $notifyTmp = clone $notify;
                                $notifyTmp->cms_user_id = $id;
                                $notifyTmp->save();
                            }
                        }
                    }


                }
            }
        });
    }


    public $modelsConfig = [
        CmsTree::class           => [
            'name'       => 'Разделы',
            'name_one'   => 'Раздел',
            'controller' => 'cms/admin-tree',
        ],




        CmsCompany::class        => [
            'name'       => 'Компании',
            'name_one'   => 'Компания',
            'controller' => 'cms/admin-cms-company',
        ],
        CmsCompanyEmail::class   => [
            'name'       => 'Email-ы компаний',
            'name_one'   => 'Email компании',
            'controller' => 'cms/admin-cms-company-email',
        ],
        CmsCompanyPhone::class   => [
            'name'       => 'Телефоны компаний',
            'name_one'   => 'Телефон компании',
            'controller' => 'cms/admin-cms-company-phone',
        ],
        CmsCompanyAddress::class => [
            'name'       => 'Адреса компаний',
            'name_one'   => 'Адрес компании',
            'controller' => 'cms/admin-cms-company-address',
        ],
        CmsCompanyLink::class    => [
            'name'       => 'Ссылки компаний',
            'name_one'   => 'Ссылка компании',
            'controller' => 'cms/admin-cms-company-link',
        ],


        CmsUser::class        => [
            'name'       => 'Пользователи',
            'name_one'   => 'Пользователь',
            'controller' => 'cms/admin-user',
        ],
        CmsUserEmail::class   => [
            'name'       => 'Email-ы клиентов',
            'name_one'   => 'Email клиента',
            'controller' => 'cms/admin-user-email',
        ],
        CmsUserPhone::class   => [
            'name'       => 'Телефоны клиентов',
            'name_one'   => 'Телефон клиента',
            'controller' => 'cms/admin-user-phone',
        ],
        CmsUserAddress::class => [
            'name'       => 'Адреса клиентов',
            'name_one'   => 'Адрес клиента',
            'controller' => 'cms/admin-user-address',
        ],




        CmsDeal::class           => [
            'name'       => 'Сделки',
            'name_one'   => 'Сделка',
            'controller' => 'cms/admin-cms-deal',
        ],
        CmsProject::class        => [
            'name'       => 'Проекты',
            'name_one'   => 'Проект',
            'controller' => 'cms/admin-cms-project',
        ],
        CmsTask::class        => [
            'name'       => 'Задачи',
            'name_one'   => 'Задача',
            'controller' => 'cms/admin-cms-task',
        ],

        ShopBill::class        => [
            'name'       => 'Счета',
            'name_one'   => 'Счет',
            'controller' => 'cms/admin-cms-bill',
        ],
        ShopPayment::class        => [
            'name'       => 'Платежи',
            'name_one'   => 'Платеж',
            'controller' => 'shop/admin-payment',
        ],
        ShopBonusTransaction::class        => [
            'name'       => 'Бонусы',
            'name_one'   => 'Бонус',
            'controller' => 'shop/admin-bonus-transaction',
        ],


        CmsContentElement::class        => [
            'name'       => 'Контент',
            'name_one'   => 'Контент',
            'controller' => 'cms/admin-cms-content-element',
        ],
        ShopCmsContentElement::class        => [
            'name'       => 'Товары',
            'name_one'   => 'Товар',
            'controller' => 'shop/admin-cms-content-element',
        ],
    ];

    /**
     * @return array
     */
    public function setLogTypes(array $logTypes)
    {
        $this->_logTypes = ArrayHelper::merge((array)$this->_logTypes, (array)$logTypes);
        return $this;
    }

    /**
     * @return array
     */
    public function getLogTypes()
    {
        return ArrayHelper::merge((array)$this->defaultLogTypes, (array)$this->_logTypes);
    }

    /**
     * @var null
     */
    private $_serverName = null;

    /**
     * @return CmsSite
     */
    public function getSite()
    {
        $cmsSiteClass = $this->siteClass;

        if ($this->_site === null) {
            if (\Yii::$app instanceof \yii\console\Application) {

                if ($cms_site_id = getenv("CMS_SITE")) {
                    $this->_site = $cmsSiteClass::find()->active()->andWhere(['id' => $cms_site_id])->one();
                } else {

                    $table = $cmsSiteClass::safeGetTableSchema();
                    if ($table) {
                        if ($table->getColumn('is_default')) {
                            $this->_site = $cmsSiteClass::find()->active()->andWhere(['is_default' => 1])->one();
                        } else {
                            $this->_site = $cmsSiteClass::find()->active()->one();
                        }
                    }
                }


            } else {
                $this->_serverName = \Yii::$app->getRequest()->getServerName();
                $dependencySiteDomain = new TagDependency([
                    'tags' => [
                        (new CmsSiteDomain())->getTableCacheTag(),
                    ],
                ]);


                $cmsDomain = CmsSiteDomain::getDb()->cache(function ($db) {
                    return CmsSiteDomain::find()->where(['domain' => $this->_serverName])->one();
                }, null, $dependencySiteDomain);

                /**
                 * @var CmsSiteDomain $cmsDomain
                 */
                if ($cmsDomain) {
                    //$this->_site = $cmsDomain->cmsSite;

                    $this->_site = CmsSiteDomain::getDb()->cache(function ($db) use ($cmsSiteClass, $cmsDomain) {
                        return $cmsSiteClass::find()->andWhere(['id' => $cmsDomain->cms_site_id])->limit(1)->one();
                    },
                        null,
                        new TagDependency([
                            'tags' => [
                                (new CmsSiteDomain())->getTableCacheTagCmsSite($cmsDomain->cms_site_id),
                                (new $cmsSiteClass())->getTableCacheTag(),
                            ],
                        ])
                    );

                } else {

                    $this->_site = CmsSiteDomain::getDb()->cache(function ($db) use ($cmsSiteClass) {
                        return $cmsSiteClass::find()->active()->andWhere(['is_default' => 1])->one();
                    },
                        null,
                        new TagDependency([
                            'tags' => [
                                (new $cmsSiteClass())->getTableCacheTag(),
                            ],
                        ])
                    );
                }
            }


        }

        if (\Yii::$app instanceof Application) {
            if ($this->_site) {
                if ($this->_site->cmsSiteMainDomain) {
                    \Yii::$app->urlManager->hostInfo = $this->_site->cmsSiteMainDomain->url;
                }
            }
        }

        //Если только поставили проект, и база еще пустая.
        if (!$this->_site) {
            //$this->_site = new CmsSite();
            //$this->_site->setIsNewRecord(true);
        }

        return $this->_site;
    }

    /**
     * @param CmsSite $cmsSite
     * @return $this
     */
    public function setSite(CmsSite $cmsSite)
    {
        $this->_site = $cmsSite;
        return $this;
    }

    /**
     * @return void
     */
    static public function unlimited()
    {
        set_time_limit(0);
        ini_set("memory_limit", "50G");
    }
}