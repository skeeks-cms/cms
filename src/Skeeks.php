<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 27.03.2015
 */

namespace skeeks\cms;

use skeeks\cms\backend\BackendComponent;
use skeeks\cms\log\CmsLogRendererInterface;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsCompanyAddress;
use skeeks\cms\models\CmsCompanyEmail;
use skeeks\cms\models\CmsCompanyLink;
use skeeks\cms\models\CmsCompanyPhone;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsFaq;
use skeeks\cms\models\CmsLog;
use skeeks\cms\models\CmsLead;
use skeeks\cms\models\CmsLeadEmail;
use skeeks\cms\models\CmsLeadPhone;
use skeeks\cms\models\CmsLeadRejectReason;
use skeeks\cms\models\CmsLeadStage;
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
use skeeks\cms\shop\models\ShopDocument;
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
 * @property string[] $activityLogTypes
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
        CmsLog::LOG_TYPE_PHONE_CALL => 'Звонок',
        CmsLog::LOG_TYPE_COMMENT => 'Комментарий',

        CmsLog::LOG_TYPE_DELETE => 'Удаление',
        CmsLog::LOG_TYPE_UPDATE => 'Обновление',
        CmsLog::LOG_TYPE_INSERT => 'Создание',
    ];

    public function bootstrap($application)
    {
        // "Создание из источника" (?source=<type>:<id>) in every standard create window.
        if ($application instanceof \yii\web\Application) {
            \skeeks\cms\source\RecordSources::attach();
        }

        // The journal of the access to clients: its events in the client feeds (crm\CrmAccessLog).
        if (!isset($this->logRenderers[\skeeks\cms\crm\CrmAccessLog::LOG_TYPE])) {
            $this->logRenderers[\skeeks\cms\crm\CrmAccessLog::LOG_TYPE] = \skeeks\cms\log\AccessLogRenderer::class;
        }
        $this->setActivityLogTypes([\skeeks\cms\crm\CrmAccessLog::LOG_TYPE]);

        // П2д: the protection of a client follows its payments (cms-shop, when installed) and services.
        $protection = static function (callable $callback) {
            try {
                if (\skeeks\cms\crm\CrmProtection::ready()) {
                    $callback(new \skeeks\cms\crm\CrmProtection());
                }
            } catch (\Throwable $e) {
                \Yii::warning('Защита клиента не пересчитана: '.$e->getMessage(), \skeeks\cms\crm\CrmProtection::class);
            }
        };
        if (class_exists('\skeeks\cms\shop\models\ShopPayment')) {
            foreach ([BaseActiveRecord::EVENT_AFTER_INSERT, BaseActiveRecord::EVENT_AFTER_UPDATE] as $paymentEvent) {
                Event::on('\skeeks\cms\shop\models\ShopPayment', $paymentEvent, static function (Event $event) use ($protection) {
                    $protection(static fn($service) => $service->onPayment($event->sender));
                });
            }
        }
        foreach ([BaseActiveRecord::EVENT_AFTER_INSERT, BaseActiveRecord::EVENT_AFTER_UPDATE, BaseActiveRecord::EVENT_AFTER_DELETE] as $dealEvent) {
            Event::on(CmsDeal::class, $dealEvent, static function (Event $event) use ($protection) {
                $deal = $event->sender;
                $protection(static function ($service) use ($deal) {
                    if ($deal->cms_company_id) {
                        $service->recalc('company', (int)$deal->cms_company_id);
                    } elseif ($deal->cms_user_id) {
                        $service->recalc('client', (int)$deal->cms_user_id);
                    }
                });
            });
        }

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
        // П2д: a presentation or a КП sent from a deal of «Исходящие продажи» and its first view move the deal.
        \skeeks\cms\crm\OutboundSales::subscribe();

        //Уведомления для пользователей
        Event::on(CmsLog::class, BaseActiveRecord::EVENT_AFTER_INSERT, function (Event $event) {
            /**
             * @var $sender CmsLog
             */
            $sender = $event->sender;
            if ($sender->model_code != CmsTask::class) {
                return;
            }
            /**
             * @var $model CmsTask
             */
            $model = $sender->model;
            if (!$model) {
                return;
            }
            $notifications = \Yii::$app->notifications;
            $task = ['model' => $model];

            //Поставлена задача
            if ($sender->log_type == CmsLog::LOG_TYPE_INSERT) {
                if ($model->executor_id && $model->executor_id != $model->created_by) {
                    $notifications->notify($model->executor_id, 'task.created', ['name' => "Вам поставлена новая задача", 'eventKey' => 'task.created:'.$model->id] + $task);
                }
            }

            if ($sender->log_type == CmsLog::LOG_TYPE_UPDATE) {
                //Только если не сам себе ставил задачу
                if ($model->executor_id != $model->created_by) {

                    $executor = ArrayHelper::getValue($sender->data, "executor_id.value");
                    $status = ArrayHelper::getValue($sender->data, "status.value");
                    $oldStatus = ArrayHelper::getValue($sender->data, "status.old_value");
                    $clientUrl = $model->isClientAuthored() ? $model->getClientViewUrl() : null;

                    //У задачи поменялся исполнитель
                    if ($executor) {
                        $notifications->notify($executor, 'task.passed', ['name' => "Вам передали задачу", 'eventKey' => 'task.passed:'.$sender->id] + $task);
                    }
                    //У задачи поменялся статус
                    if ($status) {
                        if ($status == CmsTask::STATUS_ON_CHECK) {
                            $notifications->notify($model->created_by, 'task.check', ['name' => "Вам необходимо проверить задачу", 'url' => $clientUrl, 'eventKey' => 'task.status:'.$sender->id] + $task);
                        }

                        if ($status == CmsTask::STATUS_READY) {
                            $notifications->notify($model->created_by, 'task.ready', ['name' => "Ваша задача готова", 'url' => $clientUrl, 'eventKey' => 'task.status:'.$sender->id] + $task);
                        }

                        if ($status == CmsTask::STATUS_CANCELED) {
                            $notifications->notify([$model->created_by, $model->executor_id], 'task.canceled', ['name' => "Задача отменена", 'eventKey' => 'task.status:'.$sender->id] + $task);
                        }

                        if ($status == CmsTask::STATUS_IN_WORK && $oldStatus == CmsTask::STATUS_ON_CHECK) {
                            $notifications->notify($model->executor_id, 'task.resumed', ['name' => "Задача возобновлена", 'eventKey' => 'task.status:'.$sender->id] + $task);
                        }
                    }
                }
            }

            //Добавлен комментарий к задаче
            if ($sender->log_type == CmsLog::LOG_TYPE_COMMENT) {
                $currentUserId = \Yii::$app->has('user') && !\Yii::$app->user->isGuest
                    ? (int)\Yii::$app->user->id
                    : null;
                foreach (array_unique(array_filter([(int)$model->executor_id, (int)$model->created_by])) as $id) {
                    if ($currentUserId !== null && $id === $currentUserId) {
                        continue;
                    }
                    $url = $id === (int)$model->created_by && $model->isClientAuthored() ? $model->getClientViewUrl((int)$sender->id) : null;
                    $notifications->notify($id, 'task.comment', ['name' => "Добавлен новый комментарий к задаче", 'url' => $url, 'eventKey' => 'task.comment:'.$sender->id] + $task);
                }
            }
        });
    }


    public $modelsConfig = [

        CmsLead::class          => [
            'name'       => 'Сделки',
            'name_one'   => 'Сделка',
            'controller' => 'cms/admin-cms-lead',
        ],
        \skeeks\cms\models\CmsLeadPipeline::class => [
            'name'       => 'Воронки сделок',
            'name_one'   => 'Воронка',
            'controller' => 'cms/admin-cms-lead-pipeline',
        ],
        // A notification about a request opens its card in the backend window.
        \skeeks\cms\models\CmsCrmAccessRequest::class => [
            'name'       => 'Запросы доступа',
            'name_one'   => 'Запрос доступа',
            'controller' => 'cms/admin-cms-crm-access-request',
        ],
        CmsLeadStage::class     => [
            'name'       => 'Этапы сделок',
            'name_one'   => 'Этап сделки',
            'controller' => 'cms/admin-cms-lead-stage',
        ],
        CmsLeadRejectReason::class => [
            'name'       => 'Причины отказа по сделкам',
            'name_one'   => 'Причина отказа',
            'controller' => 'cms/admin-cms-lead-reject-reason',
        ],
        CmsLeadPhone::class     => [
            'name'       => 'Телефоны сделок',
            'name_one'   => 'Телефон сделки',
            'controller' => 'cms/admin-cms-lead-phone',
        ],
        CmsLeadEmail::class     => [
            'name'       => 'Email-ы сделок',
            'name_one'   => 'Email сделки',
            'controller' => 'cms/admin-cms-lead-email',
        ],

        CmsFaq::class           => [
            'name'       => 'Вопросы/Ответы',
            'name_one'   => 'Вопрос/Ответ',
            'controller' => 'cms/admin-cms-faq',
        ],

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
            'name'       => 'Услуги',
            'name_one'   => 'Услуга',
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
        ShopDocument::class        => [
            'name'       => 'Документы',
            'name_one'   => 'Документ',
            'controller' => 'cms/admin-cms-document',
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
     * Renderers of log types owned by other packages: log type => class name,
     * Yii object config or callable `function (CmsLog $log): string`.
     * A registered renderer takes precedence over the built-in rendering.
     *
     * @var array
     */
    public $logRenderers = [];

    /** @var CmsLogRendererInterface[]|callable[] */
    private $_logRendererInstances = [];

    /**
     * @param string $logType
     * @return CmsLogRendererInterface|callable|null
     */
    public function getLogRenderer($logType)
    {
        $logType = (string)$logType;
        if (!isset($this->logRenderers[$logType])) {
            return null;
        }

        if (!array_key_exists($logType, $this->_logRendererInstances)) {
            $renderer = $this->logRenderers[$logType];
            if (!is_callable($renderer)) {
                $renderer = \Yii::createObject($renderer);
                if (!$renderer instanceof CmsLogRendererInterface) {
                    throw new \yii\base\InvalidConfigException("Log renderer of «{$logType}» must implement ".CmsLogRendererInterface::class);
                }
            }
            $this->_logRendererInstances[$logType] = $renderer;
        }

        return $this->_logRendererInstances[$logType];
    }

    /**
     * Sources new CRM records are created from ("Создать задачу из письма"):
     * source type => class name or Yii object config of a
     * \skeeks\cms\source\RecordSourceInterface. The standard create window
     * uses it with ?source=<type>:<id> (\skeeks\cms\source\RecordSources).
     * Registrations of several packages merge.
     *
     * @var array
     */
    public $recordSources = [];

    /** @var \skeeks\cms\source\RecordSourceInterface[] */
    private $_recordSourceInstances = [];

    /**
     * @param string $type
     * @return \skeeks\cms\source\RecordSourceInterface|null
     */
    public function getRecordSource($type)
    {
        $type = (string)$type;
        if (!isset($this->recordSources[$type])) {
            return null;
        }
        if (!isset($this->_recordSourceInstances[$type])) {
            $source = \Yii::createObject($this->recordSources[$type]);
            if (!$source instanceof \skeeks\cms\source\RecordSourceInterface) {
                throw new \yii\base\InvalidConfigException("Record source «{$type}» must implement ".\skeeks\cms\source\RecordSourceInterface::class);
            }
            $this->_recordSourceInstances[$type] = $source;
        }

        return $this->_recordSourceInstances[$type];
    }

    /**
     * Log types shown in the activity feeds of CRM cards (lead, company, client).
     *
     * @var string[]
     */
    public $defaultActivityLogTypes = [
        CmsLog::LOG_TYPE_PHONE_CALL,
        CmsLog::LOG_TYPE_COMMENT,
    ];

    /** @var string[] */
    private $_activityLogTypes = [];

    /**
     * Adds log types to the activity feeds; packages register their own types here.
     *
     * @param string[] $logTypes
     * @return $this
     */
    public function setActivityLogTypes(array $logTypes)
    {
        $this->_activityLogTypes = array_merge($this->_activityLogTypes, array_values($logTypes));
        return $this;
    }

    /**
     * @return string[]
     */
    public function getActivityLogTypes()
    {
        return array_values(array_unique(array_map('strval', array_merge((array)$this->defaultActivityLogTypes, $this->_activityLogTypes))));
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
