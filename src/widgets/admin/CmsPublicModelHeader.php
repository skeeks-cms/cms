<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendModelHeader;
use yii\helpers\Html;

/**
 * Shared publication policy for public CMS tree nodes and content elements.
 */
class CmsPublicModelHeader extends BackendModelHeader
{
    /** @var string|null Public website URL. */
    public $publicUrl;

    /** @var string|null External supplier entity URL for records with sx_id. */
    public $supplierUrl;

    /** @var string|null Plain parent or section label. */
    public $parentLabel;

    /** @var string|null Longer parent tooltip. */
    public $parentTitle;

    public function init()
    {
        if ($this->title === null && isset($this->model->name)) {
            $this->title = $this->model->name;
        }

        parent::init();

        $this->titleSuffix .= $this->renderPublicationFlags();
        if ($this->parentLabel) {
            $this->metaItems[] = Html::tag('span',
                BackendIcon::render('folder', ['size' => 13]).' '.Html::encode($this->parentLabel),
                [
                    'title'       => $this->parentTitle ?: $this->parentLabel,
                    'data-toggle' => 'tooltip',
                ]
            );
        }
        if ($this->publicUrl) {
            $label = \Yii::t('skeeks/cms', 'Watch to site (opens new window)');
            $this->toolbar = Html::a(
                BackendIcon::render('external-link', ['size' => 16]),
                $this->publicUrl,
                [
                    'class'       => 'btn btn-default',
                    'target'      => '_blank',
                    'rel'         => 'noopener noreferrer',
                    'data-pjax'   => '0',
                    'data-toggle' => 'tooltip',
                    'title'       => $label,
                    'aria-label'  => $label,
                ]
            ).$this->toolbar;
        }
    }

    protected function renderPublicationFlags()
    {
        $result = '';

        if (isset($this->model->is_adult) && $this->model->is_adult) {
            $result .= $this->renderMarker(
                '18+',
                'Материал имеет возрастное ограничение 18+',
                'sx-text--danger'
            );
        }
        if (isset($this->model->isAllowIndex) && !$this->model->isAllowIndex) {
            $result .= $this->renderMarker(
                'no index',
                'Материал не индексируется поисковыми системами',
                'sx-text--danger'
            );
        }
        if (isset($this->model->sx_id) && $this->model->sx_id) {
            $result .= $this->renderSupplierReference();
        }
        if (isset($this->model->isCanonical) && $this->model->isCanonical) {
            $result .= $this->renderMarker(
                'canonical',
                'Канонический URL: '.$this->model->canonicalUrl,
                'sx-text--danger'
            );
        }
        if (isset($this->model->isRedirect) && $this->model->isRedirect) {
            $result .= Html::tag('span', BackendIcon::render('external-link', ['size' => 15]), [
                'class'       => 'sx-model-header__external-id',
                'data-toggle' => 'tooltip',
                'title'       => $this->model->redirect_code.' redirect: '.$this->model->url,
                'aria-label'  => $this->model->redirect_code.' redirect: '.$this->model->url,
            ]);
        }

        return $result;
    }

    protected function renderSupplierReference()
    {
        $isUpdated = !isset($this->model->is_sx_info_update) || $this->model->is_sx_info_update;
        $stateClass = $isUpdated ? 'sx-text--success' : 'sx-text--danger';
        $title = $isUpdated
            ? "SkeekS ID: {$this->model->sx_id}. Информация обновляется из сервиса SkeekS Товары"
            : "SkeekS ID: {$this->model->sx_id}. Обновление информации из сервиса SkeekS Товары запрещено";
        $icon = BackendIcon::render('external-link', ['size' => 15]);
        $options = [
            'class'       => $stateClass,
            'data-toggle' => 'tooltip',
            'title'       => $title,
            'aria-label'  => $title,
        ];
        $reference = $this->supplierUrl
            ? Html::a($icon, $this->supplierUrl, array_merge($options, [
                'target'    => '_blank',
                'rel'       => 'noopener noreferrer',
                'data-pjax' => '0',
            ]))
            : Html::tag('span', $icon, $options);

        return Html::tag('span', $reference, ['class' => 'sx-model-header__external-id']);
    }

    protected function renderMarker($label, $title, $class = '')
    {
        return Html::tag('span', Html::tag('span', Html::encode('['.$label.']'), [
            'data-toggle' => 'tooltip',
            'title'       => $title,
        ]), [
            'class' => trim('sx-model-header__external-id '.$class),
        ]);
    }
}
