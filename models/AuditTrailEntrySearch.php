<?php
namespace asinfotrack\yii2\audittrail\models;

use yii\data\ActiveDataProvider;
use asinfotrack\yii2\audittrail\behaviors\AuditTrailBehavior;

class AuditTrailEntrySearch extends \asinfotrack\yii2\audittrail\models\AuditTrailEntry
{
	
	public function rules()
	{
		return [
			[['id','happened_at','user_id'], 'integer'],
			[['id','table_name','happened_at','foreign_pk','user_id','type','data'], 'safe'],
			[['type'], 'in', 'range'=>AuditTrailBehavior::$AUDIT_TYPES],
		];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::scenarios()
	 */
	public function scenarios()
	{
		//bypass scenarios() implementation in the parent class
		return Model::scenarios();
	}
	
	/**
	 * Creates the dataprovider for searching audit trails
	 * @param mixed $params the params as used by yii's search methods
	 * @param \yii\db\ActiveRecord $subject the model to get the audit trail entries for
	 * @return \asinfotrack\yii2\audittrail\models\ActiveDataProvider
	 */
	public function search($params, $subject=null)
	{
		/* @var $query \asinfotrack\yii2\audittrail\models\AuditTrailEntryQuery */
		
		//prepare data provider
		$query = AuditTrailEntry::find();
		if ($subject !== null) $query->subject($subject);
		$dataProvider = new ActiveDataProvider([
			'query'=>$query,
		]);

		//if no query data, return it
		if (!($this->load($params) && $this->validate())) {
			return $dataProvider;
		}
		
		//apply filtering
		$query->andFilterWhere([
			'id'=>$this->id,
			'happened_at'=>$this->happened_at,
			'user_id'=>$this->user_id,
			'type'=>$this->type,
		]);
		$query
			->andFilterWhere(['like', 'foreign_pk', $this->foreign_pk])
			->andFilterWhere(['like', 'data', $this->data]);

		return $dataProvider;		
	}
	
}