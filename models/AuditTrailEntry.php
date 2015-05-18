<?php
namespace asinfotrack\yii2\audittrail\models;

use yii\helpers\Json;
use yii\base\InvalidCallException;
use asinfotrack\yii2\audittrail\behaviors\AuditTrailBehavior;


/**
 * This is the model class for audit trail entries and the table "audit_trail_entry".
 * 
 * @author Pascal Mueller, AS infotrack AG
 * @link http://www.asinfotrack.ch
 * @license MIT
 * 
 * @property integer $id
 * @property string $table_name
 * @property integer $happened_at
 * @property string $foreign_pk
 * @property integer $user_id
 * @property string $type
 * @property string $data
 */
class AuditTrailEntry extends \yii\db\ActiveRecord
{
	
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'audit_trail_entry';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::rules()
	 */
	public function rules()
	{
		return [
			[['table_name','happened_at','foreign_pk','type'], 'required'],
				
			[['happened_at','user_id'], 'integer'],
			[['table_name','foreign_pk','type'], 'string', 'max'=>255],
			
			[['type'], 'in', 'range'=>AuditTrailBehavior::$AUDIT_TYPES],
		];
	}
	
	public function attributeLabels()
	{
		return [
			'id'=>Yii::t('app', 'ID'),
			'table_name'=>Yii::t('app', 'Table name'),
			'happened_at'=>Yii::t('app', 'Happened at'),
			'foreign_pk'=>Yii::t('app', 'Foreign PK'),
			'user_id'=>Yii::t('app', 'User ID'),
			'type'=>Yii::t('app', 'Type'),
			'data'=>Yii::t('app', 'Data'),
		];
	}
	
	/**
	 * Returns an instance of the query-type for this model
	 * @return \asinfotrack\yii2\audittrail\models\AuditTrailEntryQuery
	 */
	public static function find()
	{
		return new AuditTrailEntryQuery(get_called_class());
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\db\BaseActiveRecord::beforeSave($insert)
	 * @throws \yii\base\InvalidCallException if trying to update a record (this is not allowed!)
	 */
	public function beforeSave($insert)
	{
		//prevent updating of audit trail entries
		if (!$insert) throw new InvalidCallException('Updating audit trail entries is not allowed!');
		
		//prepare data attribute if necessary
		if ($this->data !== null)
		{
			if (count($this->data) > 0) {
				$this->data = Json::encode($this->data);	
			} else {
				$this->data = null;
			}
		}
		
		return parent::beforeSave($insert);
	}
	
}