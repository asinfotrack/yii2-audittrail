<?php
namespace \asinfotrack\yii2\audittrail\widgets;

use yii\grid\DataColumn;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\base\InvalidConfigException;
use asinfotrack\yii2\audittrail\behaviors\AuditTrailBehavior;
use asinfotrack\yii2\audittrail\models\AuditTrailEntrySearch;

/**
 * This widget renders the audit trail of a model in the form of a gridview.
 * Following is an complex configuration for the widget as an example:
 * 
 * <code>
 * AuditTrail::widget([
 * 		'model'=>$model,
 * 		'userIdCallback'=>function ($userId, $model) {
 * 			return User::findOne($userId)->fullname;
 * 		},
 * 		'changeTypeCallback'=>function ($type, $model) {
 * 			return Html::tag('span', strtoupper($type), ['class'=>'label label-info']);
 * 		},
 * 		'attributeRenderCallbacks'=>[
 * 			'desktop_id'=>function ($value) {
 * 				$model = Desktop::findOne($value);
 * 				return sprintf('%s %s', $model->manufacturer, $model->device_name);
 * 			},
 * 			'last_checked'=>function ($value) {
 * 				return Yii::$app->formatter->asDatetime($value);
 * 			},
 * 		],
 *		'dataTableOptions'=>['class'=>'table table-condensed table-bordered'],
 * ]);
 * </code>
 * 
 * @author Pascal Mueller, AS infotrack AG
 * @link http://www.asinfotrack.ch
 * @license MIT
 */
class AuditTrail extends \yii\grid\GridView
{
	
	/**
	 * @var \yii\db\ActiveRecord the model to list the audit for. The model
	 * MUST implement AuditTrailBehavior!
	 */
	public $model;
		
	/**
	 * @var mixed the params to use for the search filtering. Defaults to
	 * 'Yii::$app->request->getQueryParams()'
	 */
	public $searchParams = null;
	
	/**
	 * @var \Closure|null optional closure to render the value of the user_id column.
	 * If provided use the format 'function ($userId, $model)' and return the contents 
	 * of the cell.
	 * 
	 * If not set the user id will be render in plain format.
	 */
	public $userIdCallback = null;
	
	/**
	 * @var \Closure|null optional closure to render the value of the type column.
	 * If provided use the format 'function ($type, $model)' and return the contents
	 * of the cell.
	 * To see what possible values there are for the type, check out the statics of the
	 * class AuditTrailBehavior.
	 * 
	 * If not set the type will be rendered i plain format.
	 */
	public $changeTypeCallback = null;
	
	/**
	 * @var \Closure[] contains an array with a model attribute as key and a closure as its
	 * value. Example:
	 * <code>
	 * 		[
	 * 			'email'=>function($value) {
	 * 				return Html::mailto($value);
	 *			},
	 * 		]
	 * </code>	 * 
	 * This provides you the ability to render related objects or complex value instead of
	 * raw data changed. You could for example display a users name instead of his plain id.
	 * 
	 * Make sure each closure is in the format 'function ($value)'.
	 */
	public $attributeRenderCallbacks = [];
	
	/**
	 * @var mixed the options for the inner table displaying the actual changes
	 */
	public $dataTableOptions = ['class'=>'table table-condensed table-bordered'];
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\grid\GridView::init()
	 */
	public function init()
	{	
		//assert model is not null
		if (empty($this->model)) {
			throw new InvalidConfigException('Model cannot be null!');
		}
		
		//assert model has behavior
		$foundBehavior = false;
		foreach ($this->model->behaviors() as $b) {
			if ($b['class'] != AuditTrailBehavior::className()) continue;
			
			$foundBehavior = true;
			break;
		}
		if (!$foundBehavior) {
			throw new InvalidConfigException('Model of type ' . $this->model->className() . 'doesn\'t have AuditTrailBehavior!');
		}
		
		//data provider configuration
		$searchModel = new AuditTrailEntrySearch();
		$this->dataProvider = $searchModel->search($this->searchParams === null ? Yii::$app->request->getQueryParams() : $this->searchParams);
		
		//prepare columns of grid view
		$this->columns = $this->createColumnConfig();
		
		//parent initialization
		parent::init();		
	}
	
	/**
	 * Prepares the default column configuration for the grid view
	 * 
	 * @return mixed the default column configuration for the gridview
	 */
	protected function createColumnConfig()
	{
		//get local references
		$userIdCallback = $this->userIdCallback;
		$changeTypeCallback = $this->changeTypeCallback;
		$attributeRenderCallbacks = $this->attributeRenderCallbacks;
		$dataTableOptions = $this->dataTableOptions;
		
		//prepare column config
		return [
			'happened_at:datetime',
			[
				'attribute'=>'type',
				'value'=>function ($model, $key, $index, $column) use ($changeTypeCallback) {
					if ($changeTypeCallback === null) {
						return $model->type;
					} else {
						return call_user_func($changeTypeCallback, $model->type, $model);
					}
				},
			],
			[
				'attribute'=>'user_id',
				'value'=>function ($model, $key, $index, $column) use ($userIdCallback) {
					if ($userIdCallback === null) {
						return $model->user_id;
					} else {					
						return call_user_func($userIdCallback, $model->user_id, $model);
					}					
				},
			],
			[
				'attribute'=>'data',
				'value'=>function ($model, $key, $index, $column) use ($attributeRenderCallbacks, $dataTableOptions) {
					/* @var $model \yii\db\ActiveRecord */
					
					//catch empty data
					if ($model->data === null) return null;
					$data = Json::decode($model->data);
					if (count($data) == null) return null;
					
					$ret = Html::beginTag('table', $dataTableOptions);
					
					//table head
					$ret .= Html::beginTag('thead');
					$ret .= Html::beginTag('tr');
					$ret .= Html::tag('th', Yii::t('app', 'Attribute'));
					$ret .= Html::tag('th', Yii::t('app', 'From'));
					$ret .= Html::tag('th', Yii::t('app', 'To'));
					$ret .= Html::endTag('tr');
					$ret .= Html::endTag('thead');
					
					//table body
					$ret .= Html::beginTag('tbody');
					foreach ($data as $change) {
						$callback = isset($attributeRenderCallbacks[$change->attr]) ? $attributeRenderCallbacks[$change->attr] : null;
						
						$ret .= Html::beginTag('tr');
						$ret .= Html::tag('td', $model->getAttributeLabel($change->attr));
						$ret .= Html::tag('td', $callback !== null ? call_user_func($callback, $change->from) : $change->from);
						$ret .= Html::tag('td', $callback !== null ? call_user_func($callback, $change->to) : $change->to);
						$ret .= Html::endTag('tr');
					}
					$ret .= Html::endTag('tbody');
					
					$ret .= Html::endTag('table');
				},
			],
		];
	}
	
}