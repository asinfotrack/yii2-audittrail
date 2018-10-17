<?php
namespace asinfotrack\yii2\audittrail\widgets;

use Yii;
use yii\helpers\Html;
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
	 * @var \asinfotrack\yii2\audittrail\behaviors\AuditTrailBehavior holds the configuration
	 * of the audit trail behavior once loaded or null if not found
	 */
	protected $behaviorInstance;
	
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
	 * @var string[] Attributes listed in this array won't be listed in the data table no
	 * matter if there were changes in that attribute or not.
	 */
	public $hiddenAttributes = [];
	
	/**
	 * @var mixed the options for the inner table displaying the actual changes
	 */
	public $dataTableOptions = ['class'=>'table table-condensed table-bordered'];

	/**
	 * @var array configuration for the data tables column-widths. Three keys are used:
	 * - 'attribute':	width of the first column containing the attribute name
	 * - 'from':		width of the from-column
	 * - 'to:			width of the to column
	 * 
	 * Used a string to define this property. The string will be used as the css-width-value
	 * of the corresponding '<col>'-tag within the colgroup definition.
	 */
	public $dataTableColumnWidths = [
		'attribute'=>null,
		'from'=>'30%',
		'to'=>'30%',	
	];
	
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
		$this->behaviorInstance = $this->getBehaviorInstance();
		if ($this->behaviorInstance === null) {
			throw new InvalidConfigException('Model of type ' . $this->model->className() . 'doesn\'t have AuditTrailBehavior!');
		}
		
		//data provider configuration
		$searchModel = new AuditTrailEntrySearch();
		$searchParams = $this->searchParams === null ? Yii::$app->request->getQueryParams() : $this->searchParams;
		$this->dataProvider = $searchModel->search($searchParams, $this->model);
		
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
		$attributeOutput = $this->behaviorInstance->attributeOutput;
		$dataTableOptions = $this->dataTableOptions;
		
		//prepare column config
		return [
			'happened_at:datetime',
			[
				'attribute'=>'type',
				'format'=>$changeTypeCallback === null ? 'text' : 'raw',
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
				'format'=>$userIdCallback === null ? 'text' : 'raw',
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
				'format'=>'raw',
				'value'=>function ($model, $key, $index, $column) use ($attributeOutput, $dataTableOptions) {
					/* @var $model \yii\db\ActiveRecord */
					
					//catch empty data
					$changes = $model->changes;
					if ($changes === null || count($changes) === 0) {
						return null;
					}
					
					$ret = Html::beginTag('table', $dataTableOptions);
					
					//colgroup
					$ret .= Html::beginTag('colgroup');
					$widths = $this->dataTableColumnWidths;
					$ret .= Html::tag('col', '', ['style'=>sprintf('width: %s;', isset($widths['attribute']) ? $widths['attribute'] : 'auto')]);
					if ($model->type === AuditTrailBehavior::AUDIT_TYPE_UPDATE) {	
						$ret .= Html::tag('col', '', ['style'=>sprintf('width: %s;', isset($widths['from']) ? $widths['from'] : 'auto')]);
					}
					$ret .= Html::tag('col', '', ['style'=>sprintf('width: %s;', isset($widths['to']) ? $widths['to'] : 'auto')]);
					
					//table head
					$ret .= Html::beginTag('thead');
					$ret .= Html::beginTag('tr');
					$ret .= Html::tag('th', Yii::t('app', 'Attribute'));
					if ($model->type === AuditTrailBehavior::AUDIT_TYPE_UPDATE) {
						$ret .= Html::tag('th', Yii::t('app', 'From'));
					}
					$ret .= Html::tag('th', Yii::t('app', 'To'));
					$ret .= Html::endTag('tr');
					$ret .= Html::endTag('thead');
					
					//table body
					$ret .= Html::beginTag('tbody');
					foreach ($changes as $change) {
						//skip hidden attributes
						if (in_array($change['attr'], $this->hiddenAttributes)) continue;
						
						//render data row
						$ret .= Html::beginTag('tr');
						$ret .= Html::tag('td', $this->model->getAttributeLabel($change['attr']));
						if ($model->type === AuditTrailBehavior::AUDIT_TYPE_UPDATE) {
							$ret .= Html::tag('td', $this->formatValue($change['attr'], $change['from']));
						}
						$ret .= Html::tag('td', $this->formatValue($change['attr'], $change['to']));
						$ret .= Html::endTag('tr');
					}
					$ret .= Html::endTag('tbody');
					
					$ret .= Html::endTag('table');
					
					return $ret;
				},
			],
		];
	}
	
	/**
	 * Formats a value into its final outoput. If the value is null, the formatters null-display is used.
	 * If there is a value and nothing is declared in attributeOutput, the raw value is returned. If an
	 * output is defined (either a format-string or a closure, it is used for formatting.
	 * 
	 * @param string $attrName name of the attribute
	 * @param mixed $value the value
	 * @throws InvalidConfigException if the attributeOutput for this attribute is not a string or closure
	 * @return mixed the formatted output value
	 */
	protected function formatValue($attrName, $value)
	{
		//check if there is a formatter defined
		if (isset($this->behaviorInstance->attributeOutput[$attrName])) {
			$attrOutput = $this->behaviorInstance->attributeOutput[$attrName];
			
			//assert attr output format is either a string or a closure
			if (!is_string($attrOutput) && !($attrOutput instanceof \Closure)) {
				$msg = sprintf('The attribute out put for the attribute %s is invalid. It needs to be a string or a closure!', $attrName);
				throw new InvalidConfigException($msg);
			}

			//perform formatting
			if ($attrOutput instanceof \Closure) {
				return call_user_func($attrOutput, $value);
			} else {
				return Yii::$app->formatter->format($value, $attrOutput);
			}			
		} else {
			return Yii::$app->formatter->asText($value);
		}
	}
	
	/**
	 * Finds the models audit trail behavior configuration and returns it
	 * 
	 * @return \asinfotrack\yii2\audittrail\behaviors\AuditTrailBehavior|null the configuration or null if not found
	 */
	protected function getBehaviorInstance()
	{
		foreach ($this->model->behaviors() as $name=>$config) {
			if (isset($config['class']) && $config['class'] == AuditTrailBehavior::className()) {
				return $this->model->getBehavior($name);
			}
		}		
		return null;
	}
	
}
