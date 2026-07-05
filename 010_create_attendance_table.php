<?php
namespace App\Database\Migrations;

class CreateAttendanceTable extends Migration {
    public function up() {
        $this->createTable('attendance', function($table) {
            $table->id();
            $table->integer('enrollment_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused']);
            $table->time('time_in', true);
            $table->time('time_out', true);
            $table->timestamps();
            
            $table->foreign('enrollment_id', 'enrollments', 'CASCADE');
            $table->unique(['enrollment_id', 'date']);
            $table->index('enrollment_id');
            $table->index('date');
            $table->index('status');
        });
    }
    
    public function down() {
        $this->dropTable('attendance');
    }
}