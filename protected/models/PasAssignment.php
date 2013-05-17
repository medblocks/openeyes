<?php
/**
 * OpenPasAssignments
 *
 * (C) Moorfields PasAssignment Hospital NHS Foundation Trust, 2008-2011
 * (C) OpenPasAssignments Foundation, 2011-2013
 * This file is part of OpenPasAssignments.
 * OpenPasAssignments is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenPasAssignments is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with OpenPasAssignments in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenPasAssignments
 * @link http://www.openeyes.org.uk
 * @author OpenPasAssignments <info@openeyes.org.uk>
 * @copyright Copyright (c) 2008-2011, Moorfields PasAssignment Hospital NHS Foundation Trust
 * @copyright Copyright (c) 2011-2013, OpenPasAssignments Foundation
 * @license http://www.gnu.org/licenses/gpl-3.0.html The GNU General Public License V3.0
 */

/**
 * This is the model class for table "eye".
 *
 * The followings are the available columns in table 'pas_assignment':
 * @property string $id
 * @property string $name
 * @property string $ShortName
 */
class PasAssignment extends BaseActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return Firm the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'pas_assignment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		return array();
	}
}
