<?php
namespace asinfotrack\yii2\audittrail\behaviors;

use Yii;
use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use asinfotrack\yii2\audittrail\models\AuditTrailEntry;
use asinfotrack\yii2\toolbox\helpers\PrimaryKey;

/**
 * Behavior which enables a model to be audited. Each modification (insert, update and delete)
 * will be logged with the changed field values.
 * To enable the behavior on a model simply add it to its behaviors. Further configuration is
 * possible. Check out this classes attributes to see what options there are.
 *
 * @author Pascal Mueller, AS infotrack AG
 * @link http://www.asinfotrack.ch
 * @license MIT
 */
class AuditTrailBehavior extends \yii\base\Behavior
{

	//constants for entry-types
	const AUDIT_TYPE_INSERT = 'insert';
	const AUDIT_TYPE_UPDATE = 'update';
	const AUDIT_TYPE_DELETE = 'delete';

	/**
	 * @var string[] holds all allowed audit types
	 */
	public static $AUDIT_TYPES = [self::AUDIT_TYPE_INSERT, self::AUDIT_TYPE_UPDATE, self::AUDIT_TYPE_DELETE];

	/**
	 * @var string[] if defined, the listed attributes will be ignored. Good examples for
	 * fields to ignore would be the db-fields of TimestampBehavior or BlameableBehavior.
	 */
	public $ignoredAttributes = [];

	/**
	 * @var \Closure|null optional closure to return the timestamp of an event. It needs to be
	 * in the format 'function() { }' returning an integer. If not set 'time()' is used.
	 */
	public $timestampCallback = null;

	/**
	 * @var integer|\Closure|null the user id to use if console actions modify a model.
	 * If a closure is used, use the 'function() { }' and return an integer or null.
	 */
	public $consoleUserId = null;

	/**
	 * @var boolean if set to true, the data fields will be persisted upon insert. Defaults to true.
	 */
	public $persistValuesOnInsert = true;

	/**
	 * @var bool if this is set to true, a change to an empty string value will be logged as null
	 */
	public $emptyStringIsNull = true;

	/**
	 * @var bool whether or not to compare strings in a case sensitive way to detect changes (default: false)
	 */
	public $caseSensitive = false;

	/**
	 * @var boolean if set to true, inserts will be logged (default: true)
	 */
	public $logInsert = true;

	/**
	 * @var boolean if set to true, updates will be logged (default: true)
	 */
	public $logUpdate = true;

	/**
	 * @var boolean if set to true, deletes will be logged (default: true)
	 */
	public $logDelete = true;

	/**
	 * @var \Closure[] contains an array with a model attribute as key and a either a string with
	 * a default yii-format or a closure as its value. Example:
	 * <code>
	 * 		[
	 * 			'title'=>function($value) {
	 * 				return Html::tag('strong', $value);
	 *			},
	 *			'email'=>'email',
	 * 		]
	 * </code>	 *
	 * This provides the AuditTrail-widget the ability to render related objects or complex value instead of
	 * raw data changed. You could for example display a users name instead of his plain id.
	 *
	 * Make sure each closure is in the format 'function ($value)'.
	 */
	public $attributeOutput = [];

	/**
	 * @inheritdoc
	 */
	public function attach($owner)
	{
		//assert owner extends class ActiveRecord
		if (!($owner instanceof ActiveRecord)) {
			throw new InvalidConfigException('AuditTrailBehavior can only be applied to classes extending \yii\db\ActiveRecord');
		}

		parent::attach($owner);
	}

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_INSERT  => 'onAfterInsert',
			ActiveRecord::EVENT_AFTER_UPDATE  => 'onAfterUpdate',
			ActiveRecord::EVENT_BEFORE_DELETE => 'onBeforeDelete',
		];
	}

	/**
	 * Handler for after insert event
	 *
	 * @param \yii\db\AfterSaveEvent $event the after save event
	 */
	public function onAfterInsert($event)
	{
		if (!$this->logInsert) return;
		$entry = $this->createPreparedAuditTrailEntry(self::AUDIT_TYPE_INSERT);

		//if configured write initial values
		if ($this->persistValuesOnInsert) {
			foreach ($this->getRelevantDbAttributes() as $attrName) {
				$newVal = $this->owner->{$attrName};

				//catch null values
				if ($newVal === null) continue;

				//catch empty strings
				if ($this->emptyStringIsNull && is_string($newVal) && empty($newVal)) {
					continue;
				}

				$entry->addChange($attrName, null, $newVal);
			}
		}

		static::saveEntry($entry);
	}

	/**
	 * Handler for after update event
	 *
	 * @param \yii\db\AfterSaveEvent $event the after save event
	 */
	public function onAfterUpdate($event)
	{
		if (!$this->logUpdate) return;
		$entry = $this->createPreparedAuditTrailEntry(self::AUDIT_TYPE_UPDATE);

		//fetch dirty attributes and add changes
		$relevantAttrs = $this->getRelevantDbAttributes();
		foreach ($event->changedAttributes as $attrName=>$oldVal) {
			//skip if ignored
			if (!in_array($attrName, $relevantAttrs)) continue;
			$newVal = static::castValue($oldVal, $this->owner->{$attrName});

			//additional comparison after casting
			if ((is_string($newVal) && call_user_func($this->caseSensitive ? 'strcmp' : 'strcasecmp', $oldVal, $newVal) === 0) || $oldVal === $newVal) {
				continue;
			}

			//catch empty strings
			if ($this->emptyStringIsNull && is_string($newVal) && empty($newVal)) {
				if ($oldVal === null) continue;
				$newVal = null;
			}

			$entry->addChange($attrName, $oldVal, $newVal);
		}

		//only save when there were changes
		if ($entry->hasChanges) static::saveEntry($entry);
	}

	/**
	 * Handler for before delete event
	 */
	public function onBeforeDelete()
	{
		if (!$this->logDelete) return;
		$entry = $this->createPreparedAuditTrailEntry(self::AUDIT_TYPE_DELETE);

		static::saveEntry($entry);
	}

	/**
	 * Creates and returns a preconfigured audit trail model
	 *
	 * @param string $changeKind the kind of audit trail entry (use this classes statics)
	 * @return \asinfotrack\yii2\audittrail\models\AuditTrailEntry
	 */
	protected function createPreparedAuditTrailEntry($changeKind)
	{
		$entry = new AuditTrailEntry([
			'model_type'=>$this->owner->className(),
			'foreign_pk'=>$this->createPrimaryKeyJson(),
			'happened_at'=>$this->getHappenedAt(),
			'user_id'=>$this->getUserId(),
			'type'=>$changeKind,
		]);
		return $entry;
	}

	/**
	 * Returns the user id to use for am audit trail entry
	 *
	 * @return integer|null returns either a user id or null.
	 */
	protected function getUserId()
	{
		if (Yii::$app instanceof \yii\console\Application) {
			if ($this->consoleUserId instanceof \Closure) {
				return call_user_func($this->consoleUserId);
			} else {
				return $this->consoleUserId;
			}
		} else if (Yii::$app->user->getIsGuest()) {
			return null;
		} else {
			return Yii::$app->user->getId();
		}
	}

	/**
	 * Returns the timestamp for the audit trail entry.
	 *
	 * @return integer unix-timestamp
	 */
	protected function getHappenedAt()
	{
		if ($this->timestampCallback !== null) {
			return call_user_func($this->timestampCallback);
		} else {
			return time();
		}
	}

	/**
	 * Creates the json-representation of the pk (array in the format attribute=>value)
	 * @see \asinfotrack\yii2\toolbox\helpers\PrimaryKey::asJson()
	 *
	 * @return string json-representation of the pk-array
	 * @throws \yii\base\InvalidParamException if the model is not of type ActiveRecord
	 * @throws \yii\base\InvalidConfigException if the models pk is empty or invalid
	 */
	protected function createPrimaryKeyJson()
	{
		return PrimaryKey::asJson($this->owner);
	}

	/**
	 * This method is responsible to create a list of relevant db-columns to track. The ones
	 * listed to exclude will be removed here already.
	 *
	 * @return string[] array containing relevant db-columns
	 */
	protected function getRelevantDbAttributes()
	{
		//get cols from db-schema
		$cols = array_keys($this->owner->getTableSchema()->columns);

		//return if no ignored cols
		if (empty($this->ignoredAttributes)) return $cols;

		//remove ignored cols and return
		$colsFinal = [];
		foreach ($cols as $c) {
			if (in_array($c, $this->ignoredAttributes)) continue;
			$colsFinal[] = $c;
		}

		return $colsFinal;
	}

	/**
	 * Casts the new value into the type of the old value when necessary
	 *
	 * @param mixed $oldVal the old value of which the type is relevant
	 * @param mixed $newVal the newly received value which will be disinfected
	 * @return mixed the type casted into correct type
	 */
	protected static function castValue($oldVal, $newVal)
	{
		//handle numerical and boolean values
		if (is_string($newVal) && is_numeric($newVal)) {
			if (is_bool($oldVal)) {
				return boolval($newVal);
			} else if (preg_match('/[0-9]+/', $newVal)) {
				return intval($newVal);
			} else if (is_float($oldVal)) {
				return floatval($newVal);
			} else if (is_double($oldVal)) {
				return doubleval($newVal);
			}
		}

		return $newVal;
	}

	/**
	 * Saves the entry and outputs an exception describing the problem if necessary
	 *
	 * @param \asinfotrack\yii2\audittrail\models\AuditTrailEntry $entry
	 * @throws InvalidValueException if entry couldn't be saved (validation error)
	 */
	protected static function saveEntry($entry)
	{
		//do nothing if successful
		if ($entry->save()) return;

		//otherwise throw exception
		$lines = [];
		foreach ($entry->errors as $attr=>$errors) {
			foreach ($errors as $err) $lines[] = $err;
		}
		throw new InvalidValueException(sprintf('Error while saving audit-trail-entry: %s', implode(', ', $lines)));
	}

}
