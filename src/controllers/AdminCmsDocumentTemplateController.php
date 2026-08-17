<?php

namespace skeeks\cms\controllers;

use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\grid\BackendEntityLinkColumn;
use skeeks\cms\grid\DateTimeColumnData;
use skeeks\cms\models\CmsDocumentTemplate;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\widgets\AjaxFileUploadWidget;
use skeeks\cms\widgets\ColorInput;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\FieldSet;
use skeeks\yii2\form\fields\SelectField;
use skeeks\yii2\form\fields\TextareaField;
use skeeks\yii2\form\fields\WidgetField;
use yii\base\Event;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class AdminCmsDocumentTemplateController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = 'Шаблоны документов';
        $this->modelShowAttribute = 'name';
        $this->modelClassName = CmsDocumentTemplate::class;
        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;

        parent::init();
    }

    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index' => [
                'backendShowings' => false,
                'filters' => false,
                'grid' => [
                    'on init' => function (Event $event) {
                        /** @var ActiveDataProvider $dataProvider */
                        $dataProvider = $event->sender->dataProvider;
                        /** @var ActiveQuery $query */
                        $query = $dataProvider->query;
                        $query->andWhere(['cms_site_id' => \Yii::$app->skeeks->site->id]);
                    },
                    'defaultOrder' => ['is_default' => SORT_DESC, 'name' => SORT_ASC],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'name',
                        'theme',
                        'document_type',
                        'is_default',
                        'is_active',
                        'updated_at',
                    ],
                    'columns' => [
                        'name' => [
                            'class'        => BackendEntityLinkColumn::class,
                            'controllerId' => '/cms/admin-cms-document-template',
                            'attribute'    => 'name',
                        ],
                        'theme' => [
                            'value' => function (CmsDocumentTemplate $model) {
                                return ArrayHelper::getValue(CmsDocumentTemplate::themes(), $model->theme, $model->theme);
                            },
                        ],
                        'document_type' => [
                            'value' => function (CmsDocumentTemplate $model) {
                                return ArrayHelper::getValue(CmsDocumentTemplate::documentTypes(), $model->document_type, $model->document_type);
                            },
                        ],
                        'is_default' => [
                            'value' => function (CmsDocumentTemplate $model) {
                                return $model->is_default ? 'Да' : '';
                            },
                        ],
                        'updated_at' => [
                            'label' => 'Обновлён',
                            'class' => DateTimeColumnData::class,
                        ],
                    ],
                ],
                'emptyState' => [
                    'title'       => 'Профилей пока нет',
                    'description' => 'Создайте профиль на основе SkeekS Dark или SkeekS Light. Пока профиль не выбран, существующие PDF формируются как раньше.',
                    'icon'        => 'file-alt',
                    'action'      => ['backendAction' => 'create', 'label' => 'Создать шаблон'],
                ],
            ],
            'create' => [
                'fields' => [$this, 'updateFields'],
            ],
            'update' => [
                'fields' => [$this, 'updateFields'],
            ],
        ]);
    }

    public function updateFields($action)
    {
        /** @var CmsDocumentTemplate $model */
        $model = $action->model;
        $model->load(\Yii::$app->request->get());
        if ($model->isNewRecord) {
            $model->cms_site_id = \Yii::$app->skeeks->site->id;
        }

        return [
            'main' => [
                'class' => FieldSet::class,
                'name' => 'Основное',
                'fields' => [
                    'name',
                    'theme' => [
                        'class' => SelectField::class,
                        'items' => CmsDocumentTemplate::themes(),
                    ],
                    'document_type' => [
                        'class' => SelectField::class,
                        'items' => CmsDocumentTemplate::documentTypes(),
                    ],
                    'is_active' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                        'allowNull' => false,
                    ],
                    'is_default' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                        'allowNull' => false,
                    ],
                ],
            ],
            'branding' => [
                'class' => FieldSet::class,
                'name' => 'Брендинг',
                'fields' => [
                    'logo_storage_file_id' => [
                        'class' => WidgetField::class,
                        'widgetClass' => AjaxFileUploadWidget::class,
                        'widgetConfig' => [
                            'accept' => 'image/*',
                            'multiple' => false,
                        ],
                    ],
                    'footer_text' => [
                        'class' => TextareaField::class,
                    ],
                ],
            ],
            'colors' => [
                'class' => FieldSet::class,
                'name' => 'Цвета',
                'fields' => array_map(function ($attribute) {
                    return [
                        'class' => WidgetField::class,
                        'widgetClass' => ColorInput::class,
                        'widgetConfig' => ['options' => ['placeholder' => 'Из готовой основы']],
                    ];
                }, array_flip([
                    'accent_color',
                    'background_color',
                    'surface_color',
                    'text_color',
                    'muted_color',
                    'border_color',
                ])),
            ],
            'document' => [
                'class' => FieldSet::class,
                'name' => 'Документ',
                'fields' => [
                    'page_orientation' => [
                        'class' => SelectField::class,
                        'items' => CmsDocumentTemplate::pageOrientations(),
                    ],
                    'show_cover' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                        'allowNull' => false,
                    ],
                    'show_footer' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                        'allowNull' => false,
                    ],
                    'show_page_numbers' => [
                        'class' => BoolField::class,
                        'formElement' => BoolField::ELEMENT_CHECKBOX,
                        'allowNull' => false,
                    ],
                ],
            ],
        ];
    }
}
