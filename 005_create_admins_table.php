<?php
namespace App\Database\Migrations;

class CreateAdminsTable extends Migration {
    public function up() {
        $this->createTable('admins', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('admin_id', 20)->unique();
            $table->json('permissions', true);
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->index('admin_id');
        });
    }
    
    public function down() {
        $this->dropTable('admins');
    }
}