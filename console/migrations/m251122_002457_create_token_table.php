<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%token}}`.
 */
class m251122_002457_create_token_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%token}}', [
            'id' => $this->primaryKey(),
            'token' => $this->string()->notNull()->unique(),
            'user_id' => $this->integer()->notNull(),
            'type' => $this->string()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->bigInteger()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-token-user_id',
            '{{%token}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE'
        );

        $this->createIndex(
            'idx-token-token',
            '{{%token}}',
            'token',
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-token-user_id',
            '{{%token}}'
        );
        $this->dropIndex('idx-token-token', '{{%token}}');
        $this->dropTable('{{%token}}');
    }
}
