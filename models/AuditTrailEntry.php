<?php
namespace asinfotrack\yii2\audittrail\models;

use Yii;
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
 * @property string $model_type
 * @property integer $happened_at
 * @property string $foreign_pk
 * @property integer $user_id
 * @property string $type
 * @property string $data
 * 
 * @property \stdClass[] $changes
 * @property bool $hasChanges
 */
class AuditTrailEntry extends \yii\db\ActiveRecord
{
	
	/**
	 * @var \stdClass[] holds the changes
	 */
	private $_changes = null;
	
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%audit_trail_entry}}';
	}
	
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['model_type','happened_at','foreign_pk','type'], 'required'],
				
			[['happened_at','user_id'], 'integer'],
			[['model_type','foreign_pk','type'], 'string', 'max'=>255],
			
			[['type'], 'in', 'range'=>AuditTrailBehavior::$AUDIT_TYPES],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id'=>Yii::t('app', 'ID'),
			'model_type'=>Yii::t('app', 'Table name'),
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
	 * @inheritdoc
	 *
	 * @see \yii\db\BaseActiveRecord::beforeSave($insert)
	 * @throws \yii\base\InvalidCallException if trying to update a record (this is not allowed!)
	 */
	public function beforeSave($insert)
	{
		//prevent updating of audit trail entries
		if (!$insert) throw new InvalidCallException('Updating audit trail entries is not allowed!');
		
		//prepare data attribute
		if ($this->_changes !== null) {
			$this->data = Json::encode($this->_changes);
		} else {
			$this->data = null;
		}
		
		return parent::beforeSave($insert);
	}
	
	/**
	 * Getter for the changes attribute
	 * 
	 * @return array array containing all changes (also as arrays)
	 */
	public function getChanges()
	{
		if ($this->_changes === null) {
			$this->_changes = $this->data !== null ? Json::decode($this->data) : [];
		}
		return $this->_changes;
	}
	
	/**
	 * Setter for the changes. The changes need to be provided as instances of
	 * stdClass. Each object containing attr, from and to.
	 * 
	 * @param \stdClass[] $changes the changes
	 */
	public function setChanges($changes)
	{
		$this->_changes = $changes;
		$this->data = Json::encode($this->_changes);
	}

	/**
	 * Checks whether or not this entry has changes
	 *
	 * @return bool true if there are changes, otherwise false
	 */
	public function getHasChanges()
	{
		return !empty($this->changes);
	}
	
	/**
	 * Adds a new change to this entry.
	 * 
	 * @param string $attr the name of the attribute
	 * @param mixed $from the old value
	 * @param mixed $to the new value
	 */
	public function addChange($attr, $from, $to)
	{
		$change = new \stdClass();
		$change->attr = $attr;
		$change->from = $from;
		$change->to = $to;
		$this->_changes[] = $change;
	}
	
}
