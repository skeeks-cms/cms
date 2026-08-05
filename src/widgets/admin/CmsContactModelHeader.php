<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\helpers\BackendUrlHelper;
use skeeks\cms\backend\widgets\BackendModelHeader;
use skeeks\cms\backend\widgets\BackendQuickAccessFavoriteButton;
use skeeks\cms\widgets\user\UserOnlineWidget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/**
 * CMS contact adapter for the canonical [[BackendModelHeader]].
 *
 * It owns only contact-domain data and controls: avatar, live state,
 * quick-access favorite payload, phone/email metadata and account actions.
 */
class CmsContactModelHeader extends BackendModelHeader
{
    /** @var bool Show live online state beside the title. */
    public $showOnline = false;

    /** @var string|null Quick-access collection type, for example clients or companies. */
    public $favoriteType;

    /** @var string|null Backend view route used by the favorite item. */
    public $favoriteRoute;

    /** @var string|null Plain favorite item name. */
    public $favoriteName;

    /** @var bool Render the change-password action. */
    public $canChangePassword = true;

    /** @var string|null Optional route for the compact primary edit action. */
    public $editRoute;

    /** @var bool Render phone, SMS and email controls when their values exist. */
    public $showContactActions = true;

    public function init()
    {
        $this->title = $this->title !== null
            ? $this->title
            : (isset($this->model->shortDisplayNameWithAlias)
                ? $this->model->shortDisplayNameWithAlias
                : $this->model->asText);
        $this->roundImage = true;
        $this->showBackLink = $this->showBackLink === null ? false : $this->showBackLink;

        parent::init();

        $this->titleSuffix .= $this->renderContactTitleSuffix();
        $this->metaItems = array_merge($this->getContactMetaItems(), $this->metaItems);
        $this->toolbar = $this->renderContactToolbar().$this->toolbar;
    }

    protected function renderContactTitleSuffix()
    {
        $result = '';
        if ($this->showOnline) {
            $result .= UserOnlineWidget::widget([
                'user'    => $this->model,
                'options' => [
                    'class'  => 'sx-user-online-indicator--header',
                    'height' => '11px',
                ],
            ]);
        }
        if (isset($this->model->is_active) && !$this->model->is_active) {
            $result .= Html::tag('span', 'Отключён', [
                'class'       => 'sx-status sx-status--danger sx-model-header__state',
                'data-toggle' => 'tooltip',
                'title'       => 'Запись отключена',
            ]);
        }

        $favoriteItem = $this->getFavoriteItem();
        if ($favoriteItem) {
            $result .= BackendQuickAccessFavoriteButton::widget([
                'item' => $favoriteItem,
            ]);
        }

        return $result;
    }

    protected function getFavoriteItem()
    {
        if (!$this->favoriteType || !$this->favoriteRoute) {
            return null;
        }

        return [
            'type'   => $this->favoriteType,
            'id'     => (int)$this->model->id,
            'name'   => trim((string)($this->favoriteName ?: $this->title)),
            'url'    => Url::to([$this->favoriteRoute, 'pk' => $this->model->id]),
            'action' => (string)BackendUrlHelper::createByParams([
                $this->favoriteRoute,
                'pk' => $this->model->id,
            ])->enableEmptyLayout()->enableNoActions()->url,
            'image'  => $this->getFavoriteImageUrl(),
        ];
    }

    protected function getFavoriteImageUrl()
    {
        if (isset($this->model->avatarSrc) && $this->model->avatarSrc) {
            return (string)$this->model->avatarSrc;
        }

        $image = null;
        foreach (['image', 'cmsImage'] as $attribute) {
            if (isset($this->model->{$attribute}) && $this->model->{$attribute}) {
                $image = $this->model->{$attribute};
                break;
            }
        }
        if (!$image) {
            return null;
        }

        return (string)\Yii::$app->imaging->thumbnailUrlOnRequest(
            $image->src,
            new \skeeks\cms\components\imaging\filters\Thumbnail([
                'w' => 80,
                'h' => 80,
                'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
            ]),
            '',
            true
        );
    }

    protected function getContactMetaItems()
    {
        $items = [];
        if (isset($this->model->email) && $this->model->email) {
            $items[] = Html::tag('span',
                BackendIcon::render('mail', ['size' => 13]).' '.Html::encode($this->model->email)
            );
        }
        if (isset($this->model->phone) && $this->model->phone) {
            $items[] = Html::tag('span',
                BackendIcon::render('phone', ['size' => 13]).' '.Html::encode($this->model->phone)
            );
        }

        return $items;
    }

    protected function renderContactToolbar()
    {
        $result = '';
        if ($this->editRoute) {
            $result .= $this->renderBackendActionButton(
                $this->editRoute,
                'Редактировать основную информацию',
                'edit'
            );
        }
        if ($this->canChangePassword) {
            $result .= $this->renderBackendActionButton(
                '/cms/admin-user/change-password',
                'Изменить пароль',
                'key'
            );
        }

        if ($this->showContactActions && isset($this->model->phone) && $this->model->phone) {
            $phone = (string)$this->model->phone;
            $phoneHref = preg_replace('/[^\\d+*#,;]/', '', $phone);
            $result .= Html::a(BackendIcon::render('phone', ['size' => 16]), 'tel:'.$phoneHref, [
                'class'       => 'btn btn-default',
                'data-pjax'   => '0',
                'data-toggle' => 'tooltip',
                'title'       => 'Позвонить',
                'aria-label'  => 'Позвонить',
            ]);
            $result .= Html::a(BackendIcon::render('message', ['size' => 16]), '#', [
                'class'       => 'btn btn-default sx-send-sms-trigger',
                'data-phone'  => $phone,
                'data-toggle' => 'tooltip',
                'title'       => 'Написать SMS',
                'aria-label'  => 'Написать SMS',
            ]);
        }
        if ($this->showContactActions && isset($this->model->email) && $this->model->email) {
            $result .= Html::a(BackendIcon::render('mail', ['size' => 16]), 'mailto:'.$this->model->email, [
                'class'       => 'btn btn-default',
                'data-pjax'   => '0',
                'data-toggle' => 'tooltip',
                'title'       => 'Написать письмо',
                'aria-label'  => 'Написать письмо',
            ]);
        }

        return $result;
    }

    protected function renderBackendActionButton($route, $title, $icon)
    {
        $actionData = Json::encode([
            'isOpenNewWindow' => true,
            'size'            => 'small',
            'url'             => (string)BackendUrlHelper::createByParams([
                $route,
                'pk' => $this->model->id,
            ])->enableEmptyLayout()->enableNoActions()->enableNoModelActions()->url,
        ]);

        return Html::a(BackendIcon::render($icon, ['size' => 16]), '#', [
            'class'       => 'btn btn-default',
            'onclick'     => "new sx.classes.backend.widgets.Action({$actionData}).go(); return false;",
            'data-toggle' => 'tooltip',
            'title'       => $title,
            'aria-label'  => $title,
        ]);
    }
}
