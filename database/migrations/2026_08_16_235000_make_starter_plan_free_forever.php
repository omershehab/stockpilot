<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')
            ->where('plan', 'starter')
            ->where('is_active', true)
            ->update([
                'subscription_status' => 'active',
                'trial_ends_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Starter is intentionally permanent and should not revert to a trial on rollback.
    }
};
