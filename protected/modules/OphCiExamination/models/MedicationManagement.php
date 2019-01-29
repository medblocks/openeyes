<?php

/**
 * OpenEyes.
 *
 * (C) OpenEyes Foundation, 2018
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2018, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

namespace OEModule\OphCiExamination\models;

/**
 * This is the model class for table "et_ophciexamination_medicationmanagement".
 *
 * The followings are the available columns in table 'et_ophciexamination_medicationmanagement':
 * @property integer $id
 * @property string $event_id
 * @property string $last_modified_user_id
 * @property string $last_modified_date
 * @property string $created_user_id
 * @property string $created_date
 *
 * The followings are the available model relations:
 * @property \Event $event
 * @property \User $createdUser
 * @property \User $lastModifiedUser
 */
class MedicationManagement extends BaseMedicationElement
{
    public $do_not_save_entries = false;

    public $widgetClass = 'OEModule\OphCiExamination\widgets\MedicationManagement';

    public static $entry_class = MedicationManagementEntry::class;

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'et_ophciexamination_medicationmanagement';
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'event_id' => 'Event',
			'last_modified_user_id' => 'Last Modified User',
			'last_modified_date' => 'Last Modified Date',
			'created_user_id' => 'Created User',
			'created_date' => 'Created Date',
		);
	}

    public function getEntryRelations()
    {
        return array(
            'entries' => array(
                self::HAS_MANY,
                MedicationManagementEntry::class,
                array('id' => 'event_id'),
                'through' => 'event',
                'on' => "usage_type = '".MedicationManagementEntry::getUsageType()."' AND usage_subtype = '".MedicationManagementEntry::getUsageSubtype()."' ",
                'order' => 'entries.start_date_string_YYYYMMDD DESC, entries.end_date_string_YYYYMMDD DESC, entries.last_modified_date'
            ),
            'visible_entries' => array(
                self::HAS_MANY,
                MedicationManagementEntry::class,
                array('id' => 'event_id'),
                'through' => 'event',
                'on' => "hidden = 0 AND usage_type = '".MedicationManagementEntry::getUsageType()."' AND usage_subtype = '".MedicationManagementEntry::getUsageSubtype()."' ",
                'order' => 'visible_entries.start_date_string_YYYYMMDD DESC, visible_entries.end_date_string_YYYYMMDD DESC, visible_entries.last_modified_date'
            ),
        );
    }

    /**
     * @return MedicationManagementEntry[]
     */

    public function getContinuedEntries($debug = false)
    {
        $event_date = $this->event->event_date;
        $event_date_YYYYMMDD = substr($event_date, 0, 4).substr($event_date, 5, 2).substr($event_date, 8, 2);

        return array_filter($this->visible_entries, function($e) use($event_date_YYYYMMDD) {
            return ($e->prescribe == 0 && $e->start_date_string_YYYYMMDD <= $event_date_YYYYMMDD && is_null($e->end_date_string_YYYYMMDD));
        });
    }

    /**
     * @return MedicationManagementEntry[]
     */

    public function getEntriesStartedToday()
    {
        $event_date = $this->event->event_date;
        $event_date_YYYYMMDD = substr($event_date, 0, 4).substr($event_date, 5, 2).substr($event_date, 8, 2);
        return array_filter($this->visible_entries, function($e) use($event_date_YYYYMMDD){
            return ($e->start_date_string_YYYYMMDD == $event_date_YYYYMMDD && is_null($e->end_date_string_YYYMMDD));
        });
    }

    /**
     * @return MedicationManagementEntry[]
     */

    public function getStoppedEntries()
    {
        return array_filter($this->visible_entries, function($e){
            return !is_null($e->end_date_string_YYYYMMDD);
        });
    }

    /**
     * @return MedicationManagementEntry[]
     */

    public function getEntriesStoppedToday()
    {
        $event_date = $this->event->event_date;
        $event_date_YYYYMMDD = substr($event_date, 0, 4).substr($event_date, 5, 2).substr($event_date, 8, 2);
        return array_filter($this->visible_entries, function($e) use($event_date_YYYYMMDD){
            return ($e->end_date_string_YYYYMMDD <= $event_date_YYYYMMDD);
        });
    }

    /**
     * @return MedicationManagementEntry[]
     */

    public function getPrescribedEntries($visible_only = true)
    {
        $property = $visible_only ? "visible_entries" : "entries";

        return array_filter($this->$property, function($e){
            return $e->prescribe == 1;
        });
    }

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return MedicationManagement the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

    public function getContainer_form_view()
    {
        return false;
    }

    public function getContainer_update_view()
    {
        return '//patient/element_container_form';
    }

    public function getContainer_create_view()
    {
        return '//patient/element_container_form';
    }

    protected function saveEntries()
    {
        $criteria = new \CDbCriteria();
        $criteria->addCondition("event_id = :event_id AND usage_type = '".MedicationManagementEntry::getUsageType()."' AND usage_subtype = '".MedicationManagementEntry::getUsageSubtype()."'");
        $criteria->params['event_id'] = $this->event->id;
        $orig_entries = MedicationManagementEntry::model()->findAll($criteria);
        $saved_ids = array();
        $class = self::$entry_class;
        foreach ($this->entries as $entry) {
            /** @var MedicationManagementEntry $entry */
            $entry->event_id = $this->event->id;

            /* Why do I have to do this? */
            if(isset($entry->id) && $entry->id > 0) {
                $entry->setIsNewRecord(false);
            }

            /* ensure corrent usage type and subtype */
            $entry->usage_type = $class::getUsagetype();
            $entry->usage_subtype = $class::getUsageSubtype();

            if(!$entry->save()) {
                foreach ($entry->errors as $err) {
                    $this->addError('entries', implode(', ', $err));
                }
                return false;
            }

            /* Why do I have to do this? */

            $id = \Yii::app()->db->getLastInsertID();;
            $entry->id = $id;

            $saved_ids[] = $entry->id;

            if($entry->prescribe) {
                $this->entries_to_prescribe[] = $entry;
            }
        }

        foreach ($orig_entries as $orig_entry) {
            if(!in_array($orig_entry->id, $saved_ids)) {
                $orig_entry->delete();
            }
        }

        if(count($this->entries_to_prescribe) > 0) {
            $this->generatePrescriptionEvent();
        }
        return true;
    }

    private function generatePrescriptionEvent()
    {
        $prescription = new \Event();
        $prescription->episode_id = $this->event->episode_id;
        $criteria = new \CDbCriteria();
        $criteria->addCondition("class_name = :class_name");
        $criteria->params['class_name'] = 'OphDrPrescription';
        $prescription_event_type = \EventType::model()->findAll($criteria);
        $prescription->event_type_id = $prescription_event_type[0]->id;
        $prescription->event_date = $this->event->event_date;
        if(!$prescription->save()) {
            \Yii::trace(print_r($prescription->errors, true));
        }
        $prescription_details = $this->getPrescriptionDetails();
        $prescription_details->event_id = $prescription->id;
        if(!$prescription_details->save()){
            \Yii::trace(print_r($prescription_details->errors, true));
        }
        foreach($prescription_details->items as $item){
            $item->event_id = $prescription->id;
            if(!$item->save()) {
                \Yii::trace(print_r($item->errors, true));
            }

            $item->id = \Yii::app()->db->getLastInsertId();

            $original_item_id = $item->original_item_id;
            $orig_item = MedicationManagementEntry::model()->findByPk($original_item_id);
            if($orig_item) {
                $orig_item->setAttribute('prescription_item_id', $item->id);

                if(!$orig_item->save()) {
                    \Yii::trace(print_r($orig_item->errors, true));
                }
            }


        }
    }

    private function getPrescriptionDetails()
    {
        $entries = $this->entries_to_prescribe;
        if(is_null($this->prescription_details)) {
            $prescription_details = new \Element_OphDrPrescription_Details();
            $prescription_items = array();
            foreach($entries as $entry) {
                $prescription_item = $this->getPrescriptionItem($entry);
                $prescription_item->original_item_id = $entry->id;
                $prescription_items[] = $prescription_item;
            }
            $prescription_details->items = $prescription_items;
            $this->prescription_details = $prescription_details;
        }
        return $this->prescription_details;
    }

    private function getPrescriptionItem(\EventMedicationUse $entry)
    {
        $item = new \OphDrPrescription_Item();
        $item->dose = $entry->dose;
        $item->frequency_id = $entry->frequency_id;
        $item->route_id = $entry->route_id;
        $item->medication_id = $entry->medication_id;

        /* We can't get defaults as we don't know from which set the medication comes - so we're hard-coding :-( */

        $item->duration= 13; /* 'Until review */
        $item->dispense_condition_id = 1; /* Hospital to supply */
        $item->dispense_location_id = 2; /* Pharmacy */

        $item->laterality = $entry->laterality;
        $item->usage_type = \OphDrPrescription_Item::getUsageType();
        $item->usage_subtype = \OphDrPrescription_Item::getUsageSubtype();

        $item->start_date_string_YYYYMMDD = $entry->start_date_string_YYYYMMDD;

        return $item;
    }

    public function loadFromExisting($element)
    {
        return;
    }
}