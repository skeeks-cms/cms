<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */
/* @var $this yii\web\View */
/**
 * @var $widget \skeeks\cms\widgets\admin\CmsLogListWidget
 */
$widget = $this->context;

if (!$widget->is_show_model) {

    $this->registerCss(<<<CSS
    .sx-log-list .sx-model {
        display: none !important;    
    }
CSS
);
}

$this->registerCss(<<<CSS


.sx-list .sx-files img {
    border-radius: var(--border-radius);
    max-width: 10rem;
    border: 1px solid var(--color-light-gray);
}

.sx-list .sx-controlls a {
    color: #a1a1a1;
}
.sx-log-list .sx-log-meta {
    align-items: center;
    flex-wrap: wrap;
    gap: 0.45rem;
}
.sx-log-list .sx-log-meta-item {
    color: #9aa0a6;
    font-size: 0.78rem;
    line-height: 1.2;
    margin-right: 0.45rem;
}
.sx-log-list .sx-log-pin-toggle {
    align-items: center;
    background: #f5f7fa;
    border: 1px solid #dfe5ec;
    border-radius: 999px;
    color: #667085;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.72rem;
    font-weight: 600;
    gap: 0.3rem;
    line-height: 1;
    padding: 0.28rem 0.55rem;
    transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease, opacity 0.18s ease;
}
.sx-log-list .sx-log-pin-toggle:hover {
    background: #edf6ff;
    border-color: #b8d8f4;
    color: #1e6ba8;
}
.sx-log-list .sx-log-pin-toggle.is-pinned {
    background: #e8f7f4;
    border-color: #9edbd0;
    color: #11806f;
}
.sx-log-list .sx-log-pin-toggle.is-loading {
    opacity: 0.55;
    pointer-events: none;
}
.sx-log-list .sx-log-value-collapsed {
    display: inline;
}
.sx-log-list .sx-log-value-preview {
    color: inherit;
}
.sx-log-list .sx-log-value-toggle {
    background: #f5f7fa;
    border: 1px solid #dfe5ec;
    border-radius: 999px;
    color: #667085;
    cursor: pointer;
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1;
    margin-left: 0.35rem;
    padding: 0.25rem 0.55rem;
}
.sx-log-list .sx-log-value-toggle:hover {
    background: #edf6ff;
    border-color: #b8d8f4;
    color: #1e6ba8;
}
.sx-log-list .sx-log-value-full {
    background: #f8fafc;
    border: 1px solid #e6ebf1;
    border-radius: 0.5rem;
    display: none;
    margin-top: 0.6rem;
    max-height: 28rem;
    overflow: auto;
    padding: 0.75rem 0.9rem;
}
.sx-log-list .sx-log-value-collapsed.is-open .sx-log-value-full {
    display: block;
}
.sx-log-list .sx-log-value-collapsed.is-open .sx-log-value-preview {
    display: none;
}
.sx-log-list .sx-log-value-pre {
    margin: 0;
    white-space: pre-wrap;
}
.sx-log-list .sx-hidden-content .sx-hidden {
    display: none;
}
.sx-log-list .sx-right-btn {
    position: absolute;
    right: 1rem;
    bottom: 1rem;
}
.sx-log-list .sx-edit-btn {
    color: silver;
    cursor: pointer;
    opacity: 0;
    margin-right: 5px;
    transition: 0.4s;
}

.sx-log-list .sx-item {
    position: relative;
}
.sx-log-list .sx-item .sx-files .sx-file-item {
    margin-bottom: 0.25rem;
}
.sx-log-list .sx-item .sx-files .sx-files-block {
    background: var(--bg-color-light);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.sx-log-list .sx-item .sx-files .sx-title {
    color: var(--color-gray);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    margin-top: 1rem;
}
.sx-log-list .sx-item:hover .sx-edit-btn {
    opacity: 1;
}


.sx-tel-item.failed .sx-icon {
    background: #ffe8e8;
}
.sx-tel-item.failed .sx-icon svg {
    fill: #ff5752;
}

.sx-tel-item.answered .sx-icon {
    background: #ccffe7;
}
.sx-tel-item.answered .sx-icon svg {
    fill: #1bce7b;
}

.sx-tel-item .sx-phone-status-icon svg {
    width: 2rem;
    height: 2rem;
}

.sx-tel-item .sx-gray {
    color: gray;
}

.sx-tel-item .sx-phone-status-icon {
    position: absolute;
    /*background: white;*/
    right: 1rem;
    /*bottom: 50%;
    transform: translateY(50%);*/
    top: 1rem;
}

.sx-tel-item audio {
    width: 100%;
}
.sx-tel-item .sx-icon {
    position: relative;
    background: silver;
    padding: 1rem;
    border-radius: 1rem;
    width: 6rem;
    height: 6rem;
    display: flex;
    align-items: center;
    justify-content: center;
}
CSS
);

\skeeks\cms\assets\LinkActvationAsset::register($this);
$this->registerJs(<<<JS
new sx.classes.LinkActivation('.sx-comment-wrapper');
$("body").off("click.sxLogValueToggle").on("click.sxLogValueToggle", ".sx-log-value-toggle", function(e) {
    e.preventDefault();

    var jWrapper = $(this).closest(".sx-log-value-collapsed");
    var isOpen = !jWrapper.hasClass("is-open");

    jWrapper.toggleClass("is-open", isOpen);
    $(this).text(isOpen ? "Свернуть" : "Показать полностью");

    return false;
});
JS
);

if ($widget->is_show_pin_controls) {
    $this->registerJs(<<<JS
$("body").off("click.sxLogPinToggle").on("click.sxLogPinToggle", ".sx-log-pin-toggle", function(e) {
    e.preventDefault();

    var jBtn = $(this);
    var data = {
        id: jBtn.data("id"),
        is_pinned: jBtn.data("value")
    };

    if (window.yii) {
        data[yii.getCsrfParam()] = yii.getCsrfToken();
    }

    jBtn.addClass("is-loading");

    $.ajax({
        url: jBtn.data("url"),
        type: "post",
        data: data,
        success: function(response) {
            if (response && response.success === false) {
                alert(response.message || "Не удалось обновить комментарий.");
                jBtn.removeClass("is-loading");
                return;
            }

            var jPjax = jBtn.closest("[data-pjax-container]");
            if (jPjax.length && $.pjax) {
                $.pjax.reload({container: "#" + jPjax.attr("id"), async: false});
            } else {
                window.location.reload();
            }
        },
        error: function() {
            alert("Не удалось обновить комментарий.");
            jBtn.removeClass("is-loading");
        }
    });

    return false;
});
JS
    );
}

$dataProvider = new \yii\data\ActiveDataProvider([
    'query' => $widget->query,
    'sort'       => [
        'defaultOrder' => [
            'created_at' => SORT_DESC,
        ],
    ],
    'pagination' => [
        'defaultPageSize' => 50,
    ],
]);

?>

<? echo \yii\widgets\ListView::widget(\yii\helpers\ArrayHelper::merge([
    'dataProvider' => $dataProvider,
    'itemView'     => '_log-list-item',
    'viewParams'   => [
        'is_show_pin_controls' => (bool)$widget->is_show_pin_controls,
    ],
    'emptyText'    => '<div class="sx-block">Записей нет</div>',
    'options'      => [
        'class' => '',
        'tag'   => 'div',
    ],
    'itemOptions'  => [
        'tag'   => 'div',
        'class' => 'sx-item-wrapper col-12',
    ],
    'pager'        => [
        'container' => '.sx-list',
        'item'      => '.sx-item-wrapper',
        'class'     => \skeeks\cms\themes\unify\widgets\ScrollAndSpPager::class,
    ],
    //'summary'      => "Всего товаров: {totalCount}",
    'summary'      => false,
    //"\n{items}<div class=\"box-paging\">{pager}</div>{summary}<div class='sx-js-pagination'></div>",
    'layout'       => '<div class="row"><div class="col-md-12 sx-list-summary">{summary}</div></div>
    <div class="no-gutters row sx-list sx-log-list">{items}</div>
    <div class="row"><div class="col-md-12">{pager}</div></div>',
], (array) $widget->list_view_config))
?>
