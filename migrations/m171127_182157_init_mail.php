<?php

use yii\db\Migration;

/**
 * Class m171127_182157_init_mail
 */
class m171127_182157_init_mail extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable('{{%mail_templates}}', [
            'uuid' => 'char(36) not null',
            'code' => 'varchar(100) not null',
            'active' => 'boolean default "1"',
            'from' => 'varchar(255) not null',
            'to' => 'varchar(255) not null',
            'reply_to' => 'varchar(255) not null',
            'copy' => 'varchar(255) not null',
            'subject' => 'varchar(255) not null',
            'text' => 'text not null',
            'html' => 'text not null',
            'workflow_uuid' => 'char(36) null default null',
            'PRIMARY KEY (`uuid`)',
            'CONSTRAINT FOREIGN KEY (`workflow_uuid`) REFERENCES {{%workflow}} (`uuid`) ON DELETE SET NULL ON UPDATE CASCADE',
        ], 'ENGINE InnoDB');

        $this->createTable('{{%forms_mail}}', [
            'form_uuid' => 'char(36) not null',
            'template_uuid' => 'char(36) not null',
            'PRIMARY KEY (`form_uuid`, `template_uuid`)',
            'CONSTRAINT FOREIGN KEY (`form_uuid`) REFERENCES {{%forms}} (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE',
            'CONSTRAINT FOREIGN KEY (`template_uuid`) REFERENCES {{%mail_templates}} (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE',
        ], 'ENGINE InnoDB');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable('{{%forms_mail}}');
        $this->dropTable('{{%mail_templates}}');
    }
}