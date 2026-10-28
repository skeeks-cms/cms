<?php

use yii\db\Migration;

/**
 * П2а: the level of an employee's link to a company or a client — "deal"
 * (own deals), "edit" (own deals and the company data), "full". Existing links
 * are full; requests keep the level. The main responsible and the former
 * "sales" → "edit": m261028_140000.
 */
class m261028_110000__alter_table__crm_access_levels extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%cms_company2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumn('{{%cms_user2manager}}', 'access_level', $this->string(16)->notNull()->defaultValue('full')->comment('Уровень: deal, edit, full'));
        $this->addColumn('{{%cms_crm_access_request}}', 'level', $this->string(16)->null()->comment('Уровень: deal, edit, full'));
    }

    public function safeDown()
    {
        $this->refuseRollbackWithData([
            'уровни доступа к компаниям ниже полного' => (new \yii\db\Query())->from('{{%cms_company2manager}}')->andWhere(['<>', 'access_level', 'full']),
            'уровни доступа к клиентам ниже полного' => (new \yii\db\Query())->from('{{%cms_user2manager}}')->andWhere(['<>', 'access_level', 'full']),
            'запросы с уровнем' => (new \yii\db\Query())->from('{{%cms_crm_access_request}}')->andWhere(['not', ['level' => null]]),
        ]);
        $this->dropColumn('{{%cms_crm_access_request}}', 'level');
        $this->dropColumn('{{%cms_user2manager}}', 'access_level');
        $this->dropColumn('{{%cms_company2manager}}', 'access_level');
    }

    /**
     * MySQL does not roll DDL back, so the data this rollback would destroy is
     * checked before the first DDL (release audit F06, 29.10.2026): with any of
     * it the rollback is refused and the schema stays as it is.
     *
     * @param array $checks label => a query counting the rows that would be lost
     */
    private function refuseRollbackWithData(array $checks): void
    {
        $found = [];
        foreach ($checks as $label => $query) {
            $count = (int)$query->count('*', $this->db);
            if ($count > 0) {
                $found[] = $label.': '.$count;
            }
        }
        if ($found) {
            throw new \yii\base\Exception('Откат невозможен: он безвозвратно удалит данные CRM ('.implode('; ', $found).'). Схема не изменена. '
                .'Чтобы откатить: снимите дамп базы (mysqldump) и сохраните эти данные; если они действительно не нужны, удалите их вручную '
                .'и повторите откат. Вернуть их можно только из резервной копии базы.');
        }
    }
}
