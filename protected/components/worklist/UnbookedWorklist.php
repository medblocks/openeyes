<?php
/**
 * (C) OpenEyes Foundation, 2019
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link http://www.openeyes.org.uk
 *
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (C) 2019, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */


class UnbookedWorklist extends CComponent
{
    /**
     * @var WorklistManager
     */
    public $worklist_manager;

    public function __construct(){
        $this->worklist_manager = new \WorklistManager();
    }

    /**
     * Gets the Unbooked Worklist based on date, site and subspeicalty (and firm)
     *
     * @param DateTime $date
     * @param $site_id
     * @param $subspecialty_id
     * @param null $firm_id
     * @return bool|null
     * @throws Exception
     */
    public function getWorklist(DateTime $date, $site_id, $subspecialty_id, $firm_id = null)
    {
        $definition = $this->getDefinition($site_id, $subspecialty_id, $firm_id);

        if (!$definition) {
            $definition = $this->createWorklistDefinition($site_id, $subspecialty_id, $firm_id);
        }

        $criteria = new \CDbCriteria();
        $criteria->addCondition('worklist_definition_id = :worklist_definition_id');
        $criteria->addCondition('start >= :start');
        $criteria->addCondition('end < :end');
        $criteria->params[':start'] = $date->modify('today')->format('Y-m-d H:i:s'); // The time is set to 00:00:00
        $criteria->params[':end'] = $date->modify('tomorrow')->format('Y-m-d H:i:s'); // Midnight of tomorrow
        $criteria->params[':worklist_definition_id'] = $definition->id;

        $unbooked_worklist = Worklist::model()->find($criteria);

        if (!$unbooked_worklist) {

            //generate worklist by definition
            /**
             * Regarding to the worklist.end time, the generateAutomaticWorklists() function strips the seconds
             * of the time so the worklist end time ends up 23:59:00 regardless that in the definition is 23:59:59
             */
            $today = new \DateTime();
            if ($this->worklist_manager->generateAutomaticWorklists($definition, $today->modify('tomorrow'))) {
                $unbooked_worklist = $this->getWorklist($date, $site_id, $subspecialty_id, $firm_id);
            }
        }

        return $unbooked_worklist;
    }

    /**
     * Gets the Unbooked Worklist Definition based on based on date, site and subspeicalty (and firm)
     *
     * @param $site_id
     * @param $subspecialty_id
     * @param null $firm_id
     * @return WorklistDefinition|null
     */
    public function getDefinition($site_id, $subspecialty_id, $firm_id = null)
    {
        $criteria = new \CDbCriteria();
        $criteria->with = ['display_contexts', 'mappings.values'];
        $criteria->addCondition('display_contexts.site_id = :site_id');
        $criteria->addCondition('display_contexts.subspecialty_id = :subspecialty_id');

        $criteria->addCondition('mappings.key = "UNBOOKED"');
        $criteria->addCondition('values.mapping_value = "true"');

        $criteria->params[':site_id'] = $site_id;
        $criteria->params[':subspecialty_id'] = $subspecialty_id;

        if ($firm_id) {
            $criteria->addCondition('display_contexts.firm_id = :firm_id');
            $criteria->params[':firm_id'] = $firm_id;
        }

        return WorklistDefinition::model()->resetScope(true)->find($criteria);
    }


    /**
     * Creates Unbooked Worklist Definition based on date, site and subspeicalty (and firm)
     *
     * @param $site_id
     * @param $subspecialty_id
     * @param null $firm_id
     * @return WorklistDefinition
     * @throws Exception
     */
    public function createWorklistDefinition($site_id, $subspecialty_id, $firm_id = null)
    {
        $today = new \DateTime();
        $definition = new \WorklistDefinition();
        $definition->name = 'Unbooked';
        $definition->description = 'Patients for unbooked worklist';
        $definition->worklist_name = null;
        $definition->rrule = 'FREQ=DAILY';
        $definition->start_time = '00:00:00';
        $definition->end_time = '23:59:59';
        $definition->active_from = $today->modify('midnight')->format('Y-m-d H:i:s');
        $definition->active_until = $today->modify('tomorrow')->format('Y-m-d H:i:s');

        if ($definition->save()) {
            $context = new \WorklistDefinitionDisplayContext();
            $context->firm_id = $firm_id;
            $context->subspecialty_id = $subspecialty_id;
            $context->site_id = $site_id;
            $context->worklist_definition_id = $definition->id;
            $context->save();

            $mapping = new \WorklistDefinitionMapping();
            $mapping->key = 'UNBOOKED';
            $mapping->worklist_definition_id = $definition->id;

            if ($mapping->save()) {
                $value = new \WorklistDefinitionMappingValue();
                $value->worklist_definition_mapping_id = $mapping->id;
                $value->mapping_value = 'true';

                $value->save();
            }

            return $definition;

        } else {
            \OELog::log("WorklistDefinition saving error: " . print_r($definition->getErrors(), true));
        }

        return null;
    }
}
