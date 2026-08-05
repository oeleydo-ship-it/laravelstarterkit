<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('email_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('email');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('status', 32)->default('subscribed');
            $table->uuid('unsubscribe_token')->unique();
            $table->json('meta')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('email_list_subscriber', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_list_id')->constrained('email_lists')->cascadeOnDelete();
            $table->foreignId('email_subscriber_id')->constrained('email_subscribers')->cascadeOnDelete();
            $table->string('status', 32)->default('subscribed');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['email_list_id', 'email_subscriber_id'], 'email_list_subscriber_unique');
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('email_list_id')->nullable()->constrained('email_lists')->nullOnDelete();
            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('preview_text')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();
            $table->longText('html_body');
            $table->longText('text_body')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('email_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('email_campaign_id')->constrained('email_campaigns')->cascadeOnDelete();
            $table->foreignId('email_subscriber_id')->nullable()->constrained('email_subscribers')->nullOnDelete();
            $table->string('email');
            $table->uuid('tracking_token')->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['email_campaign_id', 'status']);
            $table->index('tenant_id');
        });

        Schema::create('email_campaign_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_campaign_recipient_id')->constrained('email_campaign_recipients')->cascadeOnDelete();
            $table->string('url', 2048);
            $table->timestamp('clicked_at');
            $table->timestamps();

            $table->index('email_campaign_recipient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_clicks');
        Schema::dropIfExists('email_campaign_recipients');
        Schema::dropIfExists('email_campaigns');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('email_list_subscriber');
        Schema::dropIfExists('email_subscribers');
        Schema::dropIfExists('email_lists');
    }
};
