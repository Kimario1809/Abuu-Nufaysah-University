<?php
namespace App\Database\Migrations;

class CreateAnnouncementsTable extends Migration {
    public function up() {
        $this->createTable('announcements', function($table) {
            $table->id();
            $table->string('title', 200);
            $table->text('content');
            $table->integer('author_id');
            $table->enum('author_role', ['admin', 'lecturer']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'], false, 'medium');
            $table->enum('target_audience', ['all', 'students', 'lecturers', 'admins'], false, 'all');
            $table->integer('department_id', true);
            $table->integer('course_id', true);
            $table->boolean('is_published', true);
            $table->datetime('expires_at', true);
            $table->timestamps();
            
            $table->foreign('author_id', 'users', 'CASCADE');
            $table->foreign('department_id', 'departments', 'SET NULL');
            $table->foreign('course_id', 'courses', 'SET NULL');
            $table->index('is_published');
            $table->index('target_audience');
            $table->index('priority');
            $table->index('expires_at');
        });
    }
    
    public function down() {
        $this->dropTable('announcements');
    }
}