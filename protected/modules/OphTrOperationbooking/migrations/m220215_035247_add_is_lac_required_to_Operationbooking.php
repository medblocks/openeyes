<?php

class m220215_035247_add_is_lac_required_to_Operationbooking extends OEMigration
{
    public function up()
    {

        $this->insert('setting_metadata', array(
            'display_order' => 25,
            'field_type_id' => 3,
            'key' => 'op_booking_disable_lac_required',
            'name' => 'Disable "Anaesthetic Cover Required"',
            'data' => serialize(array('on' => 'On', 'off' => 'Off')),
            'default_value' => 'on'
        ));
        $this->insert('setting_installation', array(
            'key' => 'op_booking_disable_lac_required',
            'value' => 'on'
        ));

        $this->addOEColumn('et_ophtroperationbooking_operation', 'is_lac_required', 'tinyint(1) unsigned after is_golden_patient', true);
    }

    public function down()
    {
        $this->dropOEColumn('et_ophtroperationbooking_operation', 'is_lac_required', true);

        $this->delete('setting_installation', '`key`="op_booking_disable_lac_required"');
        $this->delete('setting_metadata', '`key`="op_booking_disable_lac_required"');
    }
}
