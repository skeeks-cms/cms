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
 * One toolbar of every task view, the same order, size and behaviour everywhere (owner, 31.10.2026):
 * the first row — the search, the employee (when the viewer may choose) and «Добавить задачу» on the right;
 * the second row — the view's own controls (period, title, grouping, scale), only when the view has them.
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

    /** @var string hidden controls that appear on their own (the floating «Сохранить порядок задач») */
    public $floating = '';

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

        // The search of list filters: the same icon, a submit button on the right.
        $clear = '';
        if ($this->context->q !== '') {
            $clear = Html::a('&times;', \yii\helpers\Url::current(['q' => null]), [
                'class'     => 'sx-task-toolbar__search-clear',
                'title'     => 'Сбросить поиск',
                'data-pjax' => '0',
            ]);
        }
        $search = Html::tag('div',
            Html::input('text', 'q', $this->context->q, [
                'class'       => 'form-control form-control-sm',
                'placeholder' => 'Поиск: задача, клиент, проект, №',
                'autocomplete'=> 'off',
            ])
            .Html::tag('button', \skeeks\cms\backend\helpers\BackendIcon::render('search', ['size' => 16]), [
                'type'  => 'submit',
                'class' => 'sx-task-toolbar__search-btn',
                'title' => 'Найти',
            ]).$clear, ['class' => 'sx-task-toolbar__search'.($clear ? ' is-filled' : '')]);

        return Html::tag('form',
            $hidden
            .$this->floating
            .Html::tag('div',
                Html::tag('div', $search.$user, ['class' => 'sx-task-toolbar__start'])
                .Html::tag('div', $this->showAdd ? $this->addButton() : '', ['class' => 'sx-task-toolbar__end']),
                ['class' => 'sx-task-toolbar__row'])
            .($this->start !== '' || $this->tools !== '' ? Html::tag('div',
                ($this->start !== '' ? Html::tag('div', $this->start, ['class' => 'sx-task-toolbar__group']) : '')
                .($this->tools !== '' ? Html::tag('div', $this->tools, ['class' => 'sx-task-toolbar__group']) : ''),
                ['class' => 'sx-task-toolbar__row sx-task-toolbar__row--view']) : ''),
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
