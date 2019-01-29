<?php

/**
 * OpenEyes
 *
 * (C) OpenEyes Foundation, 2018
 * This file is part of OpenEyes.
 * OpenEyes is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * OpenEyes is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License along with OpenEyes in a file titled COPYING. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package OpenEyes
 * @link http://www.openeyes.org.uk
 * @author OpenEyes <info@openeyes.org.uk>
 * @copyright Copyright (c) 2018, OpenEyes Foundation
 * @license http://www.gnu.org/licenses/agpl-3.0.html The GNU Affero General Public License V3.0
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RefMedicationAdminController extends BaseAdminController
{
    public function actionList()
    {
        $admin = new Admin(Medication::model(), $this);
        $admin->setListFields(array(
            'id',
            'source_type',
            'preferred_term',
            'alternativeTerms',
            'vtm_term',
            'vmp_term',
            'amp_term',
        ));

        $admin->getSearch()->addSearchItem('preferred_term');

        $admin->setModelDisplayName('All Medications');

        $admin->listModel();
    }

    public function actionEdit($id)
    {
        $this->_getEditAdmin($id)->editModel();
    }

    private function _getEditAdmin($id)
    {
        $admin = new Admin(Medication::model(), $this);

        if(!is_null($id)) {
            $search_indexes = Medication::model()->findByPk($id)->medicationSearchIndexes;
        }
        else {
            $search_indexes = array();
        }

        $admin->setEditFields(array(
            'preferred_term'=>'Preferred term',
            'short_term'=>'Short term',
            'preferred_code'=>'Preferred code',
            'source_type'=>'Source type',
            'source_subtype'=>'Source subtype',
            'vtm_term' => 'VTM term',
            'vtm_code' => 'VTM code',
            'vmp_term' => 'VMP term',
            'vmp_code' => 'VMP code',
            'amp_term' => 'AMP term',
            'amp_code' => 'AMP code',
            'alternative_terms' =>  array(
                'widget' => 'GenericAdmin',
                'options' => array(
                    'model' => MedicationSearchIndex::class,
                    'label_field' => 'alternative_term',
                    'label_field_type' => 'text',
                    'items' => $search_indexes,
                    'filters_ready' => true,
                    'cannot_save' => true,
                    'no_form' => true,
                ),
                'label' => 'Alternative terms'
            ),
        ));

        $admin->setModelDisplayName("Medication");
        if($id) {
            $admin->setModelId($id);
        }

        $admin->setCustomSaveURL('/OphDrPrescription/refMedicationAdmin/save/'.$id);

        return $admin;
    }

    public function actionSave($id)
    {
        $admin = $this->_getEditAdmin($id);

        if(is_null($id)) {
            $model = new Medication();
        }
        else {
            if(!$model = Medication::model()->findByPk($id)) {
                throw new CHttpException(404, 'Page not found');
            }
        }

        /** @var Medication $model */

        $data = Yii::app()->request->getPost('Medication');
        $model->setAttributes($data);

        if(!$model->validate()) {
            $errors = $model->getErrors();
            $this->render($admin->getEditTemplate(), array('admin' => $admin, 'errors' => $errors));
            exit;
        }

        $model->save();

        $existing_ids = array();
        $updated_ids = array();
        foreach ($model->medicationSearchIndexes as $alt_term) {
            $existing_ids[] = $alt_term->id;
        }

        $ids = Yii::app()->request->getPost('id');
        if(is_array($ids)) {
            foreach ($ids as $key => $rid) {
                if($rid === '') {
                    $alt_term = new MedicationSearchIndex();
                }
                else {
                    $alt_term = MedicationSearchIndex::model()->findByPk($rid);
                    $updated_ids[] = $rid;
                }

                $alt_term->setAttributes(array(
                    'medication_id' => $model->id,
                    'alternative_term' => Yii::app()->request->getPost('alternative_term')[$key],
                ));

                $alt_term->save();
            }
        }

        $deleted_ids = array_diff($existing_ids, $updated_ids);
        if(!empty($deleted_ids)) {
            MedicationSearchIndex::model()->deleteByPk($deleted_ids);
        }

        $this->redirect('/OphDrPrescription/refMedicationAdmin/list');

    }

    public function actionExportForm()
    {
        $data = array();

        if(Yii::app()->request->isPostRequest) {
            $med_set_ids = Yii::app()->request->getPost('MedicationSet');
            if(!empty($med_set_ids)) {
                return $this->actionExport($med_set_ids);
            }
            else {
                $data['form_error'] = 'Please select at least one Set.';
            }
        }
        return $this->render('/admin/ref_medication_export', $data);
    }

    public function actionExport($med_set_ids = array())
    {
        ini_set('max_execution_time', 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $style_bold = [
            'font' => [
                'bold' => true,
            ]
        ];

        $sheet->setCellValue('A4', 'DM+D ID (preffered code)');
        $sheet->setCellValue('B4', 'Term (preferred term');
        $sheet->setCellValue('C4', 'Source type');
        $sheet->setCellValue('D4', 'Source subtype');
        $sheet->setCellValue('E4', 'Notes');
        $sheet->getStyle('A4:E4')->applyFromArray($style_bold);

        $sheet->setCellValue('E1', 'SET TITLE >>');
        $sheet->setCellValue('E2', 'SET ID >>');
        $sheet->setCellValue('E3', 'SET INFO >>');

        $cond = new CDbCriteria();
        $cond->addInCondition('id', $med_set_ids);

        $sets = MedicationSet::model()->findAll($cond);

        $sets_array = [];

        $sets_array[0] = array_map(function ($e){ return $e->name; }, $sets);
        $sets_array[1] = array_map(function ($e){ return $e->id; }, $sets);
        $sets_array[2] = array_map(function ($e){
            /** @var MedicationSet $e */
            $ruleString = [];
            foreach ($e->medicationSetRules as $rule) {
                $ruleString[] = $rule->usage_code." (site=".(is_null($rule->site_id) ? "null" : $rule->site->name).", ss=".(is_null($rule->subspecialty_id) ? "null" : $rule->subspecialty->name).")";
            }

            return implode(PHP_EOL, $ruleString);
        }, $sets);

        $sheet->fromArray($sets_array, null, 'E1');

        $cond = new CDbCriteria();
        $cond->addCondition("id IN (SELECT medication_id FROM medication_set_item WHERE medication_set_id IN (".implode(",", $med_set_ids)."))");

        $medications = Medication::model()->findAll($cond);

        $cells_array = [];

        foreach ($medications as $med) {
            $row = [
                $med->preferred_code, $med->preferred_term, $med->source_type, $med->source_subtype, ''
            ];

            foreach ($sets as $set) {
                if($ref_medication_set = MedicationSetItem::model()->find("medication_id = {$med->id} AND medication_set_id = {$set->id}")) {
                    $repr = new stdClass();
                    $repr->dose = $ref_medication_set->default_dose;
                    $repr->dose_unit = $ref_medication_set->default_dose_unit_term;
                    $repr->route = is_null($ref_medication_set->default_route) ? null : $ref_medication_set->defaultRoute->term;
                    $repr->frequency = is_null($ref_medication_set->default_frequency) ? null: $ref_medication_set->defaultFrequency->term;
                    $repr->duration = is_null($ref_medication_set->default_duration) ? null : $ref_medication_set->defaultDuration->name;

                    if(!empty($ref_medication_set->tapers)) {
                        $repr->tapers = array();
                        foreach ($ref_medication_set->tapers as $taper) {
                            $t = new stdClass();
                            $t->dose = $taper->dose;
                            $t->frequency = is_null($taper->frequency_id) ? null : $taper->frequency->term;
                            $t->duration = is_null($taper->duration_id) ? null : $taper->duration->name;
                            $repr->tapers[] = $t;
                        }
                    }

                    $row[] = json_encode($repr);
                }
                else {
                    $row[] = null;
                }

            }

            $cells_array[]=$row;
        }

        $sheet->fromArray($cells_array, null, 'A5');

        $writer = new Xlsx($spreadsheet);
        $writer->save('/tmp/refMedExport.xlsx');

        Yii::app()->request->sendFile("refMedExport.xlsx", @file_get_contents('/tmp/refMedExport.xlsx'), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', false);
    }


}