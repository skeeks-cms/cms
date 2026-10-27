<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 */

namespace skeeks\cms\notify;

use skeeks\cms\backend\helpers\BackendIcon;
use skeeks\cms\models\CmsWebNotify;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Html;

/**
 * The bell window of one recipient: sections by category, related events
 * shown as one entry (group_key), the badge and the actions of an entry.
 *
 * An entry is a group: its first event is the main line (the cause, e.g.
 * "клиент открыл презентацию"), later ones are the lines under it (the task
 * that followed). An action on an entry applies to the whole group.
 */
class NotifyList
{
    /** @var int */
    public $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function query()
    {
        return CmsWebNotify::find()->andWhere(['cms_user_id' => $this->userId]);
    }

    /** The badge: what the person has neither read nor seen in the window. */
    public function badge(): int
    {
        return (int)$this->query()->andWhere(['is_read' => 0, 'is_seen' => 0])->count();
    }

    /** Opening the window: the badge is reset, the highlight of unread entries stays. */
    public function markSeen(): void
    {
        CmsWebNotify::updateAll(['is_seen' => 1], ['cms_user_id' => $this->userId, 'is_seen' => 0]);
    }

    /**
     * Sections that have notifications: code => ['name', 'total', 'unread'].
     */
    public function tabs(): array
    {
        $rows = $this->query()
            ->select(['category', 'total' => new Expression('COUNT(*)'), 'unread' => new Expression('SUM(is_read = 0)')])
            ->groupBy('category')->asArray()->all();
        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['category'] ?: Notifications::CATEGORY_OTHER] = $row;
        }
        $result = [];
        foreach (\Yii::$app->notifications->categoryList as $code => $category) {
            if (isset($counts[$code])) {
                $result[$code] = ['name' => $category['name'], 'total' => (int)$counts[$code]['total'], 'unread' => (int)$counts[$code]['unread']];
                unset($counts[$code]);
            }
        }
        foreach ($counts as $code => $row) {
            $result[$code] = ['name' => \Yii::$app->notifications->categoryName($code), 'total' => (int)$row['total'], 'unread' => (int)$row['unread']];
        }

        return $result;
    }

    /**
     * A page of entries, newest first by the last event of an entry.
     *
     * @param string|null $after cursor of the previous page ("<updated_at>:<id>")
     * @return array ['entries' => CmsWebNotify[][], 'next' => string|null]
     */
    public function page(?string $category = null, ?string $after = null, int $limit = 20): array
    {
        // An entry is a group (or a single row): pages go by entries, so a group never repeats.
        $key = new Expression("COALESCE(group_key, CONCAT('id:', id))");
        $last = new Expression('MAX(COALESCE(updated_at, created_at))');
        $query = (new Query())->from(CmsWebNotify::tableName())
            ->select(['gk' => $key, 'last' => $last, 'mid' => new Expression('MAX(id)')])
            ->andWhere(['cms_user_id' => $this->userId])
            ->groupBy($key)
            ->orderBy(['last' => SORT_DESC, 'mid' => SORT_DESC])
            ->limit($limit + 1);
        if ($category) {
            $query->andWhere(['category' => $category]);
        }
        if ($after && preg_match('/^(\d+):(\d+)$/', $after, $m)) {
            $query->having('MAX(COALESCE(updated_at, created_at)) < :t OR (MAX(COALESCE(updated_at, created_at)) = :t AND MAX(id) < :i)',
                [':t' => (int)$m[1], ':i' => (int)$m[2]]);
        }
        $groups = $query->all();
        $next = null;
        if (count($groups) > $limit) {
            $groups = array_slice($groups, 0, $limit);
            $tail = end($groups);
            $next = (int)$tail['last'].':'.(int)$tail['mid'];
        }

        $ids = [];
        $groupKeys = [];
        foreach ($groups as $group) {
            if (strpos((string)$group['gk'], 'id:') === 0) {
                $ids[] = (int)substr((string)$group['gk'], 3);
            } else {
                $groupKeys[] = (string)$group['gk'];
            }
        }
        $rows = $groups ? $this->query()->andWhere(['or', ['id' => $ids ?: [0]], ['group_key' => $groupKeys ?: ['']]])->all() : [];
        $byKey = [];
        foreach ($rows as $row) {
            $byKey[$row->group_key ?: 'id:'.$row->id][] = $row;
        }
        $entries = [];
        foreach ($groups as $group) {
            $entry = $byKey[(string)$group['gk']] ?? [];
            if (!$entry) {
                continue;
            }
            usort($entry, static fn(CmsWebNotify $a, CmsWebNotify $b) => [(int)$a->created_at, (int)$a->id] <=> [(int)$b->created_at, (int)$b->id]);
            $entries[] = $entry;
        }

        return ['entries' => $entries, 'next' => $next];
    }

    /**
     * @param CmsWebNotify[] $rows a group, the first is the main event
     */
    public static function render(array $rows): string
    {
        $main = $rows[0];
        $unread = false;
        $importance = 0;
        $last = 0;
        $mutable = false;
        foreach ($rows as $row) {
            $unread = $unread || !$row->is_read;
            $importance = max($importance, (int)$row->importance);
            $last = max($last, (int)$row->updated_at ?: (int)$row->created_at);
            $mutable = $mutable || $row->isMutable;
        }
        [$name, $modelHtml] = $main->renderParts();
        $time = \Yii::$app->formatter->asRelativeTime($last);
        $meta = Html::tag('span', $time, ['class' => 'sx-time']);
        $meta .= Html::tag('span', Html::encode($main->categoryName), ['class' => 'sx-notify-entry__category']);
        if ($importance >= Notifications::IMPORTANCE_IMPORTANT) {
            $meta .= Html::tag('span', $importance >= Notifications::IMPORTANCE_URGENT ? 'Срочно' : 'Важно', ['class' => 'sx-notify-entry__importance']);
        }
        if ((int)$main->count > 1) {
            $meta .= Html::tag('span', '×'.(int)$main->count, ['class' => 'sx-notify-entry__count', 'title' => 'Событий: '.(int)$main->count]);
        }

        $body = Html::tag('div', $meta, ['class' => 'sx-notify-entry__meta'])
            .Html::tag('div', $name, ['class' => 'sx-name']);
        if ($main->comment) {
            $body .= Html::tag('div', Html::encode(mb_strimwidth((string)$main->comment, 0, 200, '…')), ['class' => 'sx-notify-entry__comment']);
        }
        if ($modelHtml !== '') {
            $body .= Html::tag('div', $modelHtml, ['class' => 'sx-model']);
        }
        foreach (array_slice($rows, 1) as $row) {
            [$childName, $childModel] = $row->renderParts();
            $line = Html::tag('span', $childName, ['class' => 'sx-notify-entry__child-name']);
            if ($childModel !== '' && $row->model_code.'#'.$row->model_id !== $main->model_code.'#'.$main->model_id) {
                $line .= Html::tag('span', $childModel, ['class' => 'sx-notify-entry__child-model']);
            }
            $body .= Html::tag('div', $line, ['class' => 'sx-notify-entry__child']);
        }

        $actions = Html::button(BackendIcon::render('more-vertical', ['size' => 16]), [
                'class' => 'sx-notify-entry__menu-toggle',
                'aria-label' => 'Действия',
                'aria-haspopup' => 'menu',
                'aria-expanded' => 'false',
            ])
            .Html::button(BackendIcon::render('close', ['size' => 16]), [
                'class' => 'sx-notify-entry__delete',
                'data-sx-notify-action' => 'delete',
                'aria-label' => 'Удалить уведомление',
                'title' => 'Удалить',
            ]);
        $menu = Html::button($unread ? 'Отметить прочитанным' : 'Отметить непрочитанным', [
            'role' => 'menuitem',
            'data-sx-notify-action' => $unread ? 'read' : 'unread',
        ]);
        if ($mutable) {
            $menu .= Html::button('Не уведомлять о таком', ['role' => 'menuitem', 'data-sx-notify-action' => 'mute']);
        }
        $menu .= Html::button('Удалить', ['role' => 'menuitem', 'data-sx-notify-action' => 'delete']);

        return Html::tag('div',
            Html::tag('div', $body, ['class' => 'sx-item-inner'])
            .Html::tag('div', $actions.Html::tag('div', $menu, ['class' => 'sx-notify-entry__menu', 'role' => 'menu']), ['class' => 'sx-notify-entry__actions']),
            [
                'class' => ['sx-item', 'sx-notify-entry', $unread ? 'sx-not-read' : '', $importance >= Notifications::IMPORTANCE_IMPORTANT ? 'sx-notify-entry--important' : ''],
                'data-sx-notify-id' => (int)$main->id,
            ]);
    }

    /**
     * An action on the entry of a notification: read, unread, delete, mute
     * (the kind is switched off and the entry is removed).
     */
    public function act(int $id, string $action): bool
    {
        $notify = $this->query()->andWhere(['id' => $id])->one();
        if (!$notify) {
            return false;
        }
        $where = $notify->group_key
            ? ['cms_user_id' => $this->userId, 'group_key' => $notify->group_key]
            : ['cms_user_id' => $this->userId, 'id' => (int)$notify->id];
        switch ($action) {
            case 'read':
                CmsWebNotify::updateAll(['is_read' => 1, 'is_seen' => 1], $where);
                return true;
            case 'unread':
                CmsWebNotify::updateAll(['is_read' => 0], $where);
                return true;
            case 'delete':
                CmsWebNotify::deleteAll($where);
                return true;
            case 'mute':
                if (!$notify->kind || !\Yii::$app->notifications->mute($this->userId, (string)$notify->kind)) {
                    return false;
                }
                CmsWebNotify::deleteAll($where);
                return true;
        }

        return false;
    }

    public function readAll(?string $category = null): int
    {
        $where = ['and', ['cms_user_id' => $this->userId, 'is_read' => 0]];
        if ($category) {
            $where[] = ['category' => $category];
        }

        return CmsWebNotify::updateAll(['is_read' => 1, 'is_seen' => 1], $where);
    }

    public function clear(?string $category = null): int
    {
        $where = ['cms_user_id' => $this->userId];
        if ($category) {
            $where['category'] = $category;
        }

        return CmsWebNotify::deleteAll($where);
    }
}
