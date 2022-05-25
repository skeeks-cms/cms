<?php
/**
 * @author Semenov Alexander <semenov@skeeks.com>
 * @link http://skeeks.com/
 * @copyright 2010 SkeekS (СкикС)
 * @date 28.08.2015
 */

use yii\db\Migration;

class m220127_230601__update_data__user_email_and_phone extends Migration
{
    public function safeUp()
    {
        $this->db->createCommand(<<<SQL
INSERT IGNORE
    INTO cms_user_email (`cms_site_id`,`cms_user_id`,`value`, `is_approved`)
SELECT
    u.cms_site_id,
    u.id,
    u.email,
    u.email_is_approved
FROM cms_user as u
WHERE
u.email is not null
;
SQL
        )->execute();

        $this->db->createCommand(<<<SQL
INSERT IGNORE
    INTO cms_user_phone (`cms_site_id`,`cms_user_id`,`value`, `is_approved`)
SELECT
    u.cms_site_id,
    u.id,
    u.phone,
    u.phone_is_approved
FROM cms_user as u
WHERE
u.phone is not null
;
SQL
        )->execute();

    }

    public function safeDown()
    {
        echo "m200507_110601__create_table__shop_product_relation cannot be reverted.\n";
        return false;
    }
}