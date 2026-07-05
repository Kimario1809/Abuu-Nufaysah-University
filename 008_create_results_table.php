<?php
namespace App\Database\Migrations;

class CreateResultsTable extends Migration {
    public function up() {
        $this->createTable('results', function($table) {
            $table->id();
            $table->integer('enrollment_id');
            $table->decimal('score', 5, 2, true);
            $table->string('grade', 2, true);
            $table->decimal('grade_point', 3, 2, true);
            $table->integer('semester', true);
            $table->integer('year', true);
            $table->integer('uploaded_by', true);
            $table->boolean('is_published', false);
            $table->timestamps();
            
            $table->foreign('enrollment_id', 'enrollments', 'CASCADE');
            $table->foreign('uploaded_by', 'users', 'SET NULL');
            $table->index('enrollment_id');
            $table->index('is_published');
            $table->index(['semester', 'year']);
        });
    }
    
    public function down() {
        $this->dropTable('results');
    }
}