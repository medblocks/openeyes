<?php
/**
 * (C) Copyright Apperta Foundation 2021
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2021, Apperta Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

class m160727_120033_add_new_columns_ophcocvi_clinicinfo_disorder extends CDbMigration
{
    public function up()
    {
        $this->addColumn('ophcocvi_clinicinfo_disorder', 'code', 'varchar(20) NOT NULL AFTER `name` ');
        $this->addColumn('ophcocvi_clinicinfo_disorder', 'section_id', 'int(10) unsigned NOT NULL AFTER `code` ');
        $this->addColumn('ophcocvi_clinicinfo_disorder', 'active', 'tinyint(1) unsigned not null default 1 AFTER `section_id` ');
        $this->addColumn('ophcocvi_clinicinfo_disorder_version', 'code', 'varchar(20) NOT NULL AFTER `name` ');
        $this->addColumn('ophcocvi_clinicinfo_disorder_version', 'section_id', 'int(12) NOT NULL AFTER `code` ');
        $this->addColumn('ophcocvi_clinicinfo_disorder_version', 'active', 'tinyint(1) unsigned not null default 1 AFTER `section_id`');
    }

    public function down()
    {
        $this->dropColumn('ophcocvi_clinicinfo_disorder', 'active');
        $this->dropColumn('ophcocvi_clinicinfo_disorder', 'code');
        $this->dropColumn('ophcocvi_clinicinfo_disorder', 'section_id');
        $this->dropColumn('ophcocvi_clinicinfo_disorder_version', 'active');
        $this->dropColumn('ophcocvi_clinicinfo_disorder_version', 'code');
        $this->dropColumn('ophcocvi_clinicinfo_disorder_version', 'section_id');
    }
}
