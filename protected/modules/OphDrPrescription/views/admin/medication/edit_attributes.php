<?php
    /** @var Medication $medication */

	$attrs = MedicationAttribute::model()->findAll(array("order" => "name"));
	$default_tarr = reset($attrs);
	$all_options = MedicationAttributeOption::model()->findAll(array("select"=>array("id","medication_attribute_id","value","description")));
	foreach ($attrs as $attr) {
		$optiondata[$attr->id] = CHtml::listData(
            array_filter($all_options, function($e) use($attr) {
                return $e->medication_attribute_id == $attr->id;
            }), 'id', function($e) {
			return $e->description." - ".$e->value;
		});
    }

    $rowkey = 0;
    $default_optiondata = reset($optiondata);

?>
<script id="row_template" type="x-tmpl-mustache">
    <tr id="{{ key }}">
        <td>
        <input type="hidden" name="Medication[medicationAttributeAssignment][id][]" value="-1" />
            <?php echo CHtml::dropDownList('Medication[medicationAttributeAssignment][medication_attribute_id][]', null, CHtml::listData($attrs, 'id', 'name'), array('empty' => '-- Please select --', "class" => "js-attribute", "id" => 'Medication_attribute_id{{ key }}')); ?>
        </td>
        <td>
            <select class="js-option" name="Medication[medicationAttributeAssignment][medication_attribute_option_id][]">
                <option>-- Please select --</option>
            </select>
        </td>
        <td>
            <a href="javascript:void(0);" class="js-delete-attribute"><i class="oe-i trash"></i></a>
        </td>
    </tr>
</script>
<script type="text/javascript">
    $(function(){
        var medication_attribute_options = <?php echo CJSON::encode($optiondata); ?>;
        $(document).on("change", ".js-attribute", function (e) {
            var attr_id = $(e.target).val();
            var $options = $(e.target).closest("tr").find(".js-option");
            $options.empty();
            $options.append('<option>-- Please select --</option>');
            $.each(medication_attribute_options[attr_id], function(i, e){
                $options.append('<option value="'+i+'">'+e+'</option>');
            });
        });

        $(document).on("click", ".js-add-attribute", function (e) {
            var lastkey = $("#medication_attribute_assignment_tbl tbody tr:last").attr("data-key");
            var key = parseInt(lastkey) + 1;
            var template = $('#row_template').html();
            Mustache.parse(template);
            var rendered = Mustache.render(template, {"key": key});
            $("#medication_attribute_assignment_tbl tbody").append(rendered);
        });

        $(document).on("click", ".js-delete-attribute", function (e) {
            $(e.target).closest("tr").remove();
        });
    });
</script>
<h3>Attributes</h3>
<table class="standard" id="medication_attribute_assignment_tbl">
    <thead>
        <tr>
            <th width="25%">Name</th>
            <th width="50%">Value</th>
            <th width="25%">Action</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($medication->medicationAttributeAssignments as $assignment): ?>
		<?php
            $attr_id = isset($assignment->medicationAttributeOption) ? $assignment->medicationAttributeOption->medication_attribute_id : null;
            $option_id = isset($assignment->medicationAttributeOption) ? $assignment->medicationAttributeOption->id : null;
            $id = is_null($assignment->id) ? -1 : $assignment->id;
            $rowkey++;
		?>
        <tr data-key="<?=$rowkey?>">
            <td>
                <input type="hidden" name="Medication[medicationAttributeAssignment][id][]" value="<?=$id?>" />
                <?php echo CHtml::dropDownList('Medication[medicationAttributeAssignment][medication_attribute_id][]', $attr_id, CHtml::listData($attrs, 'id', 'name'), array('empty' => '-- Please select --', "class" => "js-attribute", "id" => 'Medication_attribute_id'.$rowkey)); ?>
            </td>
            <td>
				<?php echo CHtml::dropDownList('Medication[medicationAttributeAssignment][medication_attribute_option_id][]', $option_id, is_null($attr_id) ? array() : $optiondata[$attr_id], array('empty' => '-- Please select --', "class" => "js-option", "id" => 'Medication_attribute_option_id'.$rowkey)); ?>
            </td>
            <td>
                <a href="javascript:void(0);" class="js-delete-attribute"><i class="oe-i trash"></i></a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot class="pagination-container">
        <tr>
            <td colspan="3">
                <div class="flex-layout flex-right">
                    <button class="button hint green js-add-attribute" type="button"><i class="oe-i plus pro-theme"></i></button>
                </div>
            </td>
        </tr>
    </tfoot>
</table>