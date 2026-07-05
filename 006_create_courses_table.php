<?php
namespace App\Database\Migrations;

class CreateCoursesTable extends Migration {
    public function up() {
        $this->createTable('courses', function($table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 200);
            $table->integer('department_id', true);
            $table->integer('credit_hours');
            $table->integer('lecturer_id', true);
            $table->text('description', true);
            $table->integer('semester', true);
            $table->integer('year', true);
            $table->integer('capacity', false, 50);
            $table->boolean('is_active', true);
            $table->timestamps();
            
            $table->foreign('department_id', 'departments', 'SET NULL');
            $table->foreign('lecturer_id', 'lecturers', 'SET NULL');
            $table->index('code');
            $table->index('lecturer_id');
            $table->index('is_active');
        });
    }
    
    public function down() {
        $this->dropTable('courses');
    }
}