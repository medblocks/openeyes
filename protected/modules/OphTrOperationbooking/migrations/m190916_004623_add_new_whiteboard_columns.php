<?php

class m190916_004623_add_new_whiteboard_columns extends OEMigration
{
    public function up()
    {
        $this->addColumn(
            'ophtroperationbooking_whiteboard',
            'axial_length',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard',
            'acd',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard',
            'formula',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard',
            'aconst',
            'varchar(100)'
        );

        $this->addColumn(
            'ophtroperationbooking_whiteboard_version',
            'axial_length',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard_version',
            'acd',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard_version',
            'formula',
            'varchar(100)'
        );
        $this->addColumn(
            'ophtroperationbooking_whiteboard_version',
            'aconst',
            'varchar(100)'
        );
    }

    public function down()
    {
        $this->dropColumn(
            'ophtroperationbooking_whiteboard',
            'axial_length'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard',
            'acd'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard',
            'formula'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard',
            'aconst'
        );

        $this->dropColumn(
            'ophtroperationbooking_whiteboard_version',
            'axial_length'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard_version',
            'acd'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard_version',
            'formula'
        );
        $this->dropColumn(
            'ophtroperationbooking_whiteboard_version',
            'aconst'
        );
    }
}