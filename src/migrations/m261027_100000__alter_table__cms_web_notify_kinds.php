<?php

use yii\db\Migration;

/**
 * Notifications, step У4a: a notification has a kind of event, its category
 * and importance; related events share a group, one event is stored once per
 * recipient (event_key), a burst updates one row (count, updated_at). The
 * badge counts what the recipient has not seen yet (is_seen), the highlight —
 * what they have not read.
 *
 * Old rows get the category of their task texts and records, kinds where the
 * text is known.
 */
class m261027_100000__alter_table__cms_web_notify_kinds extends Migration
{
    public function safeUp()
    {
        $table = '{{%cms_web_notify}}';
        $this->addColumn($table, 'updated_at', $this->integer()->null()->after('created_at'));
        $this->addColumn($table, 'kind', $this->string(64)->null()->after('cms_user_id')->comment('Вид события'));
        $this->addColumn($table, 'category', $this->string(32)->null()->after('kind')->comment('Категория'));
        $this->addColumn($table, 'importance', $this->tinyInteger(1)->notNull()->defaultValue(0)->after('category')->comment('Важность: 0 обычное, 1 важное, 2 срочное'));
        $this->addColumn($table, 'group_key', $this->string(128)->null()->after('importance')->comment('Связанные события'));
        $this->addColumn($table, 'event_key', $this->string(128)->null()->after('group_key')->comment('Одно событие у получателя'));
        $this->addColumn($table, 'count', $this->integer()->notNull()->defaultValue(1)->after('event_key')->comment('Сколько событий в записи'));
        $this->addColumn($table, 'is_seen', $this->integer(1)->notNull()->defaultValue(0)->after('is_read')->comment('Видел в окне'));

        $this->createIndex('cms_web_notify__user_event', $table, ['cms_user_id', 'event_key'], true);
        $this->createIndex('cms_web_notify__user_group', $table, ['cms_user_id', 'group_key']);
        $this->createIndex('cms_web_notify__user_category', $table, ['cms_user_id', 'category', 'is_read']);

        $this->update($table, ['is_seen' => 1], ['is_read' => 1]);
        $this->execute("UPDATE {$this->db->quoteSql($table)} SET updated_at = created_at");

        $task = 'skeeks\\cms\\models\\CmsTask';
        $kinds = [
            'Вам поставлена новая задача' => 'task.created',
            'Вам передали задачу' => 'task.passed',
            'Вам необходимо проверить задачу' => 'task.check',
            'Ваша задача готова' => 'task.ready',
            'Задача отменена' => 'task.canceled',
            'Задача возобновлена' => 'task.resumed',
            'Добавлен новый комментарий к задаче' => 'task.comment',
            'Новая неразобранная задача от клиента' => 'task.client_unassigned',
        ];
        foreach ($kinds as $name => $kind) {
            $this->update($table, ['kind' => $kind, 'category' => 'tasks'], ['name' => $name]);
        }
        $this->update($table, ['kind' => 'task.order', 'category' => 'tasks'], ['like', 'name', 'поменял(а) порядок ваших задач']);
        $this->update($table, ['kind' => 'lead.new', 'category' => 'clients', 'importance' => 1], ['name' => 'Появился новый лид']);
        $this->update($table, ['category' => 'tasks'], ['category' => null, 'model_code' => $task]);
        $this->update($table, ['category' => 'clients'], ['category' => null, 'model_code' => [
            'skeeks\\cms\\models\\CmsLead', 'skeeks\\cms\\proposal\\models\\CmsProposal',
        ]]);
        $this->update($table, ['category' => 'messages'], ['category' => null, 'model_code' => [
            'skeeks\\cms\\communication\\models\\CmsCommMessage', 'skeeks\\cms\\communication\\models\\CmsCommConversation',
        ]]);
        $this->update($table, ['category' => 'finance'], ['category' => null, 'model_code' => [
            'skeeks\\cms\\shop\\models\\ShopPartnerPayout', 'skeeks\\cms\\shop\\models\\ShopBonusTransaction',
        ]]);
        $this->update($table, ['category' => 'other'], ['category' => null]);
    }

    public function safeDown()
    {
        $table = '{{%cms_web_notify}}';
        $this->dropIndex('cms_web_notify__user_category', $table);
        $this->dropIndex('cms_web_notify__user_group', $table);
        $this->dropIndex('cms_web_notify__user_event', $table);
        foreach (['is_seen', 'count', 'event_key', 'group_key', 'importance', 'category', 'kind', 'updated_at'] as $column) {
            $this->dropColumn($table, $column);
        }
    }
}
