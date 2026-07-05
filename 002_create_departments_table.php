<?php
namespace App\Database\Migrations;

class CreateDepartmentsTable extends Migration {
    public function up() {
        $this->createTable('departments', function($table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100);
            $table->text('description', true);
            $table->timestamps();
            
            $table->index('code');
        });
    }
    
    public function down() {
        $this->dropTable('departments');
    }
}