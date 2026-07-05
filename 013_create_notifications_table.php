<?php
namespace App\Database\Migrations;

class CreateNotificationsTable extends Migration {
    public function up() {
        $this->createTable('notifications', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type', 50);
            $table->string('title', 200);
            $table->text('message');
            $table->string('link', 255, true);
            $table->boolean('is_read', false);
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->index('user_id');
            $table->index('is_read');
            $table->index('type');
            $table->index('created_at');
        });
    }
    
    public function down() {
        $this->dropTable('notifications');
    }
}