<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\actions\BackendGridModelAction;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\cms\backend\BackendController;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\ControllerActionsWidget;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\queries\CmsContractorQuery;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\TextField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\base\Exception;
use yii\bootstrap\Alert;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsContractorController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Юр. Лица");
        $this->modelShowAttribute = "asText";
        $this->modelClassName = CmsContractor::class;

        $this->generateAccessActions = false;
        $this->permissionName = 'cms/admin-company';

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [

                'on beforeRender' => function (Event $e) {
                    /*$e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],

                        'body' => <<<HTML
<p>Добавьте компании на которые вы получаете деньги, на которые заключаете договора в этот раздел. То есть, ваши компании и ИП.</p>
HTML
                        ,
                    ]);*/
                },

                "filters" => [
                    'visibleFilters' => [
                        'q',
                    ],
                    "filtersModel" => [
                        'rules'            => [
                            ['q', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                        ],

                        'fields' => [
                            'q' => [
                                'label'          => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск (ФИО, название)',
                                ],
                                'on apply'       => function (QueryFiltersEvent $e) {
                                    /**
                                     * @var $query CmsContractorQuery
                                     */
                                    $query = $e->dataProvider->query;

                                    if ($e->field->value) {
                                        $query->search($e->field->value);
                                    }
                                },
                            ],
                        ],
                    ],
                ],
                'grid'    => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->cmsSite();

                        $query->forManager();

                    },

                    'defaultOrder' => [
                        'id' => SORT_DESC,
                    ],

                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        //'code',
                        'is_active',
                        'priority',
                    ],

                    'columns' => [
                        'custom' => [
                            'format' => 'raw',
                            'value'  => function (CmsContractor $model) {

                                $data = [];
                                $data[] = Html::a($model->asText, "#", ['class' => 'sx-trigger-action']);

                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters'>
                                                <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='".($model->cmsImage ? $model->cmsImage->src : Image::getCapSrc())."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                                </div>
                                                <div class='my-auto' style='margin-left: 5px;'>".$info."</div></div>";;
                            },
                        ],

                        'is_active' => [
                            'class' => BooleanColumn::class,
                        ],

                        'image_id' => [
                            'class' => ImageColumn2::class,
                        ],
                    ],
                ],
            ],

            'bankData' => [
                'class'    => BackendModelAction::class,
                'name'     => 'Банковские реквизиты',
                'priority' => 500,
                'callback' => [$this, 'bankData'],
                'icon'     => 'far fa-file-alt',

                /*'accessCallback' => function ($action) {
                    $model = $action->model;
                    if ($model) {
                        return (bool)(in_array($model->type, [CrmContractor::TYPE_LEGAL, CrmContractor::TYPE_IP]));
                    }
                    return false;
                },*/
            ],

            "create" => [
                'fields' => [$this, 'updateFields'],
            ],
            "update" => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);
    }

    public function updateFields($action)
    {
        /**
         * @var $model CmsContractor
         */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());


        $mainFieldSet = [];
        if ($model->isNewRecord) {


            $mainFieldSet = [

                [
                    'class'   => HtmlBlock::class,
                    'content' => <<<HTML
<div class="col-12" style="margin-top: 15px;margin-bottom: 15px;">
    <div class="d-flex">
        <input type="text" id="sx-inn" style="max-width: 350px;" class="form-control" placeholder="Укажите ИНН" />
        <button class="btn btn-default sx-btn-auto-inn">Найти и заполнить</button>
    </div>
</div>
HTML
                    ,

                ],

                'contractor_type' => [
                    'class' => SelectField::class,
                    'items' => CmsContractor::optionsForType(),

                    /*'elementOptions' => [
                        'data' => [
                            'form-reload' => 'true',
                        ],
                    ],*/
                ],
            ];
        } else {
            $mainFieldSet = [
                [
                    'class'   => HtmlBlock::class,
                    'content' => '<div style="display: none;">',
                ],
                'contractor_type' => [
                    'class' => SelectField::class,
                    'items' => CmsContractor::optionsForType(),

                    /*'elementOptions' => [
                        'data' => [
                            'form-reload' => 'true',
                        ],
                    ],*/
                ],
                [
                    'class'   => HtmlBlock::class,
                    'content' => '</div>',
                ],


            ];

        }

        \Yii::$app->view->registerCss(<<<CSS
.sx-fiz-block, .sx-name-block {
    display: none;
}
CSS
        );

        $ip = CmsContractor::TYPE_INDIVIDUAL;
        $self = CmsContractor::TYPE_SELFEMPLOYED;
        $human = CmsContractor::TYPE_HUMAN;
        $legal = CmsContractor::TYPE_LEGAL;

        $innSearchData = Url::to(['dadata-inn']);
        \skeeks\cms\admin\assets\JqueryMaskInputAsset::register(\Yii::$app->view);

        \Yii::$app->view->registerJs(<<<JS
$("#cmscontractor-phone").mask("+7 999 999-99-99");
function updateFields() {
    $(".sx-fiz-block").hide();
    $(".sx-name-block").hide();
    
    var contType = $("#cmscontractor-contractor_type").val();
    if (contType == '{$ip}') {
        $(".sx-fiz-block").show();
    }
    if (contType == '{$self}') {
        $(".sx-fiz-block").show();
    }
    if (contType == '{$human}') {
        $(".sx-fiz-block").show();
    }
    if (contType == '{$legal}') {
        $(".sx-name-block").show();
    }
}

$("#cmscontractor-contractor_type").on("change", function() {
    updateFields();
});

$(".sx-btn-auto-inn").on("click", function() {
    
    var inn = $("#sx-inn").val();
    var ajaxQuery = sx.ajax.preparePostQuery('{$innSearchData}');
    ajaxQuery.setData({
        'inn' : inn
    });
    
    var ajaxHandler = new sx.classes.AjaxHandlerStandartRespose(ajaxQuery);
    ajaxHandler.on("success", function(e, response) {
        var companyData = response.data;
        
        $("#cmscontractor-address").val(companyData.data.address.unrestricted_value);
        $("#cmscontractor-mailing_address").val(companyData.data.address.unrestricted_value);
        $("#cmscontractor-mailing_postcode").val(companyData.data.address.data.postal_code);
        $("#cmscontractor-inn").val(companyData.data.inn);
        $("#cmscontractor-kpp").val(companyData.data.kpp);
        $("#cmscontractor-okpo").val(companyData.data.okpo);
        $("#cmscontractor-ogrn").val(companyData.data.ogrn);
        
        
        if (companyData.data.type == 'LEGAL') {
            $("#cmscontractor-contractor_type").val("legal");
            $("#cmscontractor-name").val(companyData.unrestricted_value);
            $("#cmscontractor-full_name").val(companyData.unrestricted_value);
            
        } else if (companyData.data.type == 'INDIVIDUAL') {
            $("#cmscontractor-contractor_type").val("individual");
            
            $("#cmscontractor-name").val(companyData.unrestricted_value);
            $("#cmscontractor-first_name").val(companyData.data.fio.name);
            $("#cmscontractor-last_name").val(companyData.data.fio.surname);
            $("#cmscontractor-patronymic").val(companyData.data.fio.patronymic);
        }
        
        $("#cmscontractor-contractor_type").trigger("change");
        
    });
    ajaxHandler.on("error", function() {
        
    });
    
    ajaxQuery.execute();
    return false;
});

updateFields();
JS
        );


        $mainFieldSet = ArrayHelper::merge($mainFieldSet, [


            [
                'class'   => HtmlBlock::class,
                'content' => '<div style="display: block;">',
            ],
            /*'is_our' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],*/
            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="sx-fiz-block">',
            ],
            'last_name',
            'first_name',
            'patronymic',

            [
                'class'   => HtmlBlock::class,
                'content' => '</div><div class="sx-name-block">',
            ],

            'name',
            'international_name',
            'full_name',

            [
                'class'   => HtmlBlock::class,
                'content' => '</div>',
            ],

            'cms_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],


        ]);

        $fieldSet2 = [


            'address' => [
                'class' => TextareaField::class,
            ],

            'mailing_address'  => [
                'class' => TextareaField::class,
            ],
            'mailing_postcode' => [
                'class' => NumberField::class,
            ],
            'phone',
            'email',

            'description' => [
                /*'class' => WidgetField::class,
                'widgetClass' => Ckeditor::class*/
                'class' => TextareaField::class,
            ],


        ];

        $fieldSet3 = [


            'stamp_id'                => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'director_signature_id'   => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'signature_accountant_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],

        ];

        /*if (!in_array($model->contractor_type, [CmsContractor::TYPE_INDIVIDUAL])) {
            $fieldSetLegal = [
                'inn',
                'kpp',
                'ogrn',
                'okpo',
            ];
        } else {
            $fieldSetLegal = [
                'inn',
            ];
        }*/

        if ($model->isNewRecord) {
            $fieldSetLegal = [
                'inn',
                'kpp',
                'ogrn',
                'okpo',
            ];
        } else {
            $fieldSetLegal = [
                'inn' => [
                    'class' => TextField::class,
                    'elementOptions' => [
                        'disabled' => 'disabled'
                    ]
                ],
                'kpp',
                'ogrn',
                'okpo',
            ];
        }



        /*if ($model->isNewRecord) {
            unset($mainFieldSet['contractor_type']);
        }*/

        $result = [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основное',
                'fields' => $mainFieldSet,
            ],

            'legal'      => [
                'class'  => FieldSet::class,
                'name'   => 'Юридические данные',
                'fields' => $fieldSetLegal,
            ],
            'address' => [
                'class'  => FieldSet::class,
                'name'   => 'Адрес и контакты',
                'fields' => $fieldSet2,
            ],
            /*'additional' => [
                'class'  => FieldSet::class,
                'name'   => 'Дополнительные данные',
                'fields' => $fieldSet3,
            ],*/
            'is_our' => [
                'class' => BoolField::class,
                'allowNull' => false,
            ],
        ];


        return $result;
    }

    public function actionDadataInn()
    {
        $rr = new RequestResponse();

        try {

            if ($inn = \Yii::$app->request->post("inn")) {

                $dadata = \Yii::$app->dadataClient->suggest->findByIdParty($inn);
                if (isset($dadata[0]) && $dadata[0]) {
                    //Создать компанию
                    $party = new PartyModel($dadata[0]);

                    $rr->success = true;
                    $rr->message = "Данные заполнены";
                    $rr->data = $party->toArray();
                } else {
                    throw new Exception("По этом ИНН ничего не надйено!");
                }
            }

        } catch (\Exception $e) {
            $rr->success = false;
            $rr->message = $e->getMessage();
        }


        return $rr;
    }
    
    
    public function bankData()
    {
        if ($controller = \Yii::$app->createController('/cms/admin-cms-contractor-bank')) {
            /**
             * @var $controller BackendController
             * @var $indexAction BackendGridModelAction
             */
            $controller = $controller[0];
            $controller->actionsMap = [
                'index' => [
                    'configKey' => $this->action->uniqueId,
                ],
            ];

            if ($indexAction = ArrayHelper::getValue($controller->actions, 'index')) {
                $indexAction->url = $this->action->urlData;
                $indexAction->filters = false;
                $visibleColumns = $indexAction->grid['visibleColumns'];
                ArrayHelper::removeValue($visibleColumns, 'cms_contractor_id');
                $indexAction->backendShowings = false;
                $indexAction->grid['visibleColumns'] = $visibleColumns;
                $indexAction->grid['columns']['actions']['isOpenNewWindow'] = true;
                $indexAction->grid['on init'] = function (Event $e) {
                    $dataProvider = $e->sender->dataProvider;
                    $dataProvider->query->andWhere(['cms_contractor_id' => $this->model->id]);
                };


                $indexAction->on('beforeRender', function (Event $event) use ($controller) {
                    if ($createAction = ArrayHelper::getValue($controller->actions, 'create')) {
                        /**
                         * @var $createAction BackendModelCreateAction
                         */
                        $createAction->name = "Добавить реквизиты";
                        $createAction->url = ArrayHelper::merge($createAction->urlData, ['cms_contractor_id' => $this->model->id]);
                        $createAction->isVisible = true;

                        $event->content = ControllerActionsWidget::widget([
                                'actions'         => [$createAction],
                                'isOpenNewWindow' => true,
                                /*'button'          => [
                                    'class' => 'btn btn-primary',
                                    //'style' => 'font-size: 11px; cursor: pointer;',
                                    'tag'   => 'button',
                                    'label' => 'Добавить реквизиты',
                                ],*/
                                'minViewCount'    => 1,
                                'itemTag'         => 'button',
                                'itemOptions'     => ['class' => 'btn btn-primary'],
                            ])."<br>";
                    }

                });


                return $indexAction->run();
            }
        }

        return '1';
    }
}
