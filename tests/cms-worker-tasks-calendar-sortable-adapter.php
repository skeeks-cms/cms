<?php

function workerTasksCalendarSortableExpect($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$view = file_get_contents(dirname(__DIR__).'/src/widgets/admin/views/worker-tasks-calendar.php');

workerTasksCalendarSortableExpect(
    strpos($view, 'BackendSortableAdapterAsset::register($this);') !== false,
    'Worker tasks calendar does not register the backend Sortable adapter.'
);
workerTasksCalendarSortableExpect(
    strpos($view, 'sx.backend.sortable.create(') !== false
    && strpos($view, 'itemSelector: "> .sx-task-tr"') !== false
    && strpos($view, 'handle: ".sx-move-btn"') !== false,
    'Worker tasks calendar does not sort task rows through their move handle.'
);
workerTasksCalendarSortableExpect(
    strpos($view, 'group: "cms-worker-task-calendar-" + this.get(\'id\')') !== false,
    'Worker tasks calendar does not connect only the lists of its own widget.'
);
workerTasksCalendarSortableExpect(
    strpos($view, 'event.from !== event.to') !== false
    && strpos($view, '$(event.to).children(".sx-task-tr").length') !== false,
    'Worker tasks calendar does not preserve the dropOnEmpty=false behavior.'
);
workerTasksCalendarSortableExpect(
    strpos($view, 'onUpdate: function()') !== false
    && strpos($view, 'self.jSavePriorityButton.fadeIn();') !== false,
    'Worker tasks calendar does not expose saving after a completed reorder.'
);
workerTasksCalendarSortableExpect(
    strpos($view, '\\yii\\jui\\Sortable::widget()') === false
    && strpos($view, '.sortable(') === false
    && strpos($view, 'connectWith:') === false,
    'Worker tasks calendar still uses jQuery UI Sortable directly.'
);

echo "CMS worker tasks calendar sortable adapter contract: OK\n";
