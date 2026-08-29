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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();

            // Full domain name e.g. crm.client.com
            $table->string('domain')->unique();

            // Lifecycle status of this domain
            $table->enum('status', ['parked_default', 'connected', 'parked_on'])->default('parked_default');

            // Linked service (nullable — may be unassigned initially)
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();

            // Self-referential: if this domain is parked ON another domain
            $table->foreignId('parked_on_id')->nullable()->constrained('domains')->nullOnDelete();

            // Who controls the DNS
            $table->enum('dns_provider', ['arvan', 'self_ns', 'external'])->default('external');

            // ArvanCloud root zone (e.g. client.com) — only when dns_provider = arvan
            $table->string('arvan_zone')->nullable();

            // DNS record ID in ArvanCloud (for cleanup / re-provisioning)
            $table->string('arvan_record_id')->nullable();

            // Path to the generated Nginx config file on disk
            $table->string('nginx_config_path')->nullable();

            // SSL certificate status
            $table->enum('ssl_status', ['none', 'pending', 'active', 'expired'])->default('none');
            $table->timestamp('ssl_expires_at')->nullable();

            // Optional admin notes
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
