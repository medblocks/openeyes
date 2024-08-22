<?php

/**
 * This is the model class for table "visual_field_test_type".
 *
 * The followings are the available columns in table 'visual_field_test_type':
 * @property integer $id
 * @property string $long_name
 * @property string $short_name
 * @property string $last_modified_user_id
 * @property string $last_modified_date
 * @property string $created_user_id
 * @property string $created_date
 *
 * The followings are the available model relations:
 * @property VisualFieldTestPreset[] $visualFieldTestPresets
 * @property User $createdUser
 * @property User $lastModifiedUser
 */
class VisualFieldTestType extends BaseActiveRecordVersioned
{
    const SELECTION_LABEL_FIELD = 'short_name';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'visual_field_test_type';
    }

    public function behaviors()
    {
        return array(
            'LookupTable' => \LookupTable::class,
        );
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('long_name', 'length', 'max' => 100),
            array('short_name', 'length', 'max' => 20),
            array('last_modified_user_id, created_user_id', 'length', 'max' => 10),
            array('last_modified_date, created_date', 'safe'),
            // The following rule is used by search().
            // @todo Please remove those attributes that should not be searched.
            array(
                'id, long_name, short_name, last_modified_user_id, last_modified_date, created_user_id, created_date',
                'safe',
                'on' => 'search'
            ),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'visualFieldTestPresets' => array(self::HAS_MANY, 'VisualFieldTestPreset', 'test_type_id'),
            'createdUser' => array(self::BELONGS_TO, 'User', 'created_user_id'),
            'lastModifiedUser' => array(self::BELONGS_TO, 'User', 'last_modified_user_id'),
            'presets' => array(self::HAS_MANY, 'VisualFieldTestPreset', 'test_type_id')
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'long_name' => 'Long Name',
            'short_name' => 'Short Name',
            'last_modified_user_id' => 'Last Modified User',
            'last_modified_date' => 'Last Modified Date',
            'created_user_id' => 'Created User',
            'created_date' => 'Created Date',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('long_name', $this->long_name, true);
        $criteria->compare('short_name', $this->short_name, true);
        $criteria->compare('last_modified_user_id', $this->last_modified_user_id, true);
        $criteria->compare('last_modified_date', $this->last_modified_date, true);
        $criteria->compare('created_user_id', $this->created_user_id, true);
        $criteria->compare('created_date', $this->created_date, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return VisualFieldTestType the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
