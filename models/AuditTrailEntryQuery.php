<?php
namespace asinfotrack\yii2\audittrail\models;

use yii\helpers\Json;

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
	 * @throws InvalidConfigException if the pk is null
	 */
	public function subject($model)
	{
		//fetch the objects pk
		$pk = $model->primaryKey();
		
		//assert that a valid pk was received
		if ($pk === null || !is_array($pk) || count($pk) == 0) {
			$msg = 'Invalid primary key definition: please provide a pk-definition for table ' . $model->tableName();
			throw new InvalidConfigException($msg);
		}
		
		//create final array and return it
		$arrPk = [];
		foreach ($pk as $pkCol) $arrPk[$pkCol] = $model->{$pkCol};
		
		$this->modelType($model::className());
		$this->andWhere(['foreign_pk'=>Json::encode($arrPk)]);
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
	
}
