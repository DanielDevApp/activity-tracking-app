<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Current state: one row per activity per day.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->date('log_date')->index();
            $table->string('status')->default('pending')->index(); // pending | done
            $table->text('remark')->nullable();
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'log_date']); // no duplicate checklist rows, even under races
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
