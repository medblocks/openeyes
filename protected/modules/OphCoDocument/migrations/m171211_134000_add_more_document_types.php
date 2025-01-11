<?php

class m171211_134000_add_more_document_types extends OEMigration
{
    public function up()
    {
        $isVF = $this->dbConnection->createCommand()
            ->select('id')
            ->from('ophcodocument_sub_types')
            ->where('name = :name', [':name' => 'Visual Field Report'])
            ->queryRow();

        if (!$isVF || $isVF['id'] === '') {
            $this->insert('ophcodocument_sub_types', [
                'name' => 'Visual Field Report',
                'display_order' => '8',
            ]);
        }

        $isLid = $this->dbConnection->createCommand()
            ->select('id')
            ->from('ophcodocument_sub_types')
            ->where('name = :name', [':name' => 'Lids Photo'])
            ->queryRow();

        if (!$isLid || $isLid['id'] === '') {
            $this->insert('ophcodocument_sub_types', [
                'name' => 'Lids Photo',
                'display_order' => '9',
            ]);
        }

        $isOrb = $this->dbConnection->createCommand()
            ->select('id')
            ->from('ophcodocument_sub_types')
            ->where('name = :name', [':name' => 'Orbit Photo'])
            ->queryRow();

        if (!$isOrb || $isOrb['id'] === '') {
            $this->insert('ophcodocument_sub_types', [
                'name' => 'Orbit Photo',
                'display_order' => '10',
            ]);
        }
    }

    public function down()
    {
        echo "Not supported here!\n";
        return true;
    }

    /*
    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
    }

    public function safeDown()
    {
    }
    */
}
