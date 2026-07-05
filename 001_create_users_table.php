<?php
namespace App\Database\Migrations;

class CreateUsersTable extends Migration {
    public function up() {
        $this->createTable('users', function($table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email', 100)->unique();
            $table->string('password_hash', 255);
            $table->enum('role', ['admin', 'lecturer', 'student']);
            $table->string('full_name', 100);
            $table->string('phone', 20, true);
            $table->string('avatar', 255, true);
            $table->boolean('is_active', true);
            $table->datetime('last_login', true);
            $table->timestamps();
            
            $table->index('role');
            $table->index('email');
        });
    }
    
    public function down() {
        $this->dropTable('users');
    }
}