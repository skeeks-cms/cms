<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS
 */

namespace skeeks\cms\controllers;

use kartik\datecontrol\DateControl;
use skeeks\cms\backend\actions\BackendModelAction;
use skeeks\cms\backend\actions\BackendModelLogAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\AjaxControllerActionsWidget;
use skeeks\cms\measure\models\CmsMeasure;
use skeeks\cms\models\CmsCompany;
use skeeks\cms\models\CmsContractor;
use skeeks\cms\models\CmsDeal;
use skeeks\cms\models\CmsUser;
use skeeks\cms\money\Money;
use skeeks\cms\queryfilters\QueryFiltersEvent;
use skeeks\cms\shop\document\FnsUpdXmlGenerator;
use skeeks\cms\shop\models\queries\ShopDocumentQuery;
use skeeks\cms\shop\models\ShopBill;
use skeeks\cms\shop\models\ShopDocument;
use skeeks\cms\widgets\AjaxSelectModel;
use skeeks\cms\widgets\formInputs\daterange\DaterangeInputWidget;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\HtmlBlock;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class AdminCmsDocumentController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', 'Документы');
        $this->modelShowAttribute = 'asText';
        $this->modelClassName = ShopDocument::class;
        $this->permissionName = 'cms/admin-company';
        $this->generateAccessActions = false;

        $this->modelHeader = function () {
            return $this->renderPartial("@skeeks/cms/views/admin-cms-document/_model_header", [
                'model' => $this->model,
            ]);
        };

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'create' => [
                'fields' => [$this, 'updateFields'],
            ],
            'view' => [
                'class'       => BackendModelAction::class,
                'name'        => 'Документ',
                'icon'        => 'fa fa-file-signature',
                'priority'    => 1,
                'defaultView' => '@skeeks/cms/views/admin-cms-document/view',
            ],
            'update' => [
                'fields'   => [$this, 'updateFields'],
                'priority' => 10,
                'accessCallback' => function (BackendModelAction $action) {
                    return $action->model && $action->model->isEditable;
                },
            ],
            'delete' => [
                'accessCallback' => function (BackendModelAction $action) {
                    return $action->model && $action->model->isEditable;
                },
            ],
            'delete-multi' => [
                'eachAccessCallback' => function (ShopDocument $model) {
                    return $model->isEditable;
                },
            ],
            'index' => [
                'filters' => [
                    'visibleFilters' => [
                        'q',
                        'type',
                        'status',
                        'date',
                    ],
                    'filtersModel' => [
                        'rules' => [
                            ['q', 'safe'],
                            ['date', 'safe'],
                            ['type', 'safe'],
                            ['status', 'safe'],
                        ],
                        'attributeDefines' => [
                            'q',
                            'date',
                            'type',
                            'status',
                        ],
                        'fields' => [
                            'q' => [
                                'label' => 'Поиск',
                                'elementOptions' => [
                                    'placeholder' => 'Поиск',
                                ],
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /** @var ShopDocumentQuery $query */
                                    $query = $e->dataProvider->query;
                                    if ($e->field->value) {
                                        $query->search($e->field->value);
                                        $query->joinWith('company as company');
                                        $query->orWhere(['LIKE', 'company.name', $e->field->value]);
                                    }
                                },
                            ],
                            'date' => [
                                'class' => WidgetField::class,
                                'widgetClass' => DaterangeInputWidget::class,
                                'widgetConfig' => [
                                    'options' => [
                                        'placeholder' => 'Диапазон дат',
                                    ],
                                ],
                                'label' => 'Дата документа',
                                'on apply' => function (QueryFiltersEvent $e) {
                                    /** @var ShopDocumentQuery $query */
                                    $query = $e->dataProvider->query;
                                    if ($e->field->value && ($range = DaterangeInputWidget::parseRange($e->field->value))) {
                                        list($start, $end) = $range;
                                        $query->andWhere(['>=', ShopDocument::tableName().'.issued_at', $start]);
                                        $query->andWhere(['<=', ShopDocument::tableName().'.issued_at', $end]);
                                    }
                                },
                            ],
                            'type' => [
                                'class' => SelectField::class,
                                'items' => ShopDocument::optionsForType(),
                                'label' => 'Тип',
                                'on apply' => function (QueryFiltersEvent $e) {
                                    if ($e->field->value) {
                                        $e->dataProvider->query->andWhere([ShopDocument::tableName().'.type' => $e->field->value]);
                                    }
                                },
                            ],
                            'status' => [
                                'class' => SelectField::class,
                                'items' => ShopDocument::optionsForStatus(),
                                'label' => 'Статус',
                                'on apply' => function (QueryFiltersEvent $e) {
                                    if ($e->field->value) {
                                        $e->dataProvider->query->andWhere([ShopDocument::tableName().'.status' => $e->field->value]);
                                    }
                                },
                            ],
                        ],
                    ],
                ],
                'grid' => [
                    'on init' => function (Event $e) {
                        /** @var ActiveDataProvider $dataProvider */
                        $dataProvider = $e->sender->dataProvider;
                        /** @var ShopDocumentQuery $query */
                        $query = $dataProvider->query;
                        $query->forManager();
                    },
                    'defaultOrder' => [
                        'issued_at' => SORT_DESC,
                        'id'        => SORT_DESC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'issued_at',
                        'amount',
                        'cms_company_id',
                        'buyer_contractor_id',
                        'bills',
                        'deals',
                        'description',
                    ],
                    'columns' => [
                        'issued_at' => [
                            'label' => 'Документ',
                            'format' => 'raw',
                            'value' => function (ShopDocument $model) {
                                $titleAction = AjaxControllerActionsWidget::widget([
                                    'controllerId'            => '/cms/admin-cms-document',
                                    'modelId'                 => $model->id,
                                    'isRunFirstActionOnClick' => true,
                                    'tag'                     => 'span',
                                    'content'                 => Html::encode($model->asText),
                                    'options'                 => [
                                        'style' => 'cursor: pointer; color: #1d70b8; display: inline-block; font-size: 15px; white-space: nowrap;',
                                    ],
                                ]);

                                $statusColors = $model->statusColors;
                                $status = Html::tag('small', '<i class="'.Html::encode($model->statusIcon).'" style="margin-right:3px;"></i> '.Html::encode($model->statusAsText), [
                                    'style' => Html::cssStyleFromArray([
                                        'display'          => 'inline-block',
                                        'margin-top'       => '5px',
                                        'padding'          => '3px 8px',
                                        'border'           => '1px solid '.$statusColors['border'],
                                        'border-radius'    => '999px',
                                        'background-color' => $statusColors['background'],
                                        'color'            => $statusColors['text'],
                                        'font-weight'      => '600',
                                    ]),
                                ]);

                                return $titleAction.'<br>'.$status;
                            },
                        ],
                        'amount' => [
                            'value' => function (ShopDocument $model) {
                                return (string)$model->money;
                            },
                        ],
                        'cms_company_id' => [
                            'format' => 'raw',
                            'value' => function (ShopDocument $model) {
                                if (!$model->company) {
                                    return '';
                                }

                                return AjaxControllerActionsWidget::widget([
                                    'controllerId' => '/cms/admin-cms-company',
                                    'modelId'      => $model->company->id,
                                    'content'      => '<i class="fas fa-users"></i> '.Html::encode($model->company->asText),
                                    'options'      => ['style' => 'text-align: left;'],
                                ]);
                            },
                        ],
                        'buyer_contractor_id' => [
                            'label' => 'Покупатель',
                            'format' => 'raw',
                            'value' => function (ShopDocument $model) {
                                if ($model->buyerContractor) {
                                    return AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-contractor',
                                        'modelId'      => $model->buyerContractor->id,
                                        'content'      => '<i class="far fa-user"></i> '.Html::encode($model->buyerContractor->asText),
                                        'options'      => ['style' => 'text-align: left;'],
                                    ]);
                                }

                                return Html::encode($model->buyerName);
                            },
                        ],
                        'bills' => [
                            'label' => 'Счета',
                            'format' => 'raw',
                            'value' => function (ShopDocument $model) {
                                $result = [];
                                foreach ($model->bills as $bill) {
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-bill',
                                        'modelId'      => $bill->id,
                                        'content'      => '<i class="far fa-file"></i> '.Html::encode($bill->asText),
                                        'options'      => ['style' => 'text-align: left;'],
                                    ]);
                                }

                                return implode('', $result);
                            },
                        ],
                        'deals' => [
                            'label' => 'Сделки',
                            'format' => 'raw',
                            'value' => function (ShopDocument $model) {
                                $result = [];
                                foreach ($model->deals as $deal) {
                                    $result[] = AjaxControllerActionsWidget::widget([
                                        'controllerId' => '/cms/admin-cms-deal',
                                        'modelId'      => $deal->id,
                                        'content'      => '<i class="far fa-file"></i> '.Html::encode($deal->asText),
                                        'options'      => ['style' => 'text-align: left;'],
                                    ]);
                                }

                                return implode('', $result);
                            },
                        ],
                    ],
                    'on afterRun' => function ($event) {
                        $grid = $event->sender;
                        $query = clone $grid->dataProvider->query;
                        $tableName = ShopDocument::tableName();
                        $result = $query->select([$tableName.'.id', 'sum' => new Expression("SUM({$tableName}.amount)")])
                            ->asArray()->one();
                        $sumAmount = ArrayHelper::getValue($result, 'sum');
                        $money = new Money($sumAmount, 'RUB');

                        $event->result = '<div class="alert alert-default"><span class="g-font-size-30">Всего: '.$money.'</span></div>';
                    },
                ],
            ],
            'log' => [
                'class' => BackendModelLogAction::class,
            ],
        ]);
    }

    public function actionCreateFromBill($bill_id, $type = ShopDocument::TYPE_ACT)
    {
        if (!$bill = ShopBill::find()->where(['id' => (int)$bill_id])->one()) {
            throw new NotFoundHttpException('Счет не найден');
        }

        $sxb = (array)\Yii::$app->request->get('_sxb', []);

        return $this->redirect(Url::to([
            'create',
            'bill_id' => $bill->id,
            'type'    => ShopDocument::normalizeType($type),
            '_sxb' => [
                'el'  => $sxb['el'] ?? 1,
                'noa' => $sxb['noa'] ?? 1,
            ],
        ]));
    }

    public function actionXml($pk)
    {
        $document = $this->findDocument($pk);
        if ($document->type != ShopDocument::TYPE_UPD) {
            throw new NotFoundHttpException('XML доступен только для УПД');
        }

        $generator = new FnsUpdXmlGenerator($document);

        return \Yii::$app->response->sendContentAsFile($generator->generate(), $generator->fileName(), [
            'mimeType' => 'application/xml',
            'inline'   => false,
        ]);
    }

    public function actionStatus($pk)
    {
        if (!\Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Некорректный запрос');
        }

        $document = $this->findDocument($pk);
        $status = (string)\Yii::$app->request->post('status');
        if (!array_key_exists($status, ShopDocument::optionsForStatus())) {
            throw new BadRequestHttpException('Некорректный статус документа');
        }

        $canceledReason = trim((string)\Yii::$app->request->post('canceled_reason'));
        if ($status === ShopDocument::STATUS_CANCELED && $canceledReason === '') {
            \Yii::$app->session->setFlash('error', 'Укажите причину отмены документа');
            return $this->redirect(\Yii::$app->request->referrer ?: Url::to(['view', $this->requestPkParamName => $document->id]));
        }

        $document->status = $status;
        $document->canceled_reason = $status === ShopDocument::STATUS_CANCELED ? $canceledReason : null;
        $document->updated_at = time();
        $document->updated_by = \Yii::$app->user->id ?: null;

        if ($document->save(false, ['status', 'canceled_reason', 'updated_at', 'updated_by'])) {
            \Yii::$app->session->setFlash('success', 'Статус документа обновлен');
        } else {
            \Yii::$app->session->setFlash('error', 'Не удалось обновить статус документа');
        }

        return $this->redirect(\Yii::$app->request->referrer ?: Url::to(['view', $this->requestPkParamName => $document->id]));
    }

    public function updateFields($action)
    {
        if (\Yii::$app->request->get('sx-document-product-search')) {
            \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            \Yii::$app->response->data = $this->actionDocumentProductSearch((string)\Yii::$app->request->get('q', ''));
            \Yii::$app->end();
        }

        /** @var ShopDocument $model */
        $model = $action->model;
        if (!$model->isNewRecord && !$model->isEditable) {
            throw new ForbiddenHttpException('Документ нельзя редактировать в текущем статусе');
        }
        $model->load(\Yii::$app->request->get());
        $this->prepareModelFromCreateRequest($model);
        $this->prepareModelFromBillRequest($model);

        if ($model->isNewRecord && !$model->issued_at) {
            $model->issued_at = strtotime('today');
        }
        if ($model->isNewRecord && !$model->comment_after) {
            $model->comment_after = $model->defaultCommentAfter();
        }

        $this->view->registerCSS(<<<CSS
.field-shopdocument-cms_company_id {
    display: none;
}
.field-shopdocument-cms_user_id {
    display: none;
}
.sx-choose-document-client .btn.sx-active {
    background: #6c757d !important;
    color: white;
}
CSS
        );

        $this->view->registerJs(<<<JS
$("body").on("click", ".sx-choose-document-client .btn", function(e, data) {
    $(".field-shopdocument-cms_company_id").slideUp();
    $(".field-shopdocument-cms_user_id").slideUp();

    var is_first = false;
    if (data && data.is_first) {
        is_first = true;
    }

    if (is_first === false) {
        $("#shopdocument-cms_company_id").val("");
        $("#shopdocument-cms_user_id").val("");
    }

    $(".sx-choose-document-client .btn").removeClass("sx-active");
    $(this).addClass("sx-active");
    $($(this).data("view")).slideDown();
    return false;
});

$("body").on("select2:select select2:unselect", "#shopdocument-cms_company_id", function() {
    $("#shopdocument-cms_user_id").val("");
});

$("body").on("select2:select select2:unselect", "#shopdocument-cms_user_id", function() {
    $("#shopdocument-cms_company_id").val("");
});

function normalizeDocumentClientValues() {
    if ($(".sx-document-cms-company-btn").hasClass("sx-active")) {
        $("#shopdocument-cms_user_id").val("");
    } else if ($(".sx-document-cms-user-btn").hasClass("sx-active")) {
        $("#shopdocument-cms_company_id").val("");
    }
}

function reloadDocumentClientView() {
    var cms_company_id = $("#shopdocument-cms_company_id").val();
    var cms_user_id = $("#shopdocument-cms_user_id").val();

    if (cms_company_id) {
        $(".sx-document-cms-company-btn").trigger("click", {
            "is_first" : true
        });
    } else if (cms_user_id) {
        $(".sx-document-cms-user-btn").trigger("click", {
            "is_first" : true
        });
    } else {
        $(".sx-document-cms-company-btn").trigger("click", {
            "is_first" : true
        });
    }

    return false;
}

$("body").on("beforeSubmit submit", "form", function() {
    if ($(this).find("#shopdocument-cms_company_id, #shopdocument-cms_user_id").length) {
        normalizeDocumentClientValues();
    }
});

reloadDocumentClientView();

$(document).on("pjax:complete", function() {
    setTimeout(function() {
        reloadDocumentClientView();
    }, 200);
});
JS
        );

        $extractRelationIds = function ($values) {
            $ids = [];
            foreach ((array)$values as $value) {
                if (is_object($value) && isset($value->id)) {
                    $ids[(int)$value->id] = (int)$value->id;
                } elseif (is_array($value) && isset($value['id'])) {
                    $ids[(int)$value['id']] = (int)$value['id'];
                } elseif (is_numeric($value)) {
                    $ids[(int)$value] = (int)$value;
                }
            }

            return array_values(array_filter($ids));
        };

        $selectedBillIds = $extractRelationIds($model->bills);
        $selectedDealIds = $extractRelationIds($model->deals);

        if (!$selectedBillIds && !$model->isNewRecord && $model->documentItems) {
            foreach ($model->documentItems as $documentItem) {
                if ((int)$documentItem->source_shop_bill_id) {
                    $selectedBillIds[(int)$documentItem->source_shop_bill_id] = (int)$documentItem->source_shop_bill_id;
                }
            }
            $selectedBillIds = array_values($selectedBillIds);
        }

        $selectedListOptions = function (array $ids) {
            $options = [];
            foreach ($ids as $id) {
                $options[(int)$id] = ['selected' => true];
            }

            return $options;
        };

        $billData = [];
        $dealData = [];

        if ($model->cms_company_id || $model->cms_user_id || $selectedBillIds) {
            $billQuery = ShopBill::find()->forManager();
            if ($model->cms_company_id) {
                $billQuery->andWhere(['cms_company_id' => $model->cms_company_id]);
            }
            if ($model->cms_user_id) {
                $billQuery->andWhere(['cms_user_id' => $model->cms_user_id]);
            }
            if ($selectedBillIds) {
                $billQuery->orWhere([ShopBill::tableName().'.id' => $selectedBillIds]);
            }
            $billData = ArrayHelper::map($billQuery->limit(100)->all(), 'id', 'asFullText');
            if ($model->bills && !$model->isNewRecord) {
                $billData = ArrayHelper::merge(ArrayHelper::map($model->bills, 'id', 'asFullText'), (array)$billData);
            }
            if ($selectedBillIds) {
                $billData = ArrayHelper::merge(ArrayHelper::map(ShopBill::find()->where(['id' => $selectedBillIds])->all(), 'id', 'asFullText'), (array)$billData);
            }
        }

        if ($model->cms_company_id || $model->cms_user_id || $selectedDealIds) {
            $dealQuery = CmsDeal::find()->forManager();
            if ($model->cms_company_id) {
                $dealQuery->andWhere(['cms_company_id' => $model->cms_company_id]);
            }
            if ($model->cms_user_id) {
                $dealQuery->andWhere(['cms_user_id' => $model->cms_user_id]);
            }
            if ($selectedDealIds) {
                $dealQuery->orWhere([CmsDeal::tableName().'.id' => $selectedDealIds]);
            }
            $dealData = ArrayHelper::map($dealQuery->limit(100)->all(), 'id', 'asText');
            if ($model->deals && !$model->isNewRecord) {
                $dealData = ArrayHelper::merge(ArrayHelper::map($model->deals, 'id', 'asText'), (array)$dealData);
            }
        }

        $mainFields = [];
        if ($model->isNewRecord && \Yii::$app->request->get('type') !== null) {
            $mainFields['type_hidden'] = [
                'class'   => HtmlBlock::class,
                'content' => Html::activeHiddenInput($model, 'type'),
            ];
        } elseif ($model->isNewRecord) {
            $mainFields['type'] = [
                'class' => SelectField::class,
                'items' => ShopDocument::optionsForType(),
            ];
        }
        $mainFields['number'] = [
            'hint' => 'Необязательно: если оставить пустым, номер заполнится автоматически после сохранения.',
        ];
        $mainFields['issued_at'] = [
            'class' => WidgetField::class,
            'widgetClass' => DateControl::class,
            'widgetConfig' => [
                'type' => DateControl::FORMAT_DATE,
            ],
        ];

        return [
            'main' => [
                'class' => FieldSet::class,
                'name' => 'Документ',
                'fields' => $mainFields,
            ],
            'client' => [
                'class' => FieldSet::class,
                'name' => 'Клиент',
                'fields' => [
                    'client_selector' => [
                        'class' => HtmlBlock::class,
                        'content' => '<div class="col-12 sx-choose-document-client form-group"><div class="btn-group btn-block" role="group" aria-label="Document client">
                              <button type="button" class="btn btn-default sx-document-cms-company-btn" data-view=".field-shopdocument-cms_company_id">&#1050;&#1086;&#1084;&#1087;&#1072;&#1085;&#1080;&#1103;</button>
                              <button type="button" class="btn btn-default sx-document-cms-user-btn" data-view=".field-shopdocument-cms_user_id">&#1050;&#1086;&#1085;&#1090;&#1072;&#1082;&#1090;</button>
                            </div></div>',
                    ],
                    'cms_company_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'options' => [
                                'data' => ['form-reload' => 'true'],
                            ],
                            'modelClass' => CmsCompany::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsCompany::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                    'cms_user_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'options' => [
                                'data' => ['form-reload' => 'true'],
                            ],
                            'modelClass' => CmsUser::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsUser::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                ],
            ],
            'requisites' => [
                'class' => FieldSet::class,
                'name' => 'Стороны',
                'fields' => [
                    'seller_contractor_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'options' => [
                                'data' => ['form-reload' => 'true'],
                            ],
                            'modelClass' => CmsContractor::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsContractor::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                    'buyer_contractor_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxSelectModel::class,
                        'widgetConfig' => [
                            'options' => [
                                'data' => ['form-reload' => 'true'],
                            ],
                            'modelClass' => CmsContractor::class,
                            'searchQuery' => function ($word = '') {
                                $query = CmsContractor::find()->forManager();
                                if ($word) {
                                    $query->search($word);
                                }
                                return $query;
                            },
                        ],
                    ],
                ],
            ],
            'relations' => [
                'class' => FieldSet::class,
                'name' => 'Связи',
                'fields' => [
                    'bills' => [
                        'class' => SelectField::class,
                        'multiple' => true,
                        'items' => $billData,
                        'elementOptions' => [
                            'value' => $selectedBillIds,
                            'options' => $selectedListOptions($selectedBillIds),
                        ],
                    ],
                    'deals' => [
                        'class' => SelectField::class,
                        'multiple' => true,
                        'items' => $dealData,
                        'elementOptions' => [
                            'value' => $selectedDealIds,
                            'options' => $selectedListOptions($selectedDealIds),
                        ],
                    ],
                ],
            ],
            'description' => [
                'class' => FieldSet::class,
                'name' => 'Комментарии',
                'fields' => [
                    'description' => [
                        'class' => TextareaField::class,
                    ],
                    'comment_before' => [
                        'class' => TextareaField::class,
                    ],
                    'comment_after' => [
                        'class' => TextareaField::class,
                    ],
                ],
            ],
            'items' => [
                'class' => HtmlBlock::class,
                'content' => $this->renderDocumentItemsField($model),
            ],
            'printData' => [
                'class' => HtmlBlock::class,
                'content' => $this->renderDocumentSpecificDataField($model),
            ],
        ];
    }

    protected function prepareModelFromBillRequest(ShopDocument $model)
    {
        if (!$model->isNewRecord || \Yii::$app->request->isPost) {
            return;
        }

        $billId = (int)\Yii::$app->request->get('bill_id');
        if (!$billId) {
            return;
        }

        $bill = ShopBill::find()->where(['id' => $billId])->one();
        if (!$bill) {
            return;
        }

        $prefilled = ShopDocument::createFromBill(
            $bill,
            \Yii::$app->request->get('type', $model->type ?: ShopDocument::TYPE_ACT)
        );

        $model->setAttributes($prefilled->getAttributes(), false);
        $model->documentItemsData = $prefilled->documentItemsData;
        $model->isSnapshotPrepared = true;
        $model->bills = $prefilled->bills;
        $model->deals = $prefilled->deals;
    }

    protected function prepareModelFromCreateRequest(ShopDocument $model)
    {
        if (!$model->isNewRecord || \Yii::$app->request->isPost) {
            return;
        }

        if (($type = \Yii::$app->request->get('type')) !== null) {
            $model->type = ShopDocument::normalizeType($type);
        }
        if ($cmsCompanyId = (int)\Yii::$app->request->get('cms_company_id')) {
            $model->cms_company_id = $cmsCompanyId;
        }
        if ($cmsUserId = (int)\Yii::$app->request->get('cms_user_id')) {
            $model->cms_user_id = $cmsUserId;
        }
    }

    public function actionDocumentProductSearch($q = '')
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $q = trim((string)$q);
        $query = \skeeks\cms\shop\models\ShopProduct::find()
            ->alias('shopProduct')
            ->joinWith([
                'cmsContentElement as cmsContentElement',
                'brand as shopBrand',
            ])
            ->with([
                'cmsContentElement.cmsTree',
                'brand',
                'country',
                'measure',
                'baseProductPrice',
            ])
            ->limit(12);

        if ($q !== '') {
            $query->andWhere([
                'or',
                ['like', 'cmsContentElement.name', $q],
                ['like', 'cmsContentElement.code', $q],
                ['like', 'shopBrand.name', $q],
                ['like', 'shopProduct.brand_sku', $q],
            ]);
        }

        $items = [];
        foreach ($query->orderBy(['cmsContentElement.name' => SORT_ASC])->all() as $product) {
            $element = $product->cmsContentElement;
            $price = $product->baseProductPrice ? (float)$product->baseProductPrice->price : 0;
            $imageSrc = '';
            if ($element && $element->mainProductImage) {
                $imageSrc = \Yii::$app->imaging->thumbnailUrlOnRequest(
                    $element->mainProductImage->src,
                    new \skeeks\cms\components\imaging\filters\Thumbnail()
                );
            }
            $currencyCode = $product->baseProductPrice && $product->baseProductPrice->currency_code
                ? $product->baseProductPrice->currency_code
                : \Yii::$app->money->currencyCode;
            $country = $product->country;

            $items[] = [
                'id'        => $product->id,
                'name'      => $element ? $element->productName : "#".$product->id,
                'price'     => $price,
                'priceText' => $this->documentFormatMoney($price).' '.$this->documentCurrencySymbol($currencyCode),
                'measure'   => $product->measure ? $product->measure->asShortText : 'шт',
                'brand'     => $product->brand ? $product->brand->name : '',
                'category'  => $element && $element->cmsTree ? $element->cmsTree->name : '',
                'image'     => $imageSrc,
                'extraData' => [
                    'code'               => trim((string)($product->brand_sku ?: ($element ? $element->code : ''))),
                    'country_code'       => $country ? $country->iso : '',
                    'country_name'       => $country ? $country->name : '',
                    'declaration_number' => '',
                ],
            ];
        }

        return [
            'items' => $items,
        ];
    }

    public function renderDocumentSpecificDataField(ShopDocument $model)
    {
        $data = (array)$model->document_data;
        $formName = $model->formName();

        $control = function ($label, array $path, $options = []) use ($data, $formName) {
            $name = $formName.'[document_data]';
            foreach ($path as $part) {
                $name .= '['.$part.']';
            }

            $value = ArrayHelper::getValue($data, implode('.', $path), ArrayHelper::getValue($options, 'default', ''));
            $type = ArrayHelper::getValue($options, 'type', 'text');
            $inputOptions = ArrayHelper::merge(['class' => 'form-control'], (array)ArrayHelper::getValue($options, 'inputOptions', []));

            if ($type == 'textarea') {
                $input = Html::textarea($name, $value, ArrayHelper::merge($inputOptions, ['rows' => ArrayHelper::getValue($options, 'rows', 2)]));
            } else {
                $input = Html::textInput($name, $value, $inputOptions);
            }

            return Html::tag('div',
                Html::tag('label', Html::encode($label), ['class' => 'control-label']).$input,
                ['class' => 'form-group']
            );
        };

        $dateValue = function ($value) {
            return ShopDocument::normalizeDocumentDateValue($value);
        };

        $renderRowsField = function ($label, array $path, array $columns, array $rows, array $emptyRow = []) use ($data, $formName, $dateValue) {
            $namePrefix = $formName.'[document_data]';
            foreach ($path as $part) {
                $namePrefix .= '['.$part.']';
            }

            $normalizeRows = function ($rows) use ($columns, $emptyRow, $dateValue) {
                $result = [];
                foreach ((array)$rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $normalized = array_merge($emptyRow, $row);
                    foreach ($columns as $column) {
                        $attribute = $column['attribute'];
                        $value = ArrayHelper::getValue($row, $attribute, ArrayHelper::getValue($emptyRow, $attribute, ''));
                        if (ArrayHelper::getValue($column, 'type') == 'date') {
                            $value = $dateValue($value);
                        }
                        $normalized[$attribute] = $value;
                    }

                    $result[] = $normalized;
                }

                return $result;
            };

            $rows = $normalizeRows($rows);
            if (!$rows) {
                $rows[] = $emptyRow;
            }

            $renderRow = function ($index, array $row) use ($columns, $namePrefix) {
                $cells = '';
                $visibleAttributes = [];
                foreach ($columns as $column) {
                    $attribute = $column['attribute'];
                    $visibleAttributes[$attribute] = $attribute;
                    $type = ArrayHelper::getValue($column, 'type', 'text');
                    $inputName = $namePrefix.'['.$index.']['.$attribute.']';
                    $inputOptions = ArrayHelper::merge([
                        'class'        => 'form-control',
                        'autocomplete' => 'off',
                    ], (array)ArrayHelper::getValue($column, 'inputOptions', []));

                    if ($type == 'number') {
                        $inputOptions['step'] = ArrayHelper::getValue($inputOptions, 'step', 'any');
                    }

                    $cells .= Html::tag('td', Html::input($type, $inputName, ArrayHelper::getValue($row, $attribute, ''), $inputOptions));
                }

                $hiddenInputs = '';
                foreach ($row as $attribute => $value) {
                    if (isset($visibleAttributes[$attribute])) {
                        continue;
                    }
                    $hiddenInputs .= Html::hiddenInput($namePrefix.'['.$index.']['.$attribute.']', $value);
                }

                $cells .= Html::tag('td',
                    $hiddenInputs.Html::button('<i class="fa fa-times"></i>', [
                        'class' => 'btn btn-default sx-document-data-remove',
                        'type'  => 'button',
                        'title' => 'Удалить',
                    ]),
                    ['class' => 'sx-document-data-remove-cell']
                );

                return Html::tag('tr', $cells, ['class' => 'sx-document-data-row']);
            };

            $headCells = '';
            foreach ($columns as $column) {
                $headCells .= Html::tag('th', Html::encode($column['label']));
            }
            $headCells .= Html::tag('th', '');

            $body = '';
            foreach ($rows as $index => $row) {
                $body .= $renderRow($index, $row);
            }

            $template = $renderRow('__index__', $emptyRow);
            $addButton = Html::button('<i class="fa fa-plus"></i> Добавить', [
                'class' => 'btn btn-link sx-document-data-add',
                'type'  => 'button',
            ]);

            return Html::tag('div',
                Html::tag('label', Html::encode($label), ['class' => 'control-label']).
                Html::tag('div',
                    Html::tag('table',
                        Html::tag('thead', Html::tag('tr', $headCells)).
                        Html::tag('tbody', $body).
                        Html::tag('tfoot', Html::tag('tr', Html::tag('td', $addButton, ['colspan' => count($columns) + 1]))),
                        ['class' => 'table table-bordered table-condensed sx-document-data-table']
                    ),
                    ['class' => 'sx-document-data-rows', 'data-template' => $template]
                ),
                ['class' => 'form-group']
            );
        };

        $paymentRows = function ($section) use ($data) {
            return ShopDocument::normalizePaymentDocuments(
                ArrayHelper::getValue($data, $section.'.payment_documents', []),
                ArrayHelper::getValue($data, $section.'.payment_document', '')
            );
        };

        $advanceRows = function ($section) use ($data) {
            return ShopDocument::normalizeNumberDateDocuments(
                ArrayHelper::getValue($data, $section.'.advance_documents', []),
                ArrayHelper::getValue($data, $section.'.advance_document', '')
            );
        };

        $paymentColumns = [
            ['attribute' => 'number', 'label' => 'Номер'],
            ['attribute' => 'date', 'label' => 'Дата', 'type' => 'date'],
            ['attribute' => 'amount', 'label' => 'Сумма', 'type' => 'number'],
        ];
        $paymentEmptyRow = [
            'number'        => '',
            'date'          => '',
            'amount'        => '',
            'currency_code' => $model->currency_code ?: \Yii::$app->money->currencyCode,
        ];
        $advanceColumns = [
            ['attribute' => 'number', 'label' => 'Номер авансового счета-фактуры'],
            ['attribute' => 'date', 'label' => 'Дата', 'type' => 'date'],
        ];

        $this->view->registerCss(<<<CSS
.sx-document-data-table {
    margin-bottom: 0;
}
.sx-document-data-table th,
.sx-document-data-table td {
    vertical-align: middle !important;
}
.sx-document-data-remove-cell {
    width: 48px;
    text-align: center;
}
.sx-document-data-remove {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 50%;
}
.sx-document-data-add {
    padding-left: 0;
}
CSS
        );

        $this->view->registerJs(<<<JS
$("body").off("click.sxDocumentDataRowsAdd").on("click.sxDocumentDataRowsAdd", ".sx-document-data-add", function(e) {
    e.preventDefault();
    var wrapper = $(this).closest(".sx-document-data-rows");
    var body = wrapper.find("tbody");
    var index = body.find("tr").length;
    var template = wrapper.attr("data-template") || "";
    body.append(template.replace(/__index__/g, index));
});

$("body").off("click.sxDocumentDataRowsRemove").on("click.sxDocumentDataRowsRemove", ".sx-document-data-remove", function(e) {
    e.preventDefault();
    var body = $(this).closest("tbody");
    $(this).closest("tr").remove();
    body.find("tr").each(function(index) {
        $(this).find("input, select, textarea").each(function() {
            var name = $(this).attr("name");
            if (name) {
                $(this).attr("name", name.replace(/\\]\\[\\d+\\]\\[/, "][" + index + "]["));
            }
        });
    });
});
JS
        );

        $html = '';
        if ($model->type == ShopDocument::TYPE_UPD) {
            $html .= Html::tag('h4', 'УПД');
            $html .= '<div class="row">'.
                '<div class="col-md-3">'.$control('Статус УПД', ['upd', 'status'], ['default' => '2']).'</div>'.
                '<div class="col-md-3">'.$control('Идентификатор госконтракта', ['upd', 'state_contract_identifier']).'</div>'.
                '<div class="col-md-6">'.$control('Документ об отгрузке', ['upd', 'shipping_document']).'</div>'.
                '</div>'.
                $renderRowsField('Платежно-расчетные документы', ['upd', 'payment_documents'], $paymentColumns, $paymentRows('upd'), $paymentEmptyRow).
                $renderRowsField('Авансовые счета-фактуры', ['upd', 'advance_documents'], $advanceColumns, $advanceRows('upd'), ['number' => '', 'date' => '']).
                '<div class="row">'.
                '<div class="col-md-6">'.$control('Грузоотправитель и адрес', ['upd', 'shipper']).'</div>'.
                '<div class="col-md-6">'.$control('Грузополучатель и адрес', ['upd', 'consignee']).'</div>'.
                '</div>'.
                '<div class="row">'.
                '<div class="col-md-4">'.$control('Вид документа-основания', ['upd', 'base_document_name'], ['default' => 'Счет']).'</div>'.
                '<div class="col-md-4">'.$control('Номер документа-основания', ['upd', 'base_document_number']).'</div>'.
                '<div class="col-md-4">'.$control('Дата документа-основания', ['upd', 'base_document_date'], ['type' => 'date']).'</div>'.
                '</div>'.
                $control('Дополнительные сведения об основании передачи', ['upd', 'base_document'], ['type' => 'textarea']).
                $control('Данные о транспортировке и грузе', ['upd', 'transport_info'], ['type' => 'textarea']).
                '<div class="row">'.
                '<div class="col-md-6">'.$control('Иные сведения об отгрузке, передаче', ['upd', 'seller_other_info'], ['type' => 'textarea']).'</div>'.
                '<div class="col-md-6">'.$control('Иные сведения о получении, приемке', ['upd', 'buyer_other_info'], ['type' => 'textarea']).'</div>'.
                '</div>';
        } elseif ($model->type == ShopDocument::TYPE_WAYBILL) {
            $html .= Html::tag('h4', 'Накладная');
            $html .= '<div class="row">'.
                '<div class="col-md-6">'.$control('Грузоотправитель', ['waybill', 'shipper']).'</div>'.
                '<div class="col-md-6">'.$control('Адрес грузоотправителя', ['waybill', 'shipper_address']).'</div>'.
                '</div>'.
                '<div class="row">'.
                '<div class="col-md-6">'.$control('Грузополучатель', ['waybill', 'consignee']).'</div>'.
                '<div class="col-md-6">'.$control('Адрес грузополучателя', ['waybill', 'consignee_address']).'</div>'.
                '</div>'.
                '<div class="row">'.
                '<div class="col-md-4">'.$control('Транспортная накладная', ['waybill', 'transport_document']).'</div>'.
                '<div class="col-md-4">'.$control('Основание', ['waybill', 'base_document']).'</div>'.
                '<div class="col-md-4">'.$control('Вид операции', ['waybill', 'operation_type']).'</div>'.
                '</div>';
        } elseif ($model->type == ShopDocument::TYPE_INVOICE_FACTURE) {
            $html .= Html::tag('h4', 'Счет-фактура');
            $html .= '<div class="row">'.
                '<div class="col-md-3">'.$control('Исправление №', ['invoice_facture', 'correction_number']).'</div>'.
                '<div class="col-md-3">'.$control('Дата исправления', ['invoice_facture', 'correction_date']).'</div>'.
                '<div class="col-md-6">'.$control('Идентификатор госконтракта', ['invoice_facture', 'state_contract_identifier']).'</div>'.
                '</div>'.
                $renderRowsField('Платежно-расчетные документы', ['invoice_facture', 'payment_documents'], $paymentColumns, $paymentRows('invoice_facture'), $paymentEmptyRow).
                '<div class="row">'.
                '<div class="col-md-6">'.$control('Грузоотправитель', ['invoice_facture', 'shipper']).'</div>'.
                '<div class="col-md-6">'.$control('Адрес грузоотправителя', ['invoice_facture', 'shipper_address']).'</div>'.
                '</div>'.
                '<div class="row">'.
                '<div class="col-md-6">'.$control('Грузополучатель', ['invoice_facture', 'consignee']).'</div>'.
                '<div class="col-md-6">'.$control('Адрес грузополучателя', ['invoice_facture', 'consignee_address']).'</div>'.
                '</div>'.
                $renderRowsField('Авансовые счета-фактуры', ['invoice_facture', 'advance_documents'], $advanceColumns, $advanceRows('invoice_facture'), ['number' => '', 'date' => '']).
                $control('Документы об отгрузке', ['invoice_facture', 'shipment_documents_text'], ['type' => 'textarea', 'rows' => 3]);
        }

        if (!$html) {
            return '';
        }

        return Html::tag('div',
            Html::tag('label', 'Данные для печатных форм', ['class' => 'control-label']).
            Html::tag('div', $html, ['class' => 'well well-sm']),
            ['class' => 'sx-document-specific-data']
        );
    }

    public function renderDocumentItemsField(ShopDocument $model)
    {
        $rows = [];
        if ($model->documentItemsData !== null) {
            $rows = $model->documentItemsData;
        } elseif (!$model->isNewRecord && $model->documentItems) {
            foreach ($model->documentItems as $item) {
                $rows[] = $item->asArray();
            }
        }

        if (!$rows) {
            $rows[] = [
                'shop_product_id' => null,
                'name'            => '',
                'quantity'        => 1,
                'measure_name'    => 'шт',
                'price'           => 0,
                'discount_amount' => 0,
                'discount_value'  => null,
                'vat_name'        => 'Без НДС',
            ];
        }

        $measureOptions = $this->documentMeasureOptions();
        $productSearchUrl = Json::htmlEncode(Url::current([
            'sx-document-product-search' => 1,
            'q' => null,
        ]));
        $formName = $model->formName();
        $currencyCode = $model->currency_code ?: \Yii::$app->money->currencyCode;
        $model->currency_code = $currencyCode;
        $currencyCodeHtml = Html::encode($currencyCode);
        $currencySymbolHtml = Html::encode($this->documentCurrencySymbol($currencyCode));
        $discountAmount = Html::encode((float)$model->discount_amount);

        $body = '';
        foreach ($rows as $index => $row) {
            $body .= $this->renderDocumentItemRow($formName, $index, $row, $measureOptions, $model->type);
        }

        $emptyRow = Json::htmlEncode($this->renderDocumentItemRow($formName, '__index__', [
            'shop_product_id' => null,
            'name'            => '',
            'quantity'        => 1,
            'measure_name'    => 'шт',
            'price'           => 0,
            'discount_amount' => 0,
            'discount_value'  => null,
            'vat_name'        => 'Без НДС',
        ], $measureOptions, $model->type));

        $this->view->registerCss(<<<CSS
.sx-document-items table {
    width: calc(100% - 28px);
    table-layout: fixed;
    border-collapse: collapse;
}
.sx-document-items col.sx-document-item-name-col {
    width: 24rem;
}
.sx-document-items col.sx-document-item-small-col {
    width: 7.5rem;
}
.sx-document-items col.sx-document-item-discount-col {
    width: 7rem;
}
.sx-document-items col.sx-document-item-extra-col {
    width: 8.5rem;
}
.sx-document-items th,
.sx-document-items td {
    border: 1px solid #dee2e6;
    vertical-align: middle;
}
.sx-document-items tbody tr {
    position: relative;
}
.sx-document-items td:focus-within {
    box-shadow: inset 0 0 0 2px #80bdff;
}
.sx-document-items tbody tr.sx-document-item-name-editing > td {
    visibility: hidden;
}
.sx-document-items tbody tr.sx-document-item-name-editing > td:first-child {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    z-index: 20;
    display: block;
    visibility: visible;
    background: #fff;
    border: 2px solid #8f99a3;
    box-shadow: 0 0 0 1px rgba(143, 153, 163, 0.15);
}
.sx-document-items tbody tr.sx-document-item-name-editing > td:first-child:focus-within {
    box-shadow: 0 0 0 1px rgba(143, 153, 163, 0.15);
}
.sx-document-items tbody tr.sx-document-item-name-editing .sx-document-item-name-input {
    height: 100%;
}
.sx-document-items th {
    background: #f8f9fa;
    color: #666;
    font-weight: 600;
    padding: 10px 12px;
}
.sx-document-items input,
.sx-document-items select {
    width: 100%;
    min-width: 0;
    height: 44px;
    border: 0;
    border-radius: 0;
    box-shadow: none;
    background: transparent;
    padding: 0 10px;
}
.sx-document-items input:focus,
.sx-document-items select:focus {
    box-shadow: none;
    background: #fff;
    outline: 0;
}
.sx-document-items .sx-document-item-amount {
    position: relative;
    padding: 0 10px;
    white-space: nowrap;
}
.sx-document-items .sx-document-item-amount:focus-within {
    box-shadow: none;
}
.sx-document-items .sx-document-item-remove {
    position: absolute;
    right: -28px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 24px;
    padding: 0;
    border: 0;
    background: transparent;
    color: #adb5bd;
    line-height: 24px;
}
.sx-document-items .sx-document-item-remove:hover {
    color: #212529;
    background: transparent;
}
.sx-document-items .sx-document-item-remove:focus,
.sx-document-items .sx-document-item-remove:active {
    color: #adb5bd;
    background: transparent;
    box-shadow: none;
    outline: 0;
}
.sx-document-items .sx-document-item-add-cell {
    padding: 10px 12px;
}
.sx-document-items .sx-document-item-add {
    border: 0;
    background: transparent;
    color: #1a73e8;
    padding: 0;
    box-shadow: none;
    text-decoration: none;
    cursor: pointer;
}
.sx-document-items .sx-document-item-add:hover,
.sx-document-items .sx-document-item-add:focus,
.sx-document-items .sx-document-item-add:active {
    color: #1a73e8;
    background: transparent;
    box-shadow: none;
    outline: 0;
    text-decoration: none;
}
.sx-document-items .sx-document-item-discount-open {
    width: 100%;
    height: 44px;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: #495057;
    text-align: left;
    padding: 0 10px;
    box-shadow: none;
}
.sx-document-items .sx-document-item-discount-open.is-empty {
    color: #adb5bd;
}
.sx-document-items .sx-document-item-discount-open:hover,
.sx-document-items .sx-document-item-discount-open:focus,
.sx-document-items .sx-document-item-discount-open:active {
    background: #fff;
    color: #212529;
    box-shadow: none;
    outline: 0;
}
.sx-document-items .sx-document-summary {
    width: 320px;
    max-width: 100%;
    margin: 20px 0 24px auto;
}
.sx-document-items .sx-document-summary-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: baseline;
    gap: 20px;
    margin-bottom: 10px;
}
.sx-document-items .sx-document-summary-label {
    margin: 0;
    color: #343a40;
    font-weight: 400;
}
.sx-document-items .sx-document-discount {
    position: relative;
}
.sx-document-items .sx-document-discount > div {
    position: relative;
}
.sx-document-items .sx-document-discount-value {
    display: none;
}
.sx-document-items .sx-document-discount-open {
    border: 0;
    border-bottom: 1px dashed #777;
    background: transparent;
    color: #343a40;
    padding: 0 0 2px;
    box-shadow: none;
    text-align: right;
    font-weight: 600;
    cursor: pointer;
}
.sx-document-items .sx-document-discount-open:hover,
.sx-document-items .sx-document-discount-open:focus,
.sx-document-items .sx-document-discount-open:active {
    border-bottom-color: #212529;
    background: transparent;
    box-shadow: none;
    outline: 0;
}
.sx-document-items .sx-document-discount-open.is-empty {
    border-bottom-color: transparent;
    color: #adb5bd;
    font-weight: 400;
}
.sx-document-items .sx-document-total {
    margin-top: 18px;
    text-align: right;
    font-size: 20px;
    font-weight: 600;
}
.sx-document-items-label {
    display: block;
    margin-bottom: 6px;
    color: #555;
    font-weight: 600;
}
.sx-document-items-wide {
    overflow-x: auto;
    padding-right: 28px;
}
.sx-document-items-wide table {
    min-width: 100rem;
}
.sx-document-discount-popover {
    position: absolute;
    right: 0;
    top: auto;
    bottom: 36px;
    z-index: 3000;
    display: none;
    width: 220px;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 12px 35px rgba(0, 0, 0, .18);
    overflow: hidden;
    text-align: left;
}
.sx-document-discount-popover-title {
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}
.sx-document-discount-popover-body {
    padding: 12px;
}
.sx-document-discount-mode {
    display: grid;
    grid-template-columns: 1fr 1fr;
    margin-bottom: 12px;
}
.sx-document-discount-mode button {
    height: 34px;
    border: 0;
    background: #dee2e6;
    color: #495057;
    cursor: pointer;
    outline: 0;
    box-shadow: none;
}
.sx-document-discount-mode button.is-active {
    background: #2b8bdc;
    color: #fff;
}
.sx-document-discount-mode button:hover,
.sx-document-discount-mode button:focus,
.sx-document-discount-mode button:active {
    outline: 0;
    box-shadow: inset 0 0 0 1px rgba(43, 139, 220, .35);
}
.sx-document-discount-popover-input {
    width: 100% !important;
    display: block !important;
    height: 38px;
    margin-bottom: 6px;
    padding: 7px 10px;
    background: #fff;
    border: 1px solid #ced4da !important;
    border-radius: 4px;
    color: #495057;
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .04);
    box-sizing: border-box;
}
.sx-document-discount-popover-input:focus {
    border-color: #80bdff !important;
    outline: 0;
    box-shadow: 0 0 0 2px rgba(0, 123, 255, .12);
}
.sx-document-discount-popover-hint {
    min-height: 18px;
    color: #868e96;
    font-size: 13px;
}
.sx-document-discount-popover-apply {
    margin-top: 12px;
}
.sx-document-discount-modal-backdrop {
    position: fixed;
    z-index: 4000;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(0, 0, 0, .45);
}
.sx-document-discount-modal {
    width: 520px;
    max-width: 100%;
    background: #fff;
    border-radius: 10px;
    padding: 24px;
    box-shadow: 0 18px 60px rgba(0, 0, 0, .25);
}
.sx-document-discount-modal-title {
    font-size: 20px;
    font-weight: 600;
    line-height: 1.25;
    margin-bottom: 6px;
}
.sx-document-discount-modal-meta {
    color: #868e96;
    margin-bottom: 24px;
}
.sx-document-discount-modal-field {
    margin-bottom: 16px;
}
.sx-document-discount-modal-field label {
    display: block;
    margin-bottom: 7px;
    font-weight: 600;
}
.sx-document-discount-modal-control {
    display: flex;
}
.sx-document-discount-modal input {
    width: 100%;
    height: 46px;
    border: 1px solid #d8dee4;
    border-radius: 4px;
    background: #fff;
    padding: 0 12px;
    box-shadow: none;
}
.sx-document-discount-modal-control input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.sx-document-discount-modal-addon {
    min-width: 46px;
    height: 46px;
    border: 1px solid #d8dee4;
    border-left: 0;
    border-radius: 0 4px 4px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    background: #f8f9fa;
}
.sx-document-discount-modal-qty {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr) 46px;
    gap: 10px;
}
.sx-document-discount-modal-qty button {
    height: 46px;
    border-radius: 4px;
    background: #fff;
    font-size: 22px;
    font-weight: 600;
    cursor: pointer;
    outline: 0;
    box-shadow: none;
}
.sx-document-discount-modal-qty-minus {
    border: 1px solid #ff3b30;
    color: #ff3b30;
}
.sx-document-discount-modal-qty-plus {
    border: 1px solid #22c55e;
    color: #22c55e;
}
.sx-document-discount-modal-qty button:hover,
.sx-document-discount-modal-qty button:focus,
.sx-document-discount-modal-qty button:active {
    outline: 0;
    box-shadow: 0 0 0 2px rgba(43, 139, 220, .12);
}
.sx-document-discount-modal-discounts {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 18px;
}
.sx-document-discount-modal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    margin-top: 24px;
}
.sx-document-discount-modal-total {
    color: #868e96;
    font-size: 22px;
    font-weight: 600;
}
.sx-document-discount-modal-total strong {
    color: #495057;
}
.sx-document-discount-modal-old-total {
    margin-left: 8px;
    color: #adb5bd;
    text-decoration: line-through;
}
.sx-document-discount-modal-close {
    min-width: 120px;
    height: 46px;
}
.sx-document-product-suggestions {
    position: absolute;
    z-index: 3000;
    display: none;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}
.sx-document-product-suggestion {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
}
.sx-document-product-suggestion-main {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.sx-document-product-suggestion-image {
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    border-radius: 4px;
    background: #f1f3f5;
    overflow: hidden;
    color: #adb5bd;
    text-align: center;
    line-height: 36px;
}
.sx-document-product-suggestion-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.sx-document-product-suggestion-text {
    min-width: 0;
}
.sx-document-product-suggestion:last-child {
    border-bottom: 0;
}
.sx-document-product-suggestion:hover,
.sx-document-product-suggestion.is-active {
    background: #f8f9fa;
}
.sx-document-product-suggestion-name {
    color: #343a40;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sx-document-product-suggestion-meta {
    margin-top: 3px;
    color: #8a8f98;
    font-size: 12px;
}
.sx-document-product-suggestion-price {
    flex: 0 0 auto;
    color: #343a40;
    font-weight: 600;
    white-space: nowrap;
}
.sx-document-product-suggestion-empty {
    padding: 12px;
    color: #8a8f98;
}
CSS
        );

        $this->view->registerJs(<<<JS
(function() {
    var productSearchUrl = {$productSearchUrl};
    var wrapper = $(".sx-document-items");
    if (!wrapper.length || wrapper.data("sx-document-ready")) {
        return;
    }
    wrapper.data("sx-document-ready", true);
    var table = wrapper.find("table");
    var emptyRow = {$emptyRow};
    var nextIndex = table.find("tbody tr").length;

    function numberValue(value) {
        value = (value || "").toString().replace(/\s+/g, "").replace(",", ".");
        var result = parseFloat(value);
        return isNaN(result) ? 0 : result;
    }

    function formatMoney(value) {
        var fixed = numberValue(value).toFixed(2);
        if (fixed.slice(-3) === ".00") {
            fixed = fixed.slice(0, -3);
        }
        var parts = fixed.split(".");
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        return parts.join(",");
    }

    function formatNumberInput(value) {
        return numberValue(value).toFixed(6).replace(/\.?0+$/, "");
    }

    function parseDiscount(raw, baseAmount) {
        raw = (raw || "").toString().replace(/\s+/g, "").replace(",", ".");
        baseAmount = Math.max(numberValue(baseAmount), 0);
        if (!raw) {
            return 0;
        }
        var amount;
        if (raw.indexOf("%") !== -1) {
            amount = baseAmount * numberValue(raw.replace("%", "")) / 100;
        } else {
            amount = numberValue(raw);
        }
        amount = Math.max(amount, 0);
        return Math.min(amount, baseAmount);
    }

    function parseItemDiscount(raw, unitPrice, quantity) {
        raw = (raw || "").toString().replace(/\s+/g, "").replace(",", ".");
        unitPrice = Math.max(numberValue(unitPrice), 0);
        quantity = Math.max(numberValue(quantity), 0);
        var baseAmount = unitPrice * quantity;
        if (!raw || baseAmount <= 0) {
            return 0;
        }

        var amount = raw.indexOf("%") !== -1
            ? baseAmount * numberValue(raw.replace("%", "")) / 100
            : numberValue(raw) * quantity;

        amount = Math.max(amount, 0);
        return Math.min(amount, baseAmount);
    }

    function discountPercent(amount, baseAmount) {
        baseAmount = Math.max(numberValue(baseAmount), 0);
        if (!baseAmount) {
            return 0;
        }
        return Math.min(Math.max(numberValue(amount) * 100 / baseAmount, 0), 100);
    }

    function formatPercent(value) {
        var fixed = numberValue(value).toFixed(2).replace(".", ",");
        return fixed.replace(/,00$/, "").replace(/(\,\d)0$/, "$1");
    }

    function discountLabel(raw, amount, baseAmount) {
        raw = (raw || "").toString().trim();
        amount = numberValue(amount);
        if (amount <= 0) {
            return "0";
        }
        if (raw.indexOf("%") !== -1) {
            return formatPercent(raw.replace("%", "")) + "%";
        }
        return formatMoney(amount) + " {$currencySymbolHtml}";
    }

    function updateRowDiscountButton(row, discountAmount, displayRaw, displayAmount) {
        var baseAmount = numberValue(row.find(".sx-document-item-quantity").val()) * numberValue(row.find(".sx-document-item-price").val());
        var raw = displayRaw !== undefined ? displayRaw : row.find(".sx-document-item-discount-value").val();
        var amount = displayAmount !== undefined ? displayAmount : discountAmount;
        row.find(".sx-document-item-discount-open")
            .text(discountLabel(raw, amount, baseAmount))
            .toggleClass("is-empty", numberValue(amount) <= 0);
    }

    function setRowDiscount(row, rawValue) {
        var discountAmount = parseItemDiscount(rawValue, row.find(".sx-document-item-price").val(), row.find(".sx-document-item-quantity").val());
        row.find(".sx-document-item-discount-value").val(rawValue || "");
        row.find(".sx-document-item-discount-amount").val(discountAmount.toFixed(4));
        updateRowDiscountButton(row, discountAmount);
        return discountAmount;
    }

    function updateDocumentDiscountButton(grossSubtotal, totalDiscount) {
        var percent = discountPercent(totalDiscount, grossSubtotal);
        var label = totalDiscount > 0
            ? "(" + formatPercent(percent) + "%) " + formatMoney(totalDiscount) + " {$currencySymbolHtml}"
            : "0 {$currencySymbolHtml}";
        wrapper.find(".sx-document-discount-open")
            .text(label)
            .toggleClass("is-empty", numberValue(totalDiscount) <= 0);
    }

    function aggregateDiscountRaw(rows, totalDiscount, grossSubtotal) {
        totalDiscount = numberValue(totalDiscount);
        grossSubtotal = numberValue(grossSubtotal);
        if (totalDiscount <= 0 || grossSubtotal <= 0) {
            return "";
        }

        var commonPercent = null;
        var isCommonPercent = true;
        rows.forEach(function(item) {
            if (item.baseAmount <= 0) {
                return;
            }

            var itemPercent = discountPercent(item.discountAmount, item.baseAmount);
            if (commonPercent === null) {
                commonPercent = itemPercent;
            } else if (Math.abs(commonPercent - itemPercent) > 0.01) {
                isCommonPercent = false;
            }
        });

        if (isCommonPercent && commonPercent > 0) {
            return formatPercent(commonPercent) + "%";
        }

        return totalDiscount.toFixed(2).replace(".", ",");
    }

    function renumber() {
        table.find("tbody tr").each(function(index) {
            $(this).find(":input").each(function() {
                var input = $(this);
                input.attr("name", input.attr("name").replace(/documentItemsData\\]\\[[^\\]]+\\]/, "documentItemsData][" + index + "]"));
            });
        });
    }

    function recalcDocumentItems() {
        var grossSubtotal = 0;
        var totalDiscount = 0;
        var rows = [];

        table.find("tbody tr").each(function() {
            var row = $(this);
            var quantity = numberValue(row.find(".sx-document-item-quantity").val());
            var price = numberValue(row.find(".sx-document-item-price").val());
            var baseAmount = quantity * price;
            var discountAmount = parseItemDiscount(row.find(".sx-document-item-discount-value").val(), price, quantity);
            var amount = Math.max(baseAmount - discountAmount, 0);

            grossSubtotal += baseAmount;
            totalDiscount += discountAmount;
            rows.push({
                row: row,
                baseAmount: baseAmount,
                discountAmount: discountAmount,
                amount: amount
            });

            row.find(".sx-document-item-discount-amount").val(discountAmount.toFixed(4));
            updateRowDiscountButton(row, discountAmount);
            row.find(".sx-document-item-amount-value").text(formatMoney(amount));
        });

        var total = Math.max(grossSubtotal - totalDiscount, 0);
        var aggregateRaw = aggregateDiscountRaw(rows, totalDiscount, grossSubtotal);
        $("#shopdocument-discount_amount").val(totalDiscount.toFixed(4));
        $("#shopdocument-amount").val(total.toFixed(4));
        wrapper.find(".sx-document-discount-value").val(aggregateRaw);
        wrapper.find(".sx-document-subtotal-value").text(formatMoney(grossSubtotal) + " {$currencySymbolHtml}");
        updateDocumentDiscountButton(grossSubtotal, totalDiscount);
        wrapper.find(".sx-document-discount-result").text("");
        wrapper.find(".sx-document-total-value").text(formatMoney(total));
    }

    function htmlEncode(value) {
        return $("<div/>").text(value || "").html();
    }

    function suggestions() {
        var dropdown = $(".sx-document-product-suggestions");
        if (!dropdown.length) {
            dropdown = $('<div class="sx-document-product-suggestions"></div>').appendTo("body");
        }
        return dropdown;
    }

    function hideSuggestions() {
        suggestions().hide().empty().removeData("input");
        $(".sx-document-item-name-editing").removeClass("sx-document-item-name-editing");
    }

    function positionSuggestions(input) {
        var inputOffset = input.offset();
        var tableOffset = table.offset();
        suggestions().css({
            left: tableOffset.left,
            top: inputOffset.top + input.outerHeight(),
            width: table.outerWidth()
        });
    }

    function setNameEditing(input, isEditing) {
        input.closest("tr").toggleClass("sx-document-item-name-editing", isEditing);
    }

    function renderProductSuggestions(input, items) {
        var dropdown = suggestions();
        dropdown.empty().data("input", input);
        positionSuggestions(input);

        if (!items.length) {
            dropdown.append('<div class="sx-document-product-suggestion-empty">Ничего не найдено</div>').show();
            return;
        }

        items.forEach(function(item) {
            var meta = [];
            if (item.category) {
                meta.push(item.category);
            }
            if (item.brand) {
                meta.push(item.brand);
            }

            $('<div class="sx-document-product-suggestion"></div>')
                .data("product", item)
                .append(
                    '<div class="sx-document-product-suggestion-main">' +
                        '<div class="sx-document-product-suggestion-image">' + (item.image ? '<img src="' + htmlEncode(item.image) + '" alt="" />' : '<i class="fa fa-image"></i>') + '</div>' +
                        '<div class="sx-document-product-suggestion-text">' +
                            '<div class="sx-document-product-suggestion-name">' + htmlEncode(item.name) + '</div>' +
                            (meta.length ? '<div class="sx-document-product-suggestion-meta">' + htmlEncode(meta.join(" - ")) + '</div>' : '') +
                        '</div>' +
                    '</div>' +
                    '<div class="sx-document-product-suggestion-price">' + htmlEncode(item.priceText) + '</div>'
                )
                .appendTo(dropdown);
        });

        dropdown.show();
    }

    function selectProductSuggestion(input, product) {
        var row = input.closest("tr");
        input.val(product.name);
        row.find(".sx-document-item-product-id").val(product.id);
        row.find(".sx-document-item-price").val(product.price);
        row.find(".sx-document-item-measure").val(product.measure);
        row.find(".sx-document-item-discount-value").val("");
        row.find(".sx-document-item-discount-amount").val("0");
        var extraData = product.extraData || {};
        row.find("input[name$='[extra_data][code]']").val(extraData.code || "");
        row.find("input[name$='[extra_data][country_code]']").val(extraData.country_code || "");
        row.find("input[name$='[extra_data][country_name]']").val(extraData.country_name || "");
        row.find("input[name$='[extra_data][declaration_number]']").val(extraData.declaration_number || "");
        hideSuggestions();
        recalcDocumentItems();
    }

    function documentTotals() {
        var grossSubtotal = 0;
        var itemsDiscount = 0;
        table.find("tbody tr").each(function() {
            var row = $(this);
            var qty = numberValue(row.find(".sx-document-item-quantity").val());
            var price = numberValue(row.find(".sx-document-item-price").val());
            var baseAmount = qty * price;
            grossSubtotal += baseAmount;
            itemsDiscount += parseItemDiscount(row.find(".sx-document-item-discount-value").val(), price, qty);
        });

        return {
            grossSubtotal: grossSubtotal,
            itemsDiscount: itemsDiscount,
            netSubtotal: grossSubtotal - itemsDiscount
        };
    }

    function documentDiscountPopover() {
        return wrapper.find(".sx-document-discount-popover");
    }

    function openDocumentDiscountPopover() {
        var popover = documentDiscountPopover();
        var totals = documentTotals();
        var subtotal = totals.grossSubtotal;
        var raw = wrapper.find(".sx-document-discount-value").val() || "";
        var amount = parseDiscount(raw, subtotal);
        var isPercent = raw.indexOf("%") !== -1;
        popover.data("mode", isPercent ? "percent" : "money");
        popover.find(".sx-document-discount-mode button").removeClass("is-active");
        popover.find('[data-mode="' + (isPercent ? "percent" : "money") + '"]').addClass("is-active");
        popover.find(".sx-document-discount-popover-input").val(isPercent ? formatPercent(raw.replace("%", "")) : (amount ? formatMoney(amount).replace(/\s/g, "") : ""));
        popover.find(".sx-document-discount-popover-hint").text(amount ? formatPercent(discountPercent(amount, subtotal)) + "% / " + formatMoney(amount) + " {$currencySymbolHtml}" : "");
        popover.show().find(".sx-document-discount-popover-input").focus().select();
    }

    function applyDocumentDiscountPopover() {
        var popover = documentDiscountPopover();
        var mode = popover.data("mode") || "money";
        var value = numberValue(popover.find(".sx-document-discount-popover-input").val());
        var rawValue = value ? (mode === "percent" ? formatPercent(value) + "%" : value.toFixed(2).replace(".", ",")) : "";
        var totals = documentTotals();
        var amount = parseDiscount(rawValue, totals.grossSubtotal);
        var rows = [];

        table.find("tbody tr").each(function() {
            var row = $(this);
            var baseAmount = numberValue(row.find(".sx-document-item-quantity").val()) * numberValue(row.find(".sx-document-item-price").val());
            rows.push({
                row: row,
                baseAmount: baseAmount
            });
        });

        if (mode === "percent") {
            var percentRaw = amount ? formatPercent(discountPercent(amount, totals.grossSubtotal)) + "%" : "";
            rows.forEach(function(item) {
                setRowDiscount(item.row, percentRaw);
            });
            wrapper.find(".sx-document-discount-value").val(percentRaw);
        } else {
            var distributed = 0;
            rows.forEach(function(item, index) {
                var itemAmount = 0;
                if (amount > 0 && totals.grossSubtotal > 0 && item.baseAmount > 0) {
                    itemAmount = index === rows.length - 1
                        ? amount - distributed
                        : Math.round((amount * item.baseAmount / totals.grossSubtotal) * 10000) / 10000;
                }

                distributed += itemAmount;
                var qty = numberValue(item.row.find(".sx-document-item-quantity").val()) || 1;
                var unitAmount = itemAmount / qty;
                setRowDiscount(item.row, unitAmount ? unitAmount.toFixed(4).replace(".", ",") : "");
            });
            wrapper.find(".sx-document-discount-value").val(rawValue);
        }

        var afterTotals = documentTotals();
        popover.find(".sx-document-discount-popover-hint").text(amount ? formatPercent(discountPercent(afterTotals.itemsDiscount, afterTotals.grossSubtotal)) + "% / " + formatMoney(afterTotals.itemsDiscount) + " {$currencySymbolHtml}" : "");
        recalcDocumentItems();
    }

    function itemDiscountModal() {
        return $(".sx-document-discount-modal-backdrop");
    }

    function activeDiscountRow() {
        return itemDiscountModal().data("row");
    }

    function syncItemDiscountModalFromRow(row) {
        var modal = itemDiscountModal();
        var name = row.find(".sx-document-item-name-input").val() || "Позиция документа";
        var price = numberValue(row.find(".sx-document-item-price").val());
        var qty = numberValue(row.find(".sx-document-item-quantity").val()) || 1;
        var baseAmount = price * qty;
        var raw = row.find(".sx-document-item-discount-value").val() || "";
        var discountAmount = parseItemDiscount(raw, price, qty);
        var percent = discountPercent(discountAmount, baseAmount);
        var total = baseAmount - discountAmount;
        var unitDiscount = raw.indexOf("%") === -1 ? numberValue(raw) : (qty > 0 ? discountAmount / qty : 0);

        modal.find(".sx-document-discount-modal-title").text(name);
        modal.find(".sx-document-modal-price").val(price);
        modal.find(".sx-document-modal-quantity").val(qty);
        modal.find(".sx-document-modal-discount-percent").val(percent ? formatPercent(percent) : "");
        modal.find(".sx-document-modal-discount-money").val(unitDiscount ? formatMoney(unitDiscount).replace(/\s/g, "") : "");
        modal.find(".sx-document-discount-modal-total strong").text(formatMoney(total));
        modal.find(".sx-document-discount-modal-total-currency").text(" {$currencySymbolHtml}");
        modal.find(".sx-document-discount-modal-old-total").text(discountAmount > 0 ? formatMoney(baseAmount) + " {$currencySymbolHtml}" : "");
    }

    function openItemDiscountModal(row) {
        var modal = itemDiscountModal();
        modal.data("row", row).css("display", "flex");
        syncItemDiscountModalFromRow(row);
        modal.find(".sx-document-modal-discount-percent").focus().select();
    }

    function closeItemDiscountModal() {
        itemDiscountModal().hide().removeData("row");
    }

    function applyItemDiscountFromModal(mode) {
        var row = activeDiscountRow();
        if (!row || !row.length) {
            return;
        }

        var modal = itemDiscountModal();
        var price = numberValue(modal.find(".sx-document-modal-price").val());
        var qty = numberValue(modal.find(".sx-document-modal-quantity").val()) || 1;
        row.find(".sx-document-item-price").val(price);
        row.find(".sx-document-item-quantity").val(qty);

        var baseAmount = price * qty;
        var rawValue;
        if (mode === "money") {
            var unitMoney = Math.min(Math.max(numberValue(modal.find(".sx-document-modal-discount-money").val()), 0), price);
            var money = unitMoney * qty;
            rawValue = unitMoney ? unitMoney.toFixed(2).replace(".", ",") : "";
            modal.find(".sx-document-modal-discount-percent").val(money ? formatPercent(discountPercent(money, baseAmount)) : "");
        } else {
            var percent = numberValue(modal.find(".sx-document-modal-discount-percent").val());
            var amount = parseDiscount(percent ? percent + "%" : "", baseAmount);
            rawValue = percent ? formatPercent(percent) + "%" : "";
            modal.find(".sx-document-modal-discount-money").val(amount && qty ? formatMoney(amount / qty).replace(/\s/g, "") : "");
        }

        setRowDiscount(row, rawValue);
        recalcDocumentItems();
        syncItemDiscountModalFromRow(row);
    }

    $("body").on("input focus", ".sx-document-item-name-input", function(e) {
        var input = $(this);
        var value = input.val();
        setNameEditing(input, true);
        if (e.type === "input") {
            input.closest("tr").find(".sx-document-item-product-id").val("");
        }

        clearTimeout(input.data("searchTimeout"));
        input.data("searchTimeout", setTimeout(function() {
            $.ajax({
                url: productSearchUrl,
                dataType: "json",
                data: {q: value},
                success: function(data) {
                    if (document.activeElement !== input[0]) {
                        return;
                    }
                    renderProductSuggestions(input, data.items || []);
                }
            });
        }, 180));
    });

    $("body").on("blur", ".sx-document-item-name-input", function() {
        var input = $(this);
        setTimeout(function() {
            if (!suggestions().is(":visible")) {
                setNameEditing(input, false);
            }
        }, 120);
    });

    $("body").on("mousedown", ".sx-document-product-suggestion", function(e) {
        e.preventDefault();
        var input = suggestions().data("input");
        if (input) {
            selectProductSuggestion(input, $(this).data("product"));
        }
    });

    $("body").on("keydown", ".sx-document-item-name-input", function(e) {
        var dropdown = suggestions();
        if (!dropdown.is(":visible")) {
            return;
        }

        var items = dropdown.find(".sx-document-product-suggestion");
        if (!items.length) {
            if (e.key === "Escape") {
                hideSuggestions();
            }
            return;
        }

        var active = items.filter(".is-active");
        var index = active.length ? items.index(active) : -1;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            items.removeClass("is-active").eq(Math.min(index + 1, items.length - 1)).addClass("is-active");
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            items.removeClass("is-active").eq(Math.max(index - 1, 0)).addClass("is-active");
        } else if (e.key === "Enter" && active.length) {
            e.preventDefault();
            selectProductSuggestion($(this), active.data("product"));
        } else if (e.key === "Escape") {
            hideSuggestions();
        }
    });

    table.on("input change", ".sx-document-item-quantity, .sx-document-item-price, .sx-document-item-discount-value", recalcDocumentItems);
    table.on("click", ".sx-document-item-remove", function() {
        if (table.find("tbody tr").length > 1) {
            $(this).closest("tr").remove();
            renumber();
        } else {
            var row = $(this).closest("tr");
            row.find("input[type=text], input[type=number], input[type=hidden]").val("");
            row.find(".sx-document-item-quantity").val("1");
            row.find(".sx-document-item-price").val("0");
            row.find(".sx-document-item-discount-amount").val("0");
            row.find(".sx-document-item-measure").val("шт");
        }
        recalcDocumentItems();
    });
    wrapper.on("click", ".sx-document-item-add", function(e) {
        e.preventDefault();
        table.find("tbody").append(emptyRow.replace(/__index__/g, nextIndex++));
        recalcDocumentItems();
    });

    $("body").on("keydown", ".sx-document-item-price, .sx-document-item-quantity, .sx-document-modal-price, .sx-document-modal-quantity", function(e) {
        if (e.key !== "ArrowUp" && e.key !== "ArrowDown") {
            return;
        }

        e.preventDefault();
        var input = $(this);
        var value = numberValue(input.val());
        var nextValue = value + (e.key === "ArrowUp" ? 1 : -1);
        if (input.is(".sx-document-item-quantity, .sx-document-modal-quantity")) {
            nextValue = Math.max(nextValue, 1);
        } else {
            nextValue = Math.max(nextValue, 0);
        }
        input.val(formatNumberInput(nextValue)).trigger("input").trigger("change");
    });

    $("body").on("click", ".sx-document-item-discount-open", function(e) {
        e.preventDefault();
        openItemDiscountModal($(this).closest("tr"));
        return false;
    });

    $("body").on("click", ".sx-document-discount-modal-backdrop", function(e) {
        if ($(e.target).is(".sx-document-discount-modal-backdrop")) {
            closeItemDiscountModal();
        }
    });

    $("body").on("click", ".sx-document-discount-modal-close", function() {
        closeItemDiscountModal();
        return false;
    });

    $("body").on("input change", ".sx-document-modal-price, .sx-document-modal-quantity, .sx-document-modal-discount-percent", function() {
        applyItemDiscountFromModal("percent");
    });

    $("body").on("input change", ".sx-document-modal-discount-money", function() {
        applyItemDiscountFromModal("money");
    });

    $("body").on("click", ".sx-document-discount-modal-qty-minus, .sx-document-discount-modal-qty-plus", function() {
        var input = $(".sx-document-modal-quantity");
        var delta = $(this).hasClass("sx-document-discount-modal-qty-plus") ? 1 : -1;
        input.val(Math.max(numberValue(input.val()) + delta, 1));
        applyItemDiscountFromModal("percent");
        return false;
    });

    wrapper.on("click", ".sx-document-discount-open", function(e) {
        e.preventDefault();
        e.stopPropagation();
        openDocumentDiscountPopover();
        return false;
    });

    wrapper.on("click", ".sx-document-discount-mode button", function() {
        var mode = $(this).data("mode");
        var popover = documentDiscountPopover();
        var totals = documentTotals();
        var currentAmount = parseDiscount(wrapper.find(".sx-document-discount-value").val(), totals.grossSubtotal);
        var nextValue = "";
        if (currentAmount > 0) {
            nextValue = mode === "percent"
                ? formatPercent(discountPercent(currentAmount, totals.grossSubtotal))
                : formatMoney(currentAmount).replace(/\s/g, "");
        }
        popover.data("mode", mode);
        popover.find(".sx-document-discount-mode button").removeClass("is-active");
        $(this).addClass("is-active");
        popover.find(".sx-document-discount-popover-input").val(nextValue).focus().select();
        return false;
    });

    wrapper.on("input change", ".sx-document-discount-popover-input", applyDocumentDiscountPopover);

    wrapper.on("click", ".sx-document-discount-popover-apply", function() {
        applyDocumentDiscountPopover();
        documentDiscountPopover().hide();
        return false;
    });

    $("body").on("click", function(e) {
        if (!$(e.target).closest(".sx-document-product-suggestions, .sx-document-item-name-input").length) {
            hideSuggestions();
        }
        if (!$(e.target).closest(".sx-document-discount-popover, .sx-document-discount-open").length) {
            documentDiscountPopover().hide();
        }
    });

    recalcDocumentItems();
})();
JS
        );

        $extraHeaders = '';
        $extraCols = '';
        $extraCount = 0;
        if ($model->type == ShopDocument::TYPE_WAYBILL) {
            $extraHeaders = '<th>Упаковка</th>'.
                '<th>В месте</th>'.
                '<th>Мест/штук</th>'.
                '<th>Брутто</th>'.
                '<th>Нетто</th>';
            $extraCount = 5;
        } elseif (in_array($model->type, [ShopDocument::TYPE_UPD, ShopDocument::TYPE_INVOICE_FACTURE], true)) {
            $extraHeaders = '<th>Код</th>'.
                '<th>Код страны</th>'.
                '<th>Страна</th>'.
                '<th>Рег. № декларации</th>';
            $extraCount = 4;
        }

        for ($i = 0; $i < $extraCount; $i++) {
            $extraCols .= '<col class="sx-document-item-extra-col" />';
        }

        $colspan = 7 + $extraCount;
        $wideClass = $extraCount ? ' sx-document-items-wide' : '';

        return '<div class="col-12 sx-document-items form-group'.$wideClass.'">'.
            '<label class="control-label sx-document-items-label">Позиции документа</label>'.
            '<input type="hidden" id="shopdocument-amount" name="'.$formName.'[amount]" value="'.Html::encode((float)$model->amount).'" />'.
            '<input type="hidden" id="shopdocument-discount_amount" name="'.$formName.'[discount_amount]" value="'.$discountAmount.'" />'.
            '<input type="hidden" id="shopdocument-currency_code" name="'.$formName.'[currency_code]" value="'.$currencyCodeHtml.'" />'.
            '<table>'.
            '<colgroup>'.
            '<col class="sx-document-item-name-col" />'.
            '<col class="sx-document-item-small-col" />'.
            '<col class="sx-document-item-small-col" />'.
            '<col class="sx-document-item-small-col" />'.
            '<col class="sx-document-item-small-col" />'.
            '<col class="sx-document-item-discount-col" />'.
            $extraCols.
            '<col class="sx-document-item-small-col" />'.
            '</colgroup>'.
            '<thead><tr>'.
            '<th>Наименование</th>'.
            '<th>Цена, '.$currencySymbolHtml.'</th>'.
            '<th>Кол-во</th>'.
            '<th>Ед. изм.</th>'.
            '<th>НДС</th>'.
            '<th>Скидка</th>'.
            $extraHeaders.
            '<th>Сумма, '.$currencySymbolHtml.'</th>'.
            '</tr></thead>'.
            '<tbody>'.$body.'</tbody>'.
            '<tfoot><tr><td colspan="'.$colspan.'" class="sx-document-item-add-cell"><a href="#" class="sx-document-item-add"><i class="fa fa-plus"></i> Добавить товар или услугу</a></td></tr></tfoot>'.
            '</table>'.
            '<div class="sx-document-summary">'.
            '<div class="sx-document-summary-row"><span class="sx-document-summary-label">Предытог</span><span class="sx-document-subtotal-value">0 '.$currencySymbolHtml.'</span></div>'.
            '<div class="sx-document-summary-row sx-document-discount"><span class="sx-document-summary-label">Скидка</span><div><input type="hidden" class="sx-document-discount-value" value="" /><button type="button" class="sx-document-discount-open is-empty">0 '.$currencySymbolHtml.'</button><div class="sx-document-discount-popover"><div class="sx-document-discount-popover-title">Размер скидки</div><div class="sx-document-discount-popover-body"><div class="sx-document-discount-mode"><button type="button" data-mode="money" class="is-active">'.$currencySymbolHtml.'</button><button type="button" data-mode="percent">%</button></div><input type="text" class="sx-document-discount-popover-input" value="" autocomplete="off" /><div class="sx-document-discount-popover-hint"></div><button type="button" class="btn btn-primary btn-block sx-document-discount-popover-apply">Готово</button></div></div></div></div>'.
            '<div class="sx-document-total">Итого: <span class="sx-document-total-value">0</span> '.$currencySymbolHtml.'</div>'.
            '</div>'.
            '<div class="sx-document-discount-modal-backdrop">'.
            '<div class="sx-document-discount-modal">'.
            '<div class="sx-document-discount-modal-title"></div>'.
            '<div class="sx-document-discount-modal-meta">Скидка по позиции документа</div>'.
            '<div class="sx-document-discount-modal-field">'.
            '<label>Цена</label>'.
            '<div class="sx-document-discount-modal-control">'.
            '<input type="number" step="any" class="sx-document-modal-price" value="0" />'.
            '<span class="sx-document-discount-modal-addon">'.$currencySymbolHtml.'</span>'.
            '</div>'.
            '</div>'.
            '<div class="sx-document-discount-modal-field">'.
            '<label>Количество</label>'.
            '<div class="sx-document-discount-modal-qty">'.
            '<button type="button" class="sx-document-discount-modal-qty-minus">−</button>'.
            '<input type="number" step="any" class="sx-document-modal-quantity" value="1" />'.
            '<button type="button" class="sx-document-discount-modal-qty-plus">+</button>'.
            '</div>'.
            '</div>'.
            '<div class="sx-document-discount-modal-field">'.
            '<label>Скидка</label>'.
            '<div class="sx-document-discount-modal-discounts">'.
            '<div class="sx-document-discount-modal-control">'.
            '<input type="text" class="sx-document-modal-discount-percent" value="" autocomplete="off" />'.
            '<span class="sx-document-discount-modal-addon">%</span>'.
            '</div>'.
            '<div class="sx-document-discount-modal-control">'.
            '<input type="text" class="sx-document-modal-discount-money" value="" autocomplete="off" />'.
            '<span class="sx-document-discount-modal-addon">'.$currencySymbolHtml.'</span>'.
            '</div>'.
            '</div>'.
            '</div>'.
            '<div class="sx-document-discount-modal-footer">'.
            '<div class="sx-document-discount-modal-total">Итого: <strong>0</strong><span class="sx-document-discount-modal-total-currency"> '.$currencySymbolHtml.'</span><span class="sx-document-discount-modal-old-total"></span></div>'.
            '<button type="button" class="btn btn-default sx-document-discount-modal-close">Закрыть</button>'.
            '</div>'.
            '</div>'.
            '</div>'.
            '</div>';
    }

    protected function findDocument($pk)
    {
        if (!$document = ShopDocument::find()->where(['id' => (int)$pk])->one()) {
            throw new NotFoundHttpException('Документ не найден');
        }

        return $document;
    }

    public function renderDocumentItemRow($formName, $index, array $row, array $measureOptions = [], $documentType = null)
    {
        $prefix = "{$formName}[documentItemsData][{$index}]";
        $name = Html::encode(ArrayHelper::getValue($row, 'name'));
        $productId = Html::encode(ArrayHelper::getValue($row, 'shop_product_id'));
        $sourceBillId = Html::encode(ArrayHelper::getValue($row, 'source_shop_bill_id'));
        $sourceBillItemId = Html::encode(ArrayHelper::getValue($row, 'source_shop_bill_item_id'));
        $price = Html::encode((float)ArrayHelper::getValue($row, 'price', 0));
        $quantity = Html::encode((float)ArrayHelper::getValue($row, 'quantity', 1));
        $discountRawValue = (string)ArrayHelper::getValue($row, 'discount_value');
        $discountRawAmount = (float)ArrayHelper::getValue($row, 'discount_amount', 0);
        if ($discountRawValue === '' && $discountRawAmount > 0) {
            $discountRawValue = (string)$discountRawAmount;
        }
        $discountValue = Html::encode($discountRawValue);
        $discountAmount = Html::encode($discountRawAmount);
        $measure = (string)ArrayHelper::getValue($row, 'measure_name', 'шт');
        if ($measure !== '' && !isset($measureOptions[$measure])) {
            $measureOptions[$measure] = $measure;
        }
        $measureSelect = Html::dropDownList("{$prefix}[measure_name]", $measure, $measureOptions, [
            'class' => 'form-control sx-document-item-measure',
        ]);

        $vat = Html::encode(ArrayHelper::getValue($row, 'vat_name', 'Без НДС'));
        $vatNo = $this->selectedDocumentItemVat($vat, 'Без НДС');
        $vat0 = $this->selectedDocumentItemVat($vat, 'НДС 0%');
        $vat5 = $this->selectedDocumentItemVat($vat, 'НДС 5%');
        $vat7 = $this->selectedDocumentItemVat($vat, 'НДС 7%');
        $vat10 = $this->selectedDocumentItemVat($vat, 'НДС 10%');
        $vat20 = $this->selectedDocumentItemVat($vat, 'НДС 20%');
        $extraCells = $this->renderDocumentItemExtraCells($prefix, (array)ArrayHelper::getValue($row, 'extra_data', []), $documentType);

        return <<<HTML
<tr>
    <td>
        <input type="hidden" class="sx-document-item-product-id" name="{$prefix}[shop_product_id]" value="{$productId}" />
        <input type="hidden" name="{$prefix}[source_shop_bill_id]" value="{$sourceBillId}" />
        <input type="hidden" name="{$prefix}[source_shop_bill_item_id]" value="{$sourceBillItemId}" />
        <input type="text" class="form-control sx-document-item-name-input" name="{$prefix}[name]" value="{$name}" placeholder="Выберите или введите товар/услугу" autocomplete="off" />
    </td>
    <td><input type="number" step="any" class="form-control sx-document-item-price" name="{$prefix}[price]" value="{$price}" /></td>
    <td><input type="number" step="any" class="form-control sx-document-item-quantity" name="{$prefix}[quantity]" value="{$quantity}" /></td>
    <td>{$measureSelect}</td>
    <td>
        <select class="form-control" name="{$prefix}[vat_name]">
            <option value="Без НДС"{$vatNo}>Без НДС</option>
            <option value="НДС 0%"{$vat0}>НДС 0%</option>
            <option value="НДС 5%"{$vat5}>НДС 5%</option>
            <option value="НДС 7%"{$vat7}>НДС 7%</option>
            <option value="НДС 10%"{$vat10}>НДС 10%</option>
            <option value="НДС 20%"{$vat20}>НДС 20%</option>
        </select>
    </td>
    <td>
        <input type="hidden" class="sx-document-item-discount-amount" name="{$prefix}[discount_amount]" value="{$discountAmount}" />
        <input type="hidden" class="sx-document-item-discount-value" name="{$prefix}[discount_value]" value="{$discountValue}" />
        <button type="button" class="btn sx-document-item-discount-open is-empty">0</button>
    </td>
    {$extraCells}
    <td class="sx-document-item-amount"><span class="sx-document-item-amount-value">0,00</span><button class="btn sx-document-item-remove" type="button" title="Удалить"><i class="fa fa-trash"></i></button></td>
</tr>
HTML;
    }

    protected function renderDocumentItemExtraCells($prefix, array $extraData, $documentType)
    {
        if ($documentType == ShopDocument::TYPE_WAYBILL) {
            return Html::tag('td', Html::textInput($prefix.'[extra_data][package]', ArrayHelper::getValue($extraData, 'package'), ['class' => 'form-control', 'placeholder' => 'коробка'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][items_per_package]', ArrayHelper::getValue($extraData, 'items_per_package'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][places]', ArrayHelper::getValue($extraData, 'places'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][gross_weight]', ArrayHelper::getValue($extraData, 'gross_weight'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][net_weight]', ArrayHelper::getValue($extraData, 'net_weight'), ['class' => 'form-control']));
        }

        if (in_array($documentType, [ShopDocument::TYPE_UPD, ShopDocument::TYPE_INVOICE_FACTURE], true)) {
            return Html::tag('td', Html::textInput($prefix.'[extra_data][code]', ArrayHelper::getValue($extraData, 'code'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][country_code]', ArrayHelper::getValue($extraData, 'country_code'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][country_name]', ArrayHelper::getValue($extraData, 'country_name'), ['class' => 'form-control'])).
                Html::tag('td', Html::textInput($prefix.'[extra_data][declaration_number]', ArrayHelper::getValue($extraData, 'declaration_number'), ['class' => 'form-control']));
        }

        return '';
    }

    protected function selectedDocumentItemVat($current, $value)
    {
        return $current == $value ? ' selected="selected"' : '';
    }

    protected function documentMeasureOptions()
    {
        $result = [];
        foreach (CmsMeasure::find()->orderBy(['priority' => SORT_ASC])->all() as $measure) {
            $value = (string)$measure->asShortText;
            if ($value !== '') {
                $result[$value] = $value;
            }
        }

        if (!$result) {
            $result['шт'] = 'шт';
        }

        return $result;
    }

    protected function documentFormatMoney($value)
    {
        $value = (float)$value;
        $result = number_format($value, 2, ',', ' ');
        if (substr($result, -3) === ',00') {
            $result = substr($result, 0, -3);
        }

        return $result;
    }

    protected function documentCurrencySymbol($currencyCode)
    {
        $symbols = [
            'RUB' => '₽',
            'USD' => '$',
            'EUR' => '€',
        ];

        return ArrayHelper::getValue($symbols, $currencyCode, $currencyCode);
    }
}
