<?php
namespace App\Database\Migrations;

class CreateStudentsTable extends Migration {
    public function up() {
        $this->createTable('students', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->string('student_id', 20)->unique();
            $table->integer('department_id', true);
            $table->date('enrollment_date', true);
            $table->integer('year_of_study', false, 1);
            $table->integer('semester', false, 1);
            $table->string('guardian_name', 100, true);
            $table->string('guardian_phone', 20, true);
            $table->text('address', true);
            $table->date('date_of_birth', true);
            $table->enum('gender', ['M', 'F', 'O'], true);
            $table->enum('status', ['active', 'inactive', 'graduated', 'suspended'], false, 'active');
            $table->timestamps();
            
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->foreign('department_id', 'departments', 'SET NULL');
            $table->index('student_id');
            $table->index('user_id');
            $table->index('status');
        });
    }
    
    public function down() {
        $this->dropTable('students');
    }
}