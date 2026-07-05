<?php
namespace App\Database\Migrations;

class CreateAiChatsTable extends Migration {
    public function up() {
        $this->createTable('ai_chats', function($table) {
            $table->id();
            $table->integer('user_id', true);
            $table->string('role', 50);
            $table->string('sender', 20);
            $table->text('message');
            $table->timestamp('created_at');
            $table->index('user_id');
            $table->index('sender');
        });
    }

    public function down() {
        $this->dropTable('ai_chats');
    }
}
