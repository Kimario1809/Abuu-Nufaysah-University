<?php
namespace App\Database\Migrations;

class CreateRbacTables extends Migration {
    public function up() {
        $this->createTable('roles', function($table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->createTable('permissions', function($table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('module', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        $this->createTable('user_roles', function($table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('role_id');
            $table->timestamps();
            $table->foreign('user_id', 'users', 'CASCADE');
            $table->foreign('role_id', 'roles', 'CASCADE');
            $table->index('user_id');
            $table->index('role_id');
        });

        $this->createTable('role_permissions', function($table) {
            $table->id();
            $table->integer('role_id');
            $table->integer('permission_id');
            $table->timestamps();
            $table->foreign('role_id', 'roles', 'CASCADE');
            $table->foreign('permission_id', 'permissions', 'CASCADE');
            $table->index('role_id');
            $table->index('permission_id');
        });

        $this->createTable('assignments', function($table) {
            $table->id();
            $table->integer('course_id');
            $table->integer('lecturer_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->foreign('course_id', 'courses', 'CASCADE');
            $table->foreign('lecturer_id', 'lecturers', 'CASCADE');
            $table->index('course_id');
            $table->index('lecturer_id');
        });

        $this->createTable('submissions', function($table) {
            $table->id();
            $table->integer('assignment_id');
            $table->integer('student_id');
            $table->text('content')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->timestamps();
            $table->foreign('assignment_id', 'assignments', 'CASCADE');
            $table->foreign('student_id', 'students', 'CASCADE');
            $table->index('assignment_id');
            $table->index('student_id');
        });

        $this->createTable('grades', function($table) {
            $table->id();
            $table->integer('student_id');
            $table->integer('course_id');
            $table->integer('lecturer_id');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->timestamps();
            $table->foreign('student_id', 'students', 'CASCADE');
            $table->foreign('course_id', 'courses', 'CASCADE');
            $table->foreign('lecturer_id', 'lecturers', 'CASCADE');
            $table->index('student_id');
            $table->index('course_id');
        });

        $this->createTable('announcements', function($table) {
            $table->id();
            $table->integer('author_id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('audience', 20)->default('all');
            $table->timestamps();
            $table->foreign('author_id', 'users', 'CASCADE');
            $table->index('author_id');
        });

        $this->createTable('audit_logs', function($table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->text('details')->nullable();
            $table->timestamps();
            $table->foreign('user_id', 'users', 'SET NULL');
            $table->index('user_id');
            $table->index('module');
        });
    }

    public function down() {
        $this->dropTable('audit_logs');
        $this->dropTable('announcements');
        $this->dropTable('grades');
        $this->dropTable('submissions');
        $this->dropTable('assignments');
        $this->dropTable('role_permissions');
        $this->dropTable('user_roles');
        $this->dropTable('permissions');
        $this->dropTable('roles');
    }
}
