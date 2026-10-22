<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\source;

use skeeks\cms\backend\actions\BackendModelCreateAction;
use skeeks\yii2\form\fields\HtmlBlock;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\Html;

/**
 * "Создание из источника" for every standard create window
 * (BackendModelCreateAction): with `?source=<type>:<id>` the source
 * registered in Skeeks::$recordSources fills the new record, shows a line
 * above the form, sets its own attributes before validation and links the
 * saved record back in the same transaction.
 *
 * A source that is unknown, hidden from the employee or does not support the
 * model is ignored: the window works as without the parameter.
 */
class RecordSources
{
    /** Request parameter: "<type>:<id>", for example "message:15". */
    public const PARAM = 'source';

    /** @var \SplObjectStorage|null action => [source object, RecordSourceInterface, transaction] */
    private static $active;

    /** Class-level handlers of the create action (web application only). */
    public static function attach(): void
    {
        if (!class_exists(BackendModelCreateAction::class)) {
            return;
        }
        Event::on(BackendModelCreateAction::class, BackendModelCreateAction::EVENT_INIT_FORM_MODELS, [self::class, 'onInit']);
        Event::on(BackendModelCreateAction::class, BackendModelCreateAction::EVENT_BEFORE_VALIDATE, [self::class, 'onBeforeValidate']);
        Event::on(BackendModelCreateAction::class, BackendModelCreateAction::EVENT_BEFORE_SAVE, [self::class, 'onBeforeSave']);
        Event::on(BackendModelCreateAction::class, BackendModelCreateAction::EVENT_AFTER_SAVE, [self::class, 'onAfterSave']);
    }

    /**
     * @return array|null [type, id] of a "<type>:<id>" value
     */
    public static function parse($value): ?array
    {
        if (!is_string($value) || !preg_match('/^([a-z][a-z0-9_-]{0,31}):([A-Za-z0-9_.-]{1,64})$/', $value, $m)) {
            return null;
        }

        return [$m[1], $m[2]];
    }

    /**
     * The source of a "<type>:<id>" value for a new record of $modelClass,
     * checked by the source itself.
     *
     * @return array|null [object source, RecordSourceInterface provider]
     */
    public static function resolve($value, string $modelClass): ?array
    {
        $parsed = self::parse($value);
        if (!$parsed || !\Yii::$app->has('skeeks')) {
            return null;
        }
        $provider = \Yii::$app->skeeks->getRecordSource($parsed[0]);
        if (!$provider || !$provider->supports($modelClass)) {
            return null;
        }
        $source = $provider->find($parsed[1]);

        return $source ? [$source, $provider] : null;
    }

    /** "?source=" of a create window URL. */
    public static function urlParams(string $type, $id): array
    {
        return [self::PARAM => $type.':'.$id];
    }

    public static function onInit(Event $event): void
    {
        if (\Yii::$app->request instanceof \yii\web\Request) {
            self::start($event->sender, \Yii::$app->request->get(self::PARAM));
        }
    }

    /**
     * Applies a "<type>:<id>" source to a create action that is opening
     * (its model is new, its fields are not built yet).
     *
     * @param BackendModelCreateAction|object $action with `model` and `fields`
     * @return bool whether a source was applied
     */
    public static function start($action, $value): bool
    {
        $model = $action->model;
        if (!$model instanceof ActiveRecord || !$model->getIsNewRecord()) {
            return false;
        }
        $resolved = self::resolve($value, get_class($model));
        if (!$resolved) {
            return false;
        }
        [$source, $provider] = $resolved;
        self::storage()[$action] = [$source, $provider, null];
        $provider->prefill($source, $model);

        $note = $provider->describe($source);
        $fields = $action->fields;
        if ($note !== '' && $fields) {
            $action->fields = static function ($action) use ($fields, $note) {
                $resolvedFields = is_callable($fields) ? call_user_func($fields, $action) : $fields;

                return array_merge([
                    '__record_source' => [
                        'class' => HtmlBlock::class,
                        'content' => Html::tag('div', Html::encode($note), ['class' => 'sx-record-source-note']),
                    ],
                ], (array)$resolvedFields);
            };
            if (\Yii::$app->has('view') && \Yii::$app->view instanceof \yii\web\View) {
                \Yii::$app->view->registerCss('.sx-record-source-note{margin:0 0 16px;padding:10px 14px;border-radius:var(--sx-radius,8px);'
                    .'background:var(--sx-color-surface-muted,rgba(127,127,127,.08));color:var(--sx-color-text-muted,inherit);font-size:13px}');
            }
        }

        return true;
    }

    public static function onBeforeValidate(Event $event): void
    {
        if ($entry = self::entry($event->sender)) {
            $entry[1]->enforce($entry[0], $event->sender->model);
        }
    }

    public static function onBeforeSave(Event $event): void
    {
        $action = $event->sender;
        if ($entry = self::entry($action)) {
            $entry[2] = $action->model->getDb()->beginTransaction();
            self::storage()[$action] = $entry;
        }
    }

    public static function onAfterSave(Event $event): void
    {
        $action = $event->sender;
        $entry = self::entry($action);
        if (!$entry) {
            return;
        }
        self::storage()->detach($action);
        /** @var \yii\db\Transaction|null $transaction */
        $transaction = $entry[2];
        try {
            $entry[1]->created($entry[0], $action->model);
            if ($transaction) {
                $transaction->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction && $transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    private static function entry($action): ?array
    {
        $storage = self::storage();

        return $storage->contains($action) ? $storage[$action] : null;
    }

    private static function storage(): \SplObjectStorage
    {
        if (self::$active === null) {
            self::$active = new \SplObjectStorage();
        }

        return self::$active;
    }
}
