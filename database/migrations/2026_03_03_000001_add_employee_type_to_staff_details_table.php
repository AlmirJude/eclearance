<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_details', function (Blueprint $table) {
            $table->enum('employee_type', ['teaching', 'non-teaching'])->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('staff_details', function (Blueprint $table) {
            $table->dropColumn('employee_type');
        });
    }
};
