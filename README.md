# Yii2-audittrail
Yii2-audittrail is a behavior and a set of widgets to track all modifications performed on a model

## Advantages
This is not the first audit trail extension. So why use this one? Those are some of the major advantages:

* this extension works with composite primary keys
* it also works with console applications
* you can explicitly configure what fields to log
* GridView-baded widget to show the audit trail with huge customization options (eg rendering of values by closures, yii-formatter, etc.)

## Installation
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
$ composer require asinfotrack/yii2-audittrail
```

or add

```
"asinfotrack/yii2-audittrail": "dev-master"
```

to the `require` section of your `composer.json` file.


## Migration
	
After downloading everything you need to apply the migration creating the audit trail entry table:

	yii migrate --migrationPath=@vendor/asinfotrack/yii2-audittrail/migrations
	
To remove the table just do the same migration downwards.

## Usage

#### Behavior
Attach the behavior to your model and you're done:

```php
public function behaviors()
{
    return [
    	// ...
    	'audittrail'=>[
    		'class'=>AuditTrailBehavior::className(),
    		
    		// some of the optional configurations
    		'ignoredAttributes'=>['created_at','updated_at'],
    		'consoleUserId'=>1, 
			'attributeOutput'=>[
				'desktop_id'=>function ($value) {
					$model = Desktop::findOne($value);
					return sprintf('%s %s', $model->manufacturer, $model->device_name);
				},
				'last_checked'=>'datetime',
			],
    	],
    	// ...
    ];
}
```

### Widget
The widget is also very easy to use. Just provide the model to get the audit trail for:

```php
<?= AuditTrail::widget([
	'model'=>$model,
	
	// some of the optional configurations
	'userIdCallback'=>function ($userId, $model) {
 		return User::findOne($userId)->fullname;
	},
	'changeTypeCallback'=>function ($type, $model) {
		return Html::tag('span', strtoupper($type), ['class'=>'label label-info']);
	},
	'dataTableOptions'=>['class'=>'table table-condensed table-bordered'],
]) ?>
```

## Changelog

###### v0.8.3
This release fixes minor bugs and updates the dependencies

Issues fixed:
- #7: Class AuditTrailEntry uses table name without prefix

Other changes:
- dependency update

###### v0.8.2
Extended the functionality to detect changes (now respecting types). Furthermore added the possibility to specify whether
or not strings should get compared case-sensitive and if empty strings should be considered as null. Both of these
features can be configured via attributes of `AuditTrailBehavior`.

Issued fixed:
- #2: No record should be created if only changed attribute is in ignored attribute array (plm57)

Other changes:
- dependency update

###### v0.8.1
Dependencies updated, otherwise no changes.

###### v0.8.0
Main classes in a stable condition and further features will be added in a backwards-compatible way.
All breaking changes will lead to a new minor version.
