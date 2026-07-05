<?php
namespace App\Database\Migrations;

class CreateTimetablesTable extends Migration {
    public function up() {
        $this->createTable('timetables', function($table) {
            $table->id();
            $table->integer('course_id');
            $table->integer('lecturer_id', true);
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue', 100, true);
            $table->integer('semester', true);
            $table->integer('year', true);
            $table->boolean('is_active', true);
            $table->timestamps();
            
            $table->foreign('course_id', 'courses', 'CASCADE');
            $table->foreign('lecturer_id', 'lecturers', 'SET NULL');
            $table->index('course_id');
            $table->index('lecturer_id');
            $table->index('day_of_week');
            $table->index('is_active');
        });
    }
    
    public function down() {
        $this->dropTable('timetables');
    }
}