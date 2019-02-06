DELETE FROM medication_set_item WHERE medication_set_id IN (SELECT id FROM medication_set WHERE `name` = 'DM+D AMP');
DELETE FROM medication_set_item WHERE medication_set_id IN (SELECT id FROM medication_set WHERE `name` = 'DM+D VMP');
DELETE FROM medication_set_item WHERE medication_set_id IN (SELECT id FROM medication_set WHERE `name` = 'DM+D VTM');
DELETE rmu FROM event_medication_use rmu LEFT JOIN medication rm ON rm.id = rmu.medication_id WHERE rm.source_type = 'DM+D';
DELETE FROM medication_form WHERE source_type = 'DM+D';
DELETE FROM medication_route WHERE source_type = 'DM+D';
DELETE FROM medication WHERE source_type = 'DM+D';
DELETE FROM medication_set WHERE `name` IN ('DM+D AMP', 'DM+D VMP', 'DM+D VTM');