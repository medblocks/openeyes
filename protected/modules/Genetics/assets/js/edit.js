$(document).ready(function () {
  OpenEyes.Genetics.Relationships.init(geneticsRelationships, []);
  OpenEyes.UI.Search.init($('#genetics_patient_lookup'));
  OpenEyes.UI.Search.setRenderItem(function (ul, item) {
    ul.addClass("z-index-1000 patient-ajax-list");
    return $("<li></li>")
      .data("item.autocomplete", item)
      .append('<a>' + item['patient.fullName'] + '</a>')
      .appendTo(ul);
  });
  OpenEyes.UI.Search.getElement().autocomplete('option', 'source', function(request, response) {
    $.getJSON('/Genetics/subject/list', {
      search: {
        'patient.contact.first_name': {
          value: request.term,
          compare_to: 'patient.contact.last_name'
        }
      }
    }, response);
  });
  OpenEyes.UI.Search.getElement().autocomplete('option', 'select', function(event, uid){
    $('#relationships_list').append(OpenEyes.Genetics.Relationships.newRelationshipForm(uid.item));
  });

  $('input[name="GeneticsPatient\[patient_lookup_gender\]"]').on('change', function(){
    var genderValue = this.value;
    $('#GeneticsPatient_gender_id option').filter(function () { return $(this).html() === genderValue; }).prop('selected', true);
  });

  $('input[name="GeneticsPatient\[patient_lookup_deceased\]"]').on('change', function(){
    console.log((this.value === '1'));
    $('#GeneticsPatient_is_deceased').prop('checked', (this.value === '1'));
  });
});