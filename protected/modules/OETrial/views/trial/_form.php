<?php
/* @var $this TrialController */
/* @var $trial Trial */
/* @var $form CActiveForm */
?>

<?php $this->renderPartial('_trial_header', array(
    'trial' => $trial,
    'title' => $trial->getIsNewRecord() ? 'Create Trial' : 'Edit Trial',
)); ?>
<div class="oe-full-content subgrid oe-worklists">
  <main class="oe-full-main">


      <?php $form = $this->beginWidget('CActiveForm', array(
          'id' => 'trial-form',
          'enableAjaxValidation' => true,
      )); ?>

      <?= $form->errorSummary($trial) ?>


    <table class="standard cols-full">
      <colgroup>
        <col class="cols-2">
        <col class="cols-4">
      </colgroup>
      <tbody>
      <tr class="col-gap">
        <td>
            <?= $form->labelEx($trial, 'name') ?>
        </td>
        <td>
            <?= $form->textField($trial, 'name', array('size' => 64, 'maxlength' => 64)) ?>
            <?= $form->error($trial, 'name') ?>
        </td>
      </tr>
      <tr class="col-gap">
        <td>
            <?= $form->labelEx($trial, 'external_data_link') ?>
        </td>
        <td>
            <?= $form->urlField($trial, 'external_data_link',
                array('size' => 100, 'maxlength' => 255, 'onblur' => 'checkUrl(this)')); ?>
            <?= $form->error($trial, 'external_data_link') ?>
        </td>
      </tr>
      <tr>
        <td>

            <?= $form->labelEx($trial, 'description') ?>
        </td>
        <td>

            <?= $form->textArea($trial, 'description') ?>
            <?= $form->error($trial, 'description') ?>
        </td>
      </tr>

      <?php if (!$trial->getIsNewRecord()): ?>
        <tr>
          <td>
              Date Range
          </td>
          <td class="flex-layour cols-full">
              <?php
              if ((bool)strtotime($trial->started_date)) {
                  $dob = new DateTime($trial->started_date);
                  $trial->started_date = $dob->format('d/m/Y');
              } else {
                  $trial->started_date = str_replace('-', '/', $trial->started_date);
              }
              ?>
              <?= $form->textField($trial, 'started_date') ?>
              <?= $form->error($trial, 'started_date') ?>

              <?php
              if ((bool)strtotime($trial->closed_date)) {
                  $dob = new DateTime($trial->closed_date);
                  $trial->closed_date = $dob->format('d/m/Y');
              } else {
                  $trial->closed_date = str_replace('-', '/', $trial->closed_date);
              }
              ?>
              <?= $form->textField($trial, 'closed_date') ?>
              <?= $form->error($trial, 'closed_date') ?>

          </td>
        </tr>
      <?php endif; ?>
      <tr>
        <td>
            <?= $form->labelEx($trial, 'trial_type') ?>
        </td>
        <td>
            <?php foreach (TrialType::model()->findAll() as $trial_type): ?>
              <label>
                  <?php echo $form->radioButton($trial, 'trial_type_id',
                      array('value' => $trial_type->id, 'uncheckValue' => null)); ?>
                  <?= $trial_type->name ?>
              </label>
            <?php endforeach; ?>
        </td>
      </tr>
      </tbody>
    </table>

      <?php $this->endWidget(); ?>
</div>


<script>
  function checkUrl(urlField) {
    var urlText = urlField.value;
    if (urlText && urlText.indexOf("http") === -1) {
      urlText = "http://" + urlText;
    }

    urlField.value = urlText;
    return urlField;
  }
</script>
