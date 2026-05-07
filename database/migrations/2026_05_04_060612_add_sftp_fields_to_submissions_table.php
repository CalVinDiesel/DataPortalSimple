<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('sftp_host')->nullable()->after('google_drive_link');
            $table->integer('sftp_port')->default(22)->after('sftp_host');
            $table->string('sftp_username')->nullable()->after('sftp_port');
            $table->string('sftp_password')->nullable()->after('sftp_username');
            $table->string('sftp_path')->nullable()->after('sftp_password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['sftp_host', 'sftp_port', 'sftp_username', 'sftp_password', 'sftp_path']);
        });
    }
};
