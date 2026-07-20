<?php

use yii\db\Migration;

class m260720_180000__add_html_content_tree_type extends Migration
{
    public function safeUp()
    {
        $tableName = '{{%cms_tree_type}}';
        if (!$this->db->getTableSchema($tableName, true)) {
            return true;
        }

        $htmlContentExists = (bool)(new \yii\db\Query())
            ->from($tableName)
            ->andWhere(['code' => 'html-content'])
            ->exists($this->db);
        if ($htmlContentExists) {
            return true;
        }

        $attributes = [
            'name' => 'HTML контент',
            'code' => 'html-content',
            'description' => 'Чистая HTML-страница без контейнеров, хлебных крошек и дополнительных блоков темы.',
            'name_meny' => 'Страницы',
            'name_one' => 'Страница',
            'view_file' => '',
            'is_active' => 1,
            'updated_at' => time(),
        ];

        $attributes['created_at'] = time();
        $attributes['priority'] = 500;
        $this->insert($tableName, $attributes);
        return true;
    }

    public function safeDown()
    {
        echo "m260720_180000__add_html_content_tree_type cannot be reverted safely.\n";
        return false;
    }
}
