<?php
namespace App\Database\Migrations;

class CreateChatTables extends Migration {
    public function up() {
        $this->createTable('chats', function($table) {
            $table->id();
            $table->integer('user_one_id');
            $table->integer('user_two_id');
            $table->timestamps();
            $table->foreign('user_one_id', 'users', 'CASCADE');
            $table->foreign('user_two_id', 'users', 'CASCADE');
            $table->index('user_one_id');
            $table->index('user_two_id');
        });

        $this->createTable('messages', function($table) {
            $table->id();
            $table->integer('chat_id');
            $table->integer('sender_id');
            $table->text('body')->nullable();
            $table->string('message_type', 20)->default('text');
            $table->string('status', 20)->default('sent');
            $table->timestamps();
            $table->foreign('chat_id', 'chats', 'CASCADE');
            $table->foreign('sender_id', 'users', 'CASCADE');
            $table->index('chat_id');
            $table->index('sender_id');
            $table->index('status');
        });

        $this->createTable('message_files', function($table) {
            $table->id();
            $table->integer('message_id');
            $table->string('file_name', 255);
            $table->string('file_path', 255);
            $table->string('file_type', 50);
            $table->timestamps();
            $table->foreign('message_id', 'messages', 'CASCADE');
            $table->index('message_id');
        });

        $this->createTable('user_themes', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('theme_name', 100)->default('Default Blue Theme');
            $table->text('custom_colors')->nullable();
            $table->timestamps();
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->index('user_id');
        });
    }

    public function down() {
        $this->dropTable('user_themes');
        $this->dropTable('message_files');
        $this->dropTable('messages');
        $this->dropTable('chats');
    }
}
