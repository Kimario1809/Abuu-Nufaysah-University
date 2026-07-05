<?php
namespace App\Database\Migrations;

class CreatePasswordResetsTable extends Migration {
    public function up() {
        $this->createTable('password_resets', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('token', 64)->unique();
            $table->datetime('expires_at');
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->index('token');
            $table->index('expires_at');
        });
    }
    
    public function down() {
        $this->dropTable('password_resets');
    }
}