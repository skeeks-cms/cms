<?php
/**
 * m140801_201442_create_user_table
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010-2014 SkeekS (Sx)
 * @date 28.10.2014
 * @since 1.0.0
 */

use yii\db\Schema;
use yii\db\Migration;

/**
 * Class m171121_201442_alter__view_file
 */
class m180109_224042_update__cms_content_element_property extends Migration
{
    public function safeUp()
    {
        if ($this->db->driverName === 'pgsql') {
            $this->update('{{%cms_content_element_property}}', [
                'value_enum' => new \yii\db\Expression('value::INTEGER'),
                'value_num' => new \yii\db\Expression('value::FLOAT'),
                'value_string' => new \yii\db\Expression('value::TEXT'),
            ]);
        } else {
            $this->update('{{%cms_content_element_property}}', [
                'value_enum' => new \yii\db\Expression('value'),
                'value_num' => new \yii\db\Expression('value'),
                'value_string' => new \yii\db\Expression('value'),
            ]);
        }
    }

    public function down()
    {
        echo "m180109_224042_update__cms_content_element_property cannot be reverted.\n";
        return false;
    }
}
