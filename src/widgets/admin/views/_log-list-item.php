<?php
/**
 * One entry of the activity feed: the shared frame around CmsLog::entry().
 * The icon of the event type, the title with badges, a short meta line
 * (time, author, the record it belongs to), the main line, details, the
 * body and files. Pinning, the link and the record menu are quiet icons
 * shown on hover (always on touch screens).
 *
 * @var $this yii\web\View
 * @var $model \skeeks\cms\models\CmsLog
 * @var $is_show_pin_controls bool
 * @var $is_raised bool
 */

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\backend\widgets\BackendEntityLink;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$log = $model;
$entry = $log->entry();
$is_show_pin_controls = isset($is_show_pin_controls) ? (bool)$is_show_pin_controls : false;
$is_raised = isset($is_raised) ? (bool)$is_raised : true;

$surfaceClass = 'sx-surface sx-surface--padded sx-item sx-log-item sx-log-entry';
if ($is_raised) {
    $surfaceClass .= ' sx-surface--raised';
}
if ($log->is_pinned) {
    $surfaceClass .= ' is-pinned';
}

$isTaskLog = $log->model_code == \skeeks\cms\models\CmsTask::class;
$pinLabel = $isTaskLog ? 'Добавить в результат задачи' : 'Закрепить';
$unpinLabel = $isTaskLog ? 'Убрать из результата задачи' : 'Открепить';
$pinnedText = $isTaskLog ? 'В результате задачи' : 'Закреплено';
$togglePinUrl = \yii\helpers\Url::to(['/cms/admin-cms-log/toggle-pin']);
$shareUrl = \yii\helpers\Url::current(['sx-log-id' => (int)$log->id], true).'#sx-log-'.(int)$log->id;
$canUpdateDelete = \Yii::$app->user->can("cms/admin-cms-log/update-delete", ['model' => $log]);

// The time: "24 окт., 17:07" this year, with the year otherwise; the full date in the tooltip.
$formatter = \Yii::$app->formatter;
$isThisYear = date('Y', (int)$log->created_at) === date('Y');
$time = Html::tag('time', Html::encode($formatter->asDatetime($log->created_at, $isThisYear ? 'd MMM, HH:mm' : 'd MMM yyyy, HH:mm')), [
    'class'    => 'sx-log-entry__time',
    'datetime' => date('c', (int)$log->created_at),
    'title'    => $formatter->asDatetime($log->created_at, 'medium'),
]);

$meta = [$time];
if ($log->createdBy) {
    $author = $log->createdBy;
    $name = (string)$author->shortDisplayName;
    $avatar = $author->image
        ? Html::img(\Yii::$app->imaging->thumbnailUrlOnRequest($author->image->src, new \skeeks\cms\components\imaging\filters\Thumbnail([
            'w' => 40,
            'h' => 40,
            'm' => \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND,
        ])), ['class' => 'sx-log-entry__avatar', 'alt' => '', 'loading' => 'lazy'])
        : Html::tag('span', Html::encode(mb_strtoupper(mb_substr($name, 0, 1))), ['class' => 'sx-log-entry__avatar', 'aria-hidden' => 'true']);
    $meta[] = BackendEntityLink::widget([
        'controllerId' => $author->is_worker ? '/cms/admin-worker' : '/cms/admin-user',
        'modelId'      => $author->id,
        'content'      => $avatar.Html::tag('span', Html::encode($name)),
        'options'      => ['class' => 'sx-log-entry__author'],
    ]);
}
// The records the entry belongs to; a card feed hides its own record (CmsLogListWidget::$is_show_model).
foreach ([['model', 'model_code', 'sx-model'], ['subModel', 'sub_model_code', '']] as [$relation, $codeAttribute, $class]) {
    $record = $log->$relation;
    if (!$record) {
        continue;
    }
    $controllerId = ArrayHelper::getValue(\Yii::$app->skeeks->modelsConfig, [$log->$codeAttribute, 'controller']);
    $label = (string)$record->asText;
    $meta[] = Html::tag('span', $controllerId
        ? BackendEntityLink::widget([
            'controllerId' => $controllerId,
            'modelId'      => $record->id,
            'label'        => $label,
            'options'      => ['class' => 'sx-log-entry__record', 'title' => $label],
        ])
        : Html::encode($label), ['class' => trim('sx-log-entry__context sx-log-model '.$class)]);
}
if ($log->is_pinned) {
    $meta[] = Html::tag('span', BackendIcon::render('pin', ['size' => 12]).Html::encode($pinnedText), ['class' => 'sx-log-entry__pinned']);
}
?>
<article id="sx-log-<?php echo (int)$log->id; ?>" class="<?php echo $surfaceClass; ?>" data-sx-log-id="<?php echo (int)$log->id; ?>">
    <div class="sx-log-entry__icon sx-log-entry__icon--<?php echo Html::encode($entry->tone); ?>" aria-hidden="true">
        <?php echo BackendIcon::render(BackendIcon::supports($entry->icon) ? $entry->icon : 'info', ['size' => 18]); ?>
    </div>
    <div class="sx-log-entry__main">
        <header class="sx-log-entry__head">
            <div class="sx-log-entry__heading">
                <span class="sx-log-entry__title"><?php echo Html::encode($entry->title !== '' ? $entry->title : $log->logTypeAsText); ?></span>
                <?php echo implode(' ', $entry->badges); ?>
            </div>
            <div class="sx-log-entry__actions">
                <?php if ($is_show_pin_controls) : ?>
                    <button type="button"
                            class="sx-icon-action sx-log-entry__action sx-log-pin-toggle <?php echo $log->is_pinned ? 'is-pinned is-active' : ''; ?>"
                            data-id="<?php echo (int)$log->id; ?>"
                            data-url="<?php echo $togglePinUrl; ?>"
                            data-value="<?php echo $log->is_pinned ? 0 : 1; ?>"
                            data-error-message="Не удалось обновить запись."
                            aria-pressed="<?php echo $log->is_pinned ? 'true' : 'false'; ?>"
                            aria-label="<?php echo Html::encode($log->is_pinned ? $unpinLabel : $pinLabel); ?>"
                            title="<?php echo Html::encode($log->is_pinned ? $unpinLabel : $pinLabel); ?>">
                        <?php echo BackendIcon::render('pin', ['size' => 16]); ?>
                    </button>
                <?php endif; ?>
                <button type="button"
                        class="sx-icon-action sx-log-entry__action sx-log-share"
                        data-url="<?php echo Html::encode($shareUrl); ?>"
                        data-success-message="Ссылка на запись скопирована"
                        aria-label="Скопировать ссылку на запись"
                        title="Скопировать ссылку на запись">
                    <?php echo BackendIcon::render('link', ['size' => 16]); ?>
                </button>
                <?php if ($canUpdateDelete) : ?>
                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::begin([
                        'controllerId' => "/cms/admin-cms-log",
                        'modelId'      => $log->id,
                        'tag'          => 'button',
                        'options'      => [
                            'type'       => 'button',
                            'title'      => 'Действия с записью',
                            'aria-label' => 'Действия с записью',
                            'class'      => 'sx-icon-action sx-log-entry__action sx-edit-btn',
                        ],
                    ]); ?>
                    <?php echo BackendIcon::render('more-vertical', ['size' => 16]); ?>
                    <?php \skeeks\cms\backend\widgets\AjaxControllerActionsWidget::end(); ?>
                <?php endif; ?>
            </div>
        </header>

        <div class="sx-log-entry__meta"><?php echo implode('', $meta); ?></div>

        <?php if ($entry->summary !== '') : ?>
            <div class="sx-log-entry__summary"><?php echo $entry->summary; ?></div>
        <?php endif; ?>

        <?php if ($entry->details) : ?>
            <div class="sx-log-entry__details">
                <?php foreach ($entry->details as $detail) : ?>
                    <div><?php echo $detail; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($entry->snippet !== '') : ?>
            <div class="sx-log-entry__snippet"><?php echo Html::encode($entry->snippet); ?></div>
        <?php endif; ?>

        <?php if ($entry->body !== '') : ?>
            <div class="sx-log-entry__body sx-comment-wrapper"><?php echo $entry->body; ?></div>
        <?php endif; ?>

        <?php if ($log->files) : ?>
            <?php
            $files = $log->files;
            $images = [];
            foreach ($files as $key => $file) {
                if ($file->isImage()) {
                    $images[] = $file;
                    unset($files[$key]);
                }
            }
            ?>
            <div class="sx-log-entry__files sx-files">
                <?php if ($images) : ?>
                    <div class="sx-log-entry__images">
                        <?php foreach ($images as $file) : ?>
                            <a href="<?php echo $file->src; ?>" target="_blank" data-pjax="0">
                                <img src="<?php echo $file->src; ?>" alt="<?php echo Html::encode((string)$file->original_name); ?>" loading="lazy"/>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php foreach ($files as $file) : ?>
                    <a class="sx-log-entry__file"
                       href="<?php echo \yii\helpers\Url::to(['/cms/admin-storage-files/download', 'pk' => $file->id]); ?>"
                       target="_blank" data-pjax="0"
                       title="Скачать">
                        <?php echo BackendIcon::render('file', ['size' => 14]); ?>
                        <span class="sx-log-entry__file-name"><?php echo Html::encode((string)$file->original_name); ?></span>
                        <span class="sx-log-entry__file-size"><?php echo \Yii::$app->formatter->asShortSize($file->size); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
