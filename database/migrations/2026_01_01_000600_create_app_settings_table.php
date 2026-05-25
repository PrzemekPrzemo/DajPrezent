<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master-admin settings store. Replaces the .env-only model of
 * configuring PayU / KSeF / invoice numbering — admins can now
 * change these from /admin/settings without SSH or redeploy.
 *
 * Sensitive values (client_secret, md5_key, ksef token, certificate
 * passphrase) are encrypted by Laravel Crypt on write and decrypted
 * on read via the SettingsRepository.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamp('updated_at')->nullable();
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
