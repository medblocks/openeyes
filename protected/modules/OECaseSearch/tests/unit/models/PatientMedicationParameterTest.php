<?php

/**
 * Class PatientMedicationParameterTest
 */
class PatientMedicationParameterTest extends CDbTestCase
{
    /**
     * @var $object PatientMedicationParameter
     */
    protected $object;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        Yii::app()->getModule('OECaseSearch');
    }

    public function setUp()
    {
        parent::setUp();
        $this->object = new PatientMedicationParameter();
        $this->object->id = 0;
    }

    public function tearDown()
    {
        unset($this->object);
        parent::tearDown();
    }

    public function getOperations()
    {
        return array(
            'Equal' => array(
                'operator' => '=',
            ),
            'Not equal' => array(
                'operator' => '!=',
            ),
        );
    }

    /**
     * @dataProvider getOperations
     * @param $operator
     */
    public function testQuery($operator)
    {
        $this->object->value = 5;
        $this->object->operation = $operator;

        $sqlValue = "
SELECT p.id
FROM patient p
LEFT JOIN patient_medication_assignment m
ON m.patient_id = p.id
LEFT JOIN medication md
ON md.id = m.medication_drug_id
WHERE md.id != :p_m_value_0
OR m.id IS NULL";

        if ($operator === '=') {
            $sqlValue = "
SELECT p.id
FROM patient p
JOIN patient_medication_assignment m
ON m.patient_id = p.id
LEFT JOIN medication d
ON d.id = m.medication_drug_id
WHERE d.id = :p_m_value_0";
        }

        $this->assertEquals(
            trim(preg_replace('/\s+/', ' ', $sqlValue)),
            trim(preg_replace('/\s+/', ' ', $this->object->query()))
        );
    }

    public function testBindValues()
    {
        $this->object->value = 5;
        $expected = array(
            'p_m_value_0' => $this->object->value,
        );

        // Ensure that all bind values are returned.
        $this->assertEquals($expected, $this->object->bindValues());
    }
}
