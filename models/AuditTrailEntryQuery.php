<?php
namespace asinfotrack\yii2\audittrail\models;

use asinfotrack\yii2\toolbox\helpers\PrimaryKey;

/**
 * Query class for audit trail entries
 * 
 * @author Pascal Mueller, AS infotrack AG
 * @link http://www.asinfotrack.ch
 * @license MIT
 */
class AuditTrailEntryQuery extends \yii\db\ActiveQuery
{
	
	/**
	 * Named scope to set the ordering to show the newest entries first.
	 * 
	 * @param boolean $sortByModelTypeFirst if set to true, the result will be sorted by
	 * model_type first
	 * @return \asinfotrack\yii2\audittrail\models\AuditTrailEntryQuery
	 */
	public function orderNewestFirst($sortByModelTypeFirst=true)
	{
		if ($sortByModelTypeFirst) {
			$this->orderBy(['model_type'=>SORT_ASC, 'happened_at'=>SORT_DESC]);
		} else {
			$this->orderBy(['happened_at'=>SORT_DESC]);
		}
		
		return $this;
	}
	
	/**
	 * Named scope to get entries for a certain model
	 * 
	 * @param \yii\db\ActiveRecord $model the model to get the audit trail for
	 * @return \asinfotrack\yii2\audittrail\models\AuditTrailEntryQuery
	 * @throws \yii\base\InvalidParamException if the model is not of type ActiveRecord
	 * @throws \yii\base\InvalidConfigException if the models pk is empty or invalid
	 */
	public function subject($model)
	{
		$this->modelType($model::className());
		$this->andWhere(['foreign_pk'=>static::createPrimaryKeyJson($model)]);
		return $this;
	}
	
	/**
	 * Named scope to filter entries by their model type
	 * 
	 * @param string $modelType class type of the model
	 * @return \asinfotrack\yii2\audittrail\models\AuditTrailEntryQuery
	 */
	public function modelType($modelType)
	{
		$this->andWhere(['model_type'=>$modelType]);
		return $this;
	}

	/**
	 * Creates the json-representation of the pk (array in the format attribute=>value)
	 * @see \asinfotrack\yii2\toolbox\helpers\PrimaryKey::asJson()
	 *
	 * @return string json-representation of the pk-array
	 * @throws \yii\base\InvalidParamException if the model is not of type ActiveRecord
	 * @throws \yii\base\InvalidConfigException if the models pk is empty or invalid
	 */
	protected static function createPrimaryKeyJson($model)
	{
		return PrimaryKey::asJson($model->owner);
	}
	
}
