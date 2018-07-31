<?php
/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2017
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2017, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */
namespace OEModule\OphCiExamination\widgets;
use OEModule\OphCiExamination\models\HistoryMedications as HistoryMedicationsElement;
use OEModule\OphCiExamination\models\HistoryMedicationsEntry;
use OEModule\OphCiExamination\models\MedicationManagement as MedicationManagementElement;
/**
 * Class HistoryMedications
 * @package OEModule\OphCiExamination\widgets
 * @property HistoryMedicationsElement $element
 */
class HistoryMedications extends BaseMedicationWidget
{
    protected $print_view = 'HistoryMedications_event_print';
    protected static $elementClass = HistoryMedicationsElement::class;
    /**
     * @param $mode
     * @return bool
     * @inheritdoc
     */
    protected function validateMode($mode)
    {
        return in_array($mode,
            array(static::$PRESCRIPTION_PRINT_VIEW, static::$INLINE_EVENT_VIEW), true) || parent::validateMode($mode);
    }
    /**
     * @return bool
     */
    protected function showViewTipWarning()
    {
        return $this->mode === static::$EVENT_VIEW_MODE;
    }
    /**
     * Creates new Entry records for any prescription items that are not in the
     * current element.
     *
     * @return array
     */
    private function getEntriesForUntrackedPrescriptionItems()
    {
        return array(); // TODO implement new method!
        $untracked = array();
        if ($api = $this->getApp()->moduleAPI->get('OphDrPrescription')) {
            $tracked_prescr_item_ids = array_map(
                function ($entry) {
                    return $entry->prescription_item_id;
                },
                $this->element->getPrescriptionEntries()
            );
            if ($untracked_prescription_items = $api->getPrescriptionItemsForPatient(
                $this->patient, $tracked_prescr_item_ids)
            ) {
                foreach ($untracked_prescription_items as $item) {
                    $entry = new HistoryMedicationsEntry();
                    $entry->loadFromPrescriptionItem($item);
                    $untracked[] = $entry;
                }
            }
        }
        return $untracked;
    }
    /**
     * @return bool
     * @inheritdoc
     */
    protected function isAtTip()
    {
        $this->is_latest_element = parent::isAtTip();
        // if it's a new record we trust that the missing prescription items will be added
        // to the element, otherwise we care if there are untracked prescription items
        // in terms of this being considered a tip record.
        if ($this->is_latest_element && $this->element->isNewRecord) {
            return true;
        }
        $this->missing_prescription_items = (bool) $this->getEntriesForUntrackedPrescriptionItems();
        foreach ($this->element->entries as $entry) {
            if ($entry->prescriptionNotCurrent()) {
                return false;
            }
        }
        return !$this->missing_prescription_items;
    }

    /**
     * @return array
     */

    private function getEntriesFromPreviousManagement()
    {
        $entries = [];
        $element = $this->element->getModuleApi()->getLatestElement(MedicationManagementElement::class, $this->patient);
        if(!is_null($element)) {
            /** @var MedicationManagementElement $element*/
            foreach ($element->entries as $entry) {
                $entries[]= clone $entry;
            }
        }

        return $entries;
    }

    private function setEntriesFromPreviousManagement()
    {
        $this->element->entries = $this->getEntriesFromPreviousManagement();
    }

    /**
     * @inheritdoc
     */
    protected function setElementFromDefaults()
    {
        if(!$this->isPostedEntries()) {
            //parent::setElementFromDefaults();
            $this->setEntriesFromPreviousManagement();
        }
        // because the entries cloned into the new element may contain stale data for related
        // prescription data (or that prescription item might have been deleted)
        // we need to update appropriately.
        $entries = array();
        foreach ($this->element->entries as $entry) {
            if ($entry->prescription_item_id) {
                if ($entry->prescription_event_deleted || !$entry->prescriptionItem) {
                    continue;
                }
                $entry->loadFromPrescriptionItem($entry->prescriptionItem);
            }
            $entries[] = $entry;
        }
        if ($untracked = $this->getEntriesForUntrackedPrescriptionItems()) {
            // tracking prescription items.
            $this->element->entries = array_merge(
                $entries,
                $untracked);
        }
    }

    /**
     * @return array
     */

    public function getMergedManagementEntries()
    {
        $this->setEntriesFromPreviousManagement();
        $this->element->assortEntries();
        return [
            'current' => $this->element->current_entries,
            'stopped' => $this->element->closed_entries
        ];
    }

    /**
     * Merges any missing (i.e. created since the element) prescription items into lists of
     * current and stopped medications for patient level rendering.
     *
     * Expected to only be used with the at tip element, but would work with older elements as well
     * should the need arise.
     *
     * @return array
     * @deprecated  please use HistoryMedications::getMergedManagementEntries
     */
    public function getMergedEntries()
    {
        //$this->element->currentOrderedEntries and stoppedOrderedEntries relations are not uses here as we
        //need to include the untracked Prescription Items as well and those are already loaded into the
        //$this->element->entries (alongside with tracked Prescription Items)
        // setElementFromDefaults() only called when the element is a new record (BaseEventElementWidget like ~166)
        // and this is where the untracked elements are loaded into the $this->element->entries
        // so if it isn't a new element ->entries only contains tracked medications
        if(!$this->element->isNewRecord){
            if ($untracked = $this->getEntriesForUntrackedPrescriptionItems()) {
                // tracking prescription items.
                $this->element->entries = array_merge(
                    $this->element->entries,
                    $untracked);
            }
        }
        $result['current'] = $this->element->current_entries;
        $result['stopped'] = $this->element->closed_entries;
        // now remove any that are no longer relevant because the prescription item
        // has been deleted
        $filter = function($entry) {
            return !($entry->prescription_item_deleted || $entry->prescription_event_deleted);
        };
        $result['current'] = array_filter($result['current'], $filter);
        $result['stopped'] = array_filter($result['stopped'], $filter);
        return $result;
    }
    /**
     * @param $entry
     * @return string
     */
    public function getPrescriptionLink($entry)
    {
        return '/OphDrPrescription/Default/view/' . $entry->prescriptionItem->event_id;
    }
    /**
     * @return string
     */
    public function popupList()
    {
        return $this->render($this->getView(), $this->getViewData());
    }
    /**
     * @return string
     * @inheritdoc
     */
    protected function getView()
    {
        // custom mode for rendering in the patient popup because the data is more complex
        // for this history element than others which just provide a list.
        $short_name = substr(strrchr(get_class($this), '\\'),1);
        if ($this->mode === static::$PATIENT_POPUP_MODE) {
            return  $short_name . '_patient_popup';
        }
        if ($this->mode === static::$INLINE_EVENT_VIEW) {
            return $short_name . '_inline_event_view';
        }
        if ($this->mode === static::$PRESCRIPTION_PRINT_VIEW) {
            return $short_name . '_prescription_print_view';
        }
        return parent::getView();
    }
    /**
     * @return array
     */
    public function getViewData()
    {
        if (in_array($this->mode, array(static::$PATIENT_POPUP_MODE, static::$PATIENT_SUMMARY_MODE)) ) {
            return array_merge(parent::getViewData(), $this->getMergedManagementEntries());
        }
        return parent::getViewData();
    }
}