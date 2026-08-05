<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 31.05.2015
 */

namespace skeeks\cms\controllers;

use skeeks\cms\actions\backend\BackendModelMultiActivateAction;
use skeeks\cms\actions\backend\BackendModelMultiDeactivateAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\backend\widgets\BackendEntityLink;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\models\CmsLang;
use skeeks\cms\rbac\CmsManager;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\WidgetField;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsLangController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Management of languages");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsLang::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ROLE_ADMIN_ACCESS;
        
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return ArrayHelper::merge(parent::actions(), [
            'index'  => [
                "filters" => [
                    'visibleFilters' => [
                        'id',
                        'name',
                    ],
                ],
                'grid'    => [
                    'defaultOrder' => [
                        'is_active' => SORT_DESC,
                        'priority' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        //'code',
                        'is_active',
                        'priority',
                    ],
                    'columns'        => [
                        'custom'       => [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function (CmsLang $model) {
                                $media = BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-cms-lang',
                                    'modelId'      => $model->id,
                                    'content'      => Html::img($model->image ? $model->image->src : Image::getCapSrc(), [
                                        'class' => 'sx-photo sx-img-size-50',
                                        'alt'   => '',
                                    ]),
                                    'options'      => [
                                        'class'      => 'sx-preview-card__media-link',
                                        'aria-label' => (string)$model->asText,
                                    ],
                                ]);
                                $title = BackendEntityLink::widget([
                                    'controllerId' => '/cms/admin-cms-lang',
                                    'modelId'      => $model->id,
                                    'label'        => $model->asText,
                                    'options'      => ['class' => 'sx-preview-card__title sx-collection-cell__primary'],
                                ]);

                                return Html::tag('div',
                                    Html::tag('div', $media, ['class' => 'sx-preview-card__media']).
                                    Html::tag('div',
                                        $title.Html::tag('span', Html::encode($model->code), [
                                            'class' => 'sx-collection-cell__secondary',
                                        ]),
                                        ['class' => 'sx-preview-card__content sx-collection-cell sx-collection-cell--stack']
                                    ),
                                    ['class' => 'sx-preview-card']
                                );
                            }
                        ],

                        'is_active'   => [
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

            "activate-multi" => [
                'class' => BackendModelMultiActivateAction::class,
            ],

            "deactivate-multi" => [
                'class' => BackendModelMultiDeactivateAction::class,
            ],
        ]);
    }

    public function updateFields($action)
    {
        return [
            'image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'code',
            'is_active'   => [
                'class'      => BoolField::class,
            ],
            'name',
            'description',
            'priority',
        ];
    }
}
