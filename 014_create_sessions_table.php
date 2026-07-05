<?php
namespace App\Database\Migrations;

class CreateSessionsTable extends Migration {
    public function up() {
        $this->createTable('sessions', function($table) {
            $table->string('id', 64)->primary();
            $table->integer('user_id', true);
            $table->string('ip_address', 45, true);
            $table->string('user_agent', 255, true);
            $table->text('payload', true);
            $table->integer('last_activity');
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'SET NULL');
            $table->index('user_id');
            $table->index('last_activity');
        });
    }
    
    public function down() {
        $this->dropTable('sessions');
    }
}