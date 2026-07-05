<?php
namespace App\Database\Migrations;

class CreateLecturersTable extends Migration {
    public function up() {
        $this->createTable('lecturers', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('employee_id', 20)->unique();
            $table->integer('department_id', true);
            $table->string('qualification', 100, true);
            $table->string('specialization', 100, true);
            $table->date('hire_date', true);
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->foreign('department_id', 'departments', 'SET NULL');
            $table->index('employee_id');
            $table->index('user_id');
        });
    }
    
    public function down() {
        $this->dropTable('lecturers');
    }
}