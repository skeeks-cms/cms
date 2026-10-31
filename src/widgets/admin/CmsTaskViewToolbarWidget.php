<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\widgets\admin;

use skeeks\cms\assets\admin\TaskCalendarAsset;
use skeeks\cms\models\CmsTask;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * One toolbar of every task view: the view's own controls on the left (period, scale), then the employee
 * (when the viewer may choose), the search and «Добавить задачу» — the same order, size and behaviour everywhere.
 */
class CmsTaskViewToolbarWidget extends Widget
{
    /** @var TaskViewContext */
    public $context;

    /** @var string controls of the view on the left (period navigation, a title) */
    public $start = '';

    /** @var string controls of the view before the search (scale, grouping) */
    public $tools = '';

    /** @var bool */
    public $showAdd = true;

    public function run()
    {
        TaskCalendarAsset::register($this->view);
        $request = \Yii::$app->request;

        $hidden = '';
        foreach ((array)$request->get() as $name => $value) {
            if (in_array($name, ['q', 'executor_id'], true) || is_array($value)) {
                continue;
            }
            $hidden .= Html::hiddenInput($name, (string)$value);
        }
        foreach ((array)$request->get() as $name => $value) {
            if (is_array($value) && $name !== 'q') {
                foreach ($value as $key => $item) {
                    if (!is_array($item)) {
                        $hidden .= Html::hiddenInput($name.'['.$key.']', (string)$item);
                    }
                }
            }
        }

        $user = '';
        if ($this->context->users) {
            $user = Html::dropDownList('executor_id', (int)$this->context->user->id,
                ArrayHelper::map($this->context->users, 'id', function ($u) {
                    return (int)$u->id === (int)\Yii::$app->user->id ? $u->shortDisplayName.' (я)' : $u->shortDisplayName;
                }),
                ['class' => 'form-control form-control-sm sx-task-toolbar__user', 'onchange' => 'this.form.submit()', 'title' => 'Чьи задачи']);
        }

        $search = Html::tag('div',
            Html::tag('i', '', ['class' => 'fas fa-search']).
            Html::input('search', 'q', $this->context->q, [
                'class'       => 'form-control form-control-sm',
                'placeholder' => 'Поиск: задача, клиент, проект, №',
                'autocomplete'=> 'off',
            ]), ['class' => 'sx-task-toolbar__search']);

        return Html::tag('form',
            $hidden
            .Html::tag('div', $this->start, ['class' => 'sx-task-toolbar__start'])
            .Html::tag('div', $this->tools.$user.$search.($this->showAdd ? $this->addButton() : ''), ['class' => 'sx-task-toolbar__end']),
            ['class' => 'sx-task-toolbar', 'method' => 'get', 'action' => '']);
    }

    protected function addButton(): string
    {
        $controller = \Yii::$app->createController('/cms/admin-cms-task');
        $createAction = $controller ? ArrayHelper::getValue($controller[0]->actions, 'create') : null;
        if (!$createAction) {
            return '';
        }
        $createAction->url = ArrayHelper::merge($createAction->urlData, [
            'CmsTask' => ['executor_id' => (int)$this->context->user->id],
        ]);
        $createAction->name = 'Добавить задачу';

        return \skeeks\cms\backend\widgets\ControllerActionsWidget::widget([
            'actions'         => [$createAction],
            'isOpenNewWindow' => true,
            'minViewCount'    => 1,
            'itemTag'         => 'button',
            'itemOptions'     => ['class' => 'btn btn-primary btn-sm sx-task-toolbar__add', 'type' => 'button'],
        ]);
    }
}
