<?php
namespace App\Database\Migrations;

class CreateEnrollmentsTable extends Migration {
    public function up() {
        $this->createTable('enrollments', function($table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('course_id');
            $table->datetime('enrollment_date', false)->default('CURRENT_TIMESTAMP');
            $table->enum('status', ['enrolled', 'dropped', 'completed'], false, 'enrolled');
            $table->string('grade', 2, true);
            $table->timestamps();
            
            $table->foreign('student_id', 'students', 'CASCADE');
            $table->foreign('course_id', 'courses', 'CASCADE');
            $table->unique('student_id', 'course_id');
            $table->index('student_id');
            $table->index('course_id');
            $table->index('status');
        });
    }
    
    public function down() {
        $this->dropTable('enrollments');
    }
}