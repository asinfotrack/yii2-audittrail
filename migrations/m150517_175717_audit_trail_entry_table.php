<?php

use yii\db\Schema;
use yii\db\Expression;

/**
 * Migration to create or remove audit trail entry table
 * 
 * @author Pascal Mueller, AS infotrack AG
 * @link http://www.asinfotrack.ch
 * @license MIT
 */
class m150517_175717_audit_trail_entry_table extends \yii\db\Migration
{
	
	/**
	 * @inheritdoc
	 */
	public function up()
	{
		$this->createTable('{{%audit_trail_entry}}', [
			'id'=>Schema::TYPE_PK,
			'model_type'=>Schema::TYPE_STRING . ' NOT NULL',
			'happened_at'=>Schema::TYPE_INTEGER . ' NOT NULL',
			'foreign_pk'=>Schema::TYPE_STRING . ' NOT NULL',
			'user_id'=>Schema::TYPE_INTEGER . ' NULL',
			'type'=>Schema::TYPE_STRING . ' NOT NULL',
			'data'=>Schema::TYPE_TEXT . ' NULL',
		]);
		$this->createIndex('IN_audit_trail_entry_fast_access', '{{%audit_trail_entry}}', [
			new Expression('`model_type` ASC'), 
			new Expression('`happened_at` DESC'),
		]);
		$this->addForeignKey('FK_audit_trail_entry_user', 
			'{{%audit_trail_entry}}', ['user_id'], 
			'{{%user}}', ['id'], 
			'SET NULL', 'CASCADE');
	}
	
	/**
	 * @inheritdoc
	 */
	public function down()
	{
		$this->dropForeignKey('FK_audit_trail_entry_user', '{{%audit_trail_entry}}');
		$this->dropIndex('IN_audit_trail_entry_fast_access', '{{%audit_trail_entry}}');
		$this->dropTable('{{%audit_trail_entry}}');
	}
	
}
