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
use skeeks\cms\backend\BackendAction;
use skeeks\cms\backend\controllers\BackendModelStandartController;
use skeeks\cms\grid\BooleanColumn;
use skeeks\cms\grid\ImageColumn2;
use skeeks\cms\helpers\Image;
use skeeks\cms\models\CmsCountry;
use skeeks\cms\models\CmsLang;
use skeeks\cms\rbac\CmsManager;
use skeeks\cms\shop\models\ShopProduct;
use skeeks\cms\widgets\GridView;
use skeeks\yii2\form\fields\BoolField;
use skeeks\yii2\form\fields\WidgetField;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class AdminCmsCountryController extends BackendModelStandartController
{
    public function init()
    {
        $this->name = \Yii::t('skeeks/cms', "Страны");
        $this->modelShowAttribute = "name";
        $this->modelClassName = CmsCountry::class;

        $this->generateAccessActions = false;
        $this->permissionName = CmsManager::PERMISSION_ADMIN_ACCESS;

        $this->accessCallback = function () {
            if (!\Yii::$app->skeeks->site->is_default) {
                return false;
            }
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
                "filters" => [
                    'visibleFilters' => [
                        'name',
                        'alpha2',
                        'alpha3',
                    ],
                ],
                'grid'    => [
                    'defaultOrder' => [
                        'name' => SORT_ASC,
                    ],
                    'visibleColumns' => [
                        'checkbox',
                        'actions',
                        'custom',
                        'alpha2',
                        'alpha3',
                        'domain',
                        'phone_code',
                        //'code',
                        'countProducts',
                        'is_active',
                        'priority',
                    ],
                    'columns'        => [
                        'countProducts'   => [
                            'format'    => 'raw',
                            'value'     => function (CmsCountry $cmsCountry) {
                                return $cmsCountry->raw_row['countProducts'];
                            },
                            'attribute' => 'countProducts',
                            'label'     => 'Количество товаров',
                            'beforeCreateCallback' => function (GridView $gridView) {
                                $query = $gridView->dataProvider->query;
    
                                $countProductsQuery = ShopProduct::find()
                                    ->select(["total" => new \yii\db\Expression("count(id)"),])
                                    ->andWhere([
                                        'country_alpha2' => new Expression(CmsCountry::tableName().".alpha2"),
                                    ]);
    
                                $query->addSelect([
                                    'countProducts' => $countProductsQuery,
                                ]);
    
                                $gridView->sortAttributes['countProducts'] = [
                                    'asc'     => ['countProducts' => SORT_ASC],
                                    'desc'    => ['countProducts' => SORT_DESC],
                                    'label'   => '',
                                    'default' => SORT_ASC,
                                ];
                            },
    
                        ],
                            
                        'domain'       => [
                            'value' => function($model) {
                                return (string) $model->domain;
                            }
                        ],
                        'phone_code'       => [
                            'value' => function($model) {
                                return (string) $model->phone_code;
                            }
                        ],
                        'custom'       => [
                            'attribute' => 'name',
                            'format' => 'raw',
                            'value' => function (CmsCountry $model) {

                                $data = [];
                                $data[] = Html::a($model->asText, "#", ['class' => 'sx-trigger-action']);

                                $info = implode("<br />", $data);

                                return "<div class='row no-gutters'>
                                            <div class='sx-trigger-action' style='width: 50px;'>
                                                <a href='#' style='text-decoration: none; border-bottom: 0;'>
                                                    <img src='". ($model->flag ? $model->flag->src : Image::getCapSrc()) ."' style='max-width: 50px; max-height: 50px; border-radius: 5px;' />
                                                </a>
                                            </div>
                                            <div style='margin: auto 5px;'>" . $info  . "</div>
                                        </div>";

                                            ;
                            }
                        ],

                        'flag_image_id' => [
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

            'import' => [
                "class"    => BackendAction::class,
                "name"     => \Yii::t('skeeks/money', "Загрузить страны"),
                "icon"     => "glyphicon glyphicon-paperclip",
                "callback" => [$this, 'actionImport'],
            ],
        ]);
    }

    public function updateFields($action)
    {
        return [
            'name',
            'alpha2',
            'alpha3',
            'iso',
            'flag_image_id' => [
                'class'        => WidgetField::class,
                'widgetClass'  => \skeeks\cms\widgets\AjaxFileUploadWidget::class,
                'widgetConfig' => [
                    'accept'   => 'image/*',
                    'multiple' => false,
                ],
            ],
            'phone_code',
            'domain',
        ];
    }


    /**
     * @see https://www.artlebedev.ru/country-list/xml/
     * @see https://github.com/mledoze/countries/blob/master/dist/countries.json
     * @return string
     * @throws \yii\base\Exception
     */
    public function actionImport()
    {
        if (\Yii::$app->request->isPost) {

            /**
             * $xml = https://www.artlebedev.ru/country-list/xml/ -> \Yii::getAlias("@skeeks/cms/data/countries/countries-lebedev.json");
             * https://github.com/mledoze/countries/blob/master/dist/countries.json -> \Yii::getAlias("@skeeks/cms/data/countries/countries.json");
             */

            $file = \Yii::getAlias("@skeeks/cms/data/countries/countries-for-import.json");
            $data = file_get_contents($file);
            $jsonArr = Json::decode($data);

            foreach ($jsonArr as $countryData)
            {
                $alpha2 = ArrayHelper::getValue($countryData, "alpha2");
                $country = CmsCountry::find()->andWhere(['alpha2' => $alpha2])->one();
                if (!$country) {
                    $country = new CmsCountry();
                    $country->alpha2 = ArrayHelper::getValue($countryData, "alpha2");
                    $country->alpha3 = ArrayHelper::getValue($countryData, "alpha3");
                    $country->iso = ArrayHelper::getValue($countryData, "iso");
                    $country->name = ArrayHelper::getValue($countryData, "name");
                    $country->domain = ArrayHelper::getValue($countryData, "domain");
                    $country->phone_code = ArrayHelper::getValue($countryData, "phone_code");

                    $country->save(true);

                    $alpha2 = strtolower($country->alpha2);

                    $fileSrc = \Yii::getAlias("@skeeks/cms/data/countries/flags/w640/{$alpha2}.png");
                    if (file_exists($fileSrc)) {
                        $cmsStorageFile = \Yii::$app->storage->upload($fileSrc);
                        $country->flag_image_id = $cmsStorageFile->id;

                        $country->save(true);
                    }

                }
            }

        }

        return $this->render($this->action->id);
    }
}
