<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\source;

use yii\db\ActiveRecord;

/**
 * A source a new CRM record is created from: a letter, a call, a lead.
 * The standard create window of any backend model opens with
 * `?source=<type>:<id>` (RecordSources::PARAM); the registered source fills
 * the new record and links it back after saving. Core knows only this
 * contract; the package that owns the source implements it.
 *
 * Register it in the `skeeks` component:
 *
 * ```php
 * 'skeeks' => [
 *     'recordSources' => ['message' => MessageRecordSource::class],
 * ],
 * ```
 *
 * Every method runs in the web request of the current employee; the source
 * checks its own permissions in find().
 */
interface RecordSourceInterface
{
    /**
     * The source for the current employee, or null when it does not exist
     * or the employee may not see it.
     *
     * @param string $id the part after "<type>:"
     * @return object|null
     */
    public function find(string $id);

    /** Whether records of this class can be created from the source. */
    public function supports(string $modelClass): bool;

    /**
     * Fills a new record when the create form opens. The employee may change
     * everything filled here: posted form values replace it.
     *
     * @param object $source
     */
    public function prefill($source, ActiveRecord $model): void;

    /**
     * Sets attributes owned by the source right before validation, after the
     * controller's own handlers (which may reset fields the form does not
     * show, such as the company of a new lead).
     *
     * @param object $source
     */
    public function enforce($source, ActiveRecord $model): void;

    /**
     * The record is saved: link it to the source and record that in its
     * feed. Runs in the transaction of the save; an exception rolls both back.
     *
     * @param object $source
     */
    public function created($source, ActiveRecord $model): void;

    /**
     * One line above the create form, plain text: "Из письма «Тема» от Ивана".
     *
     * @param object $source
     */
    public function describe($source): string;
}
