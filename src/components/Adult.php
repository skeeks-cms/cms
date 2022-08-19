<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\components;

use skeeks\cms\assets\AdultAsset;
use skeeks\cms\models\CmsContentElement;
use skeeks\cms\models\CmsTree;
use skeeks\cms\web\View;
use yii\base\Component;
use yii\bootstrap\Modal;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * @property boolean $isAllowAdult
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class Adult extends Component
{
    /**
     * @var string
     */
    public $sesstion_adult_name = 'IS_ALLOW_ADULT';

    /**
     * @var bool
     */
    protected $_is_blocked = false;

    /**
     * @var bool
     */
    protected $_is_registered_blocked_asset = false;

    /**
     * @var string
     */
    public $is_adult_css_class = "sx-adult";

    /**
     * @var string
     */
    public $blocked_btn_text = '<span class="sx-full-text"><i class="far fa-eye-slash"></i> Товар для взрослых</span><span class="sx-mini-text">18+</span>';

    /**
     * @var string
     */
    public $blocked_asset = AdultAsset::class;

    /**
     * @return bool
     */
    public function getIsAllowAdult()
    {
        return (bool)\Yii::$app->session->get($this->sesstion_adult_name, false);
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setIsAllowAdult(bool $value = true)
    {
        \Yii::$app->session->set($this->sesstion_adult_name, (int) $value);
        return $this;
    }

    /**
     * @param CmsContentElement|CmsTree $model
     * @return bool
     */
    public function isBlocked($model)
    {
        //Если элемент или раздел не имеет признака для взрослых, то он не заблокирован
        if (!$model->is_adult) {
            return false;
        }

        //Если это авторизованный пользователь то показываем ему контент для взрослых
        if (!\Yii::$app->user->isGuest) {
            return false;
        }

        //Если пользователь уже согласился с тем что ему доступен контент 18+
        if ($this->isAllowAdult) {
            return false;
        }

        $this->_is_blocked = true;
        //1 рез регистрируются скрипты и css
        if ($this->_is_registered_blocked_asset === false) {
            $this->_is_registered_blocked_asset = true;
            if (!\Yii::$app->request->isPjax || !\Yii::$app->request->isAjax) {

                $assetClass = $this->blocked_asset;
                $assetClass::register(\Yii::$app->view);
                $jsData = Json::encode([
                    'backend' => Url::to(['/cms/ajax/adult'])
                ]);
                \Yii::$app->view->registerJs(<<<JS
new sx.classes.Adult({$jsData});
JS
                );

                \Yii::$app->view->on(View::EVENT_BEGIN_BODY, function ($e) {


                    $modal = Modal::begin([
                        'options'      => [
                            'id'    => 'sx-adult-modal',
                            'class' => 'sx-adult-modal',
                        ],
                        'header'       => 'Подтвердите свой возраст',
                        'toggleButton' => false,
                    ]);
                    echo <<<HTML
<p>Данный раздел предназначен только для посетителей, достигших возраста 18 лет!</p>
<p><button class="btn btn-primary sx-btn-yes">Да, мне есть 18 лет</button>
<button class="btn btn-secondary sx-btn-no">Нет</button></p>
HTML;


                    $modal::end();

                });
            }


        }
        //todo: тут надо проверить дал ли гость согласие на 18+
        return true;
    }

    /**
     * @param CmsContentElement|CmsTree $model
     * @return string
     */
    public function renderCssClass($model)
    {
        if ($this->isBlocked($model)) {
            return $this->is_adult_css_class;
        }

        return "";
    }

    /**
     * @param $model
     * @return string
     */
    public function renderBlocked($model)
    {
        if ($this->isBlocked($model)) {

            return <<<HTML
<div class="sx-adult-block">
    <div class="sx-adult-blur"></div>
    <div href="#" class="btn btn-default sx-adult-block-text">
        {$this->blocked_btn_text}
    </div>
</div>
HTML
                ;
        }

        return "";
    }

}