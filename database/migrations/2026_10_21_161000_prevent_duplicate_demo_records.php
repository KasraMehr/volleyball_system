<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('training_sessions')->whereNotIn('id', function ($query): void {
            $query->from('training_sessions')->selectRaw('MIN(id)')->groupBy('team_id', 'session_date', 'title');
        })->delete();
        DB::table('performance_assessments')->whereNotIn('id', function ($query): void {
            $query->from('performance_assessments')->selectRaw('MIN(id)')->groupBy('athlete_id', 'assessment_date');
        })->delete();

        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->unique(['team_id', 'session_date', 'title'], 'sessions_team_date_title_unique');
        });
        Schema::table('performance_assessments', function (Blueprint $table): void {
            $table->unique(['athlete_id', 'assessment_date'], 'assessments_athlete_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', fn (Blueprint $table) => $table->dropUnique('sessions_team_date_title_unique'));
        Schema::table('performance_assessments', fn (Blueprint $table) => $table->dropUnique('assessments_athlete_date_unique'));
    }
};
