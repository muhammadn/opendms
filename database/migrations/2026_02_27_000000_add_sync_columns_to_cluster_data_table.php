<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add store-and-forward tracking columns for hybrid cloud/field deployment.
     *
     * These columns are ONLY meaningful in hybrid deployment mode, where a field
     * node running SQLite syncs records to a central server opportunistically.
     *
     * synced    — Three-state nullable boolean:
     *               null  = sync not applicable (production mode where this IS the
     *                       central DB, or standalone offline with no central URL)
     *               false = pending sync (hybrid mode, not yet delivered upstream)
     *               true  = successfully synced to central DMS
     *
     * synced_at — Timestamp of successful sync. Null in production/offline modes
     *             and in hybrid mode until the record is confirmed delivered.
     *
     * In production (MySQL/PostgreSQL central DB) and in fully offline standalone
     * (no CENTRAL_DMS_URL configured), ProcessMqttMessage leaves both columns as
     * null and never dispatches SyncRecordToCloud, so there is zero overhead.
     */
    public function up(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            // Nullable: null=not applicable, false=pending, true=synced
            $table->boolean('synced')->nullable()->default(null)->index()->after('duck_type');
            $table->timestamp('synced_at')->nullable()->after('synced');
        });
    }

    public function down(): void
    {
        Schema::table('cluster_data', function (Blueprint $table) {
            $table->dropColumn(['synced', 'synced_at']);
        });
    }
};
