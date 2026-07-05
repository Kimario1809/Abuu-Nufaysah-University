<?php
namespace App\Database\Migrations;

class CreatePaymentsTable extends Migration {
    public function up() {
        $this->createTable('payments', function($table) {
            $table->id();
            $table->integer('student_id');
            $table->string('receipt_no', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->enum('payment_type', ['tuition', 'registration', 'library', 'other']);
            $table->datetime('payment_date', false)->default('CURRENT_TIMESTAMP');
            $table->date('due_date', true);
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'], false, 'pending');
            $table->string('payment_method', 50, true);
            $table->string('transaction_id', 100, true);
            $table->text('notes', true);
            $table->timestamps();
            
            $table->foreign('student_id', 'students', 'CASCADE');
            $table->index('student_id');
            $table->index('receipt_no');
            $table->index('status');
        });
    }
    
    public function down() {
        $this->dropTable('payments');
    }
}