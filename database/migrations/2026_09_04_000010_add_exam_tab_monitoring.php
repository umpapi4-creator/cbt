<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedTinyInteger('tab_switch_count')->default(0)->after('unanswered_count');
            $table->boolean('terminated_for_tab_switch')->default(false)->after('tab_switch_count');
            $table->dateTime('terminated_at')->nullable()->after('terminated_for_tab_switch');
        });

        Schema::create('exam_tab_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->string('event_type', 40)->default('visibility_hidden');
            $table->dateTime('occurred_at');
            $table->unsignedTinyInteger('count_after');
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
            $table->index(['attempt_id', 'occurred_at']);
            $table->index('notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_tab_events');

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['tab_switch_count', 'terminated_for_tab_switch', 'terminated_at']);
        });
    }
};
