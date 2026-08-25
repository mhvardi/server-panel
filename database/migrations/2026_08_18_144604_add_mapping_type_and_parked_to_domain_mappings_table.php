<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_mappings', function (Blueprint $table) {
            // نوع اتصال دامنه: از طریق آروان، پارک روی دامنه دیگر، یا مستقیم
            $table->enum('mapping_type', ['arvan', 'parked', 'direct'])->default('arvan')->after('destination_domain');
            // اگر parked باشد، این ID به mapping اصلی اشاره می‌کند
            $table->foreignId('parent_mapping_id')->nullable()->after('mapping_type')
                  ->constrained('domain_mappings')->nullOnDelete();
            // ID رکورد DNS در ابرآروان (برای حذف آسان‌تر)
            $table->string('dns_record_id')->nullable()->after('parent_mapping_id');
            // آیا این دامنه، دامنه اصلی سرویس است؟
            $table->boolean('is_primary')->default(true)->after('dns_record_id');
        });
    }

    public function down(): void
    {
        Schema::table('domain_mappings', function (Blueprint $table) {
            $table->dropForeign(['parent_mapping_id']);
            $table->dropColumn(['mapping_type', 'parent_mapping_id', 'dns_record_id', 'is_primary']);
        });
    }
};
