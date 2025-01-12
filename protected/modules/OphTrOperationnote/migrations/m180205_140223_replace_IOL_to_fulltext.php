<?php

class m180205_140223_replace_IOL_to_fulltext extends CDbMigration
{

    // Use safeUp/safeDown to do migration with transaction
    public function safeUp()
    {
        $dataProvider = new CActiveDataProvider('Procedure');
        $iterator = new CDataProviderIterator($dataProvider);

        if ($iterator->getTotalItemCount()) {
            foreach ($iterator as $i => $procedure) {
                try {
                    $save = false;
                    $data = [
                        'table' => 'proc',
                        'model' => 'Procedure',
                        'old_term' => $procedure->getAttribute('term'),
                    ];

                    $words = explode(" ", $procedure->getAttribute('term'));
                    foreach ($words as $k => $word) {
                        if ($word === 'IOL') {
                            $words[$k] = 'Intraocular lens';
                            $procedure->setAttribute('term', implode(" ", $words));
                            $data['new_term'] = $procedure->getAttribute('term');
                            $save = true;
                        }
                    }

                    if ($save && $procedure->save()) {
                        \Audit::add('Admin', 'update', "<pre>" . (print_r($data, true)) . "</pre>", '', ['model' => 'Procedure']);
                    }
                } catch (Exception $e) {
                    echo "Error processing procedure {$i}: " . $e->getMessage() . "\n";
                    continue;
                }
            }
        }
    }

    public function safeDown()
    {
        echo "m180205_140223_replace_IOL_to_fulltext does not support migration down.\n";
        return false;
    }

}
