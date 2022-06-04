<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\helpers\RequestResponse;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\yii2\dadataClient\models\PartyModel;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\NumberField;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
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
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsContractor::class;

        $this->generateAccessActions = false;

        $this->accessCallback = function () {
            return \Yii::$app->user->can($this->uniqueId);
        };

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
                    $e->content = Alert::widget([
                        'closeButton' => false,
                        'options'     => [
                            'class' => 'alert-default',
                        ],

                        'body' => <<<HTML
<p>Добавьте компании на которые вы получаете деньги, на которые заключаете договора в этот раздел. То есть, ваши компании и ИП.</p>
HTML
                        ,
                    ]);
                },

                "filters" => [
                    'visibleFilters' => [
                        'id',
                        'name',
                    ],
                ],
                'grid'    => [

                    'on init' => function (Event $e) {
                        /**
                         * @var $dataProvider ActiveDataProvider
                         * @var $query ActiveQuery
                         */
                        $query = $e->sender->dataProvider->query;

                        $query->cmsSite()->our();
                        /*$paymentsQuery = CrmPayment::find()->select(['count(*)'])->where([
                            'or',
                            ['sender_crm_contractor_id' => new Expression(CmsContractor::tableName().".id")],
                            ['receiver_crm_contractor_id' => new Expression(CmsContractor::tableName().".id")],
                        ]);

                        $contactsQuery = CmsContractorMap::find()->select(['count(*)'])->where([
                            'crm_company_id' => new Expression(CmsContractor::tableName().".id"),
                        ]);

                        $senderQuery = CrmPayment::find()->select(['sum(amount) as amount'])->where([
                            'sender_crm_contractor_id' => new Expression(CmsContractor::tableName().".id"),
                        ]);

                        $receiverQuery = CrmPayment::find()->select(['sum(amount) as amount'])->where([
                            'receiver_crm_contractor_id' => new Expression(CmsContractor::tableName().".id"),
                        ]);

                        $query->select([
                            CmsContractor::tableName().'.*',
                            'count_payemnts'      => $paymentsQuery,
                            'count_contacts'      => $contactsQuery,
                            'sum_send_amount'     => $senderQuery,
                            'sum_receiver_amount' => $receiverQuery,
                        ]);*/
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

            $model->is_our = 1;

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
                'content' => '<div style="display: none;">',
            ],
            'is_our',
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
        $fieldSetLegal = [
                'inn',
                'kpp',
                'ogrn',
                'okpo',
            ];


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
            'additional' => [
                'class'  => FieldSet::class,
                'name'   => 'Дополнительные данные',
                'fields' => $fieldSet2,
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
}
