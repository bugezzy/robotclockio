<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The RobotClock database this app owns, not this application's own
     * default connection.
     */
    protected $connection = 'supabase';

    /**
     * Run the migrations.
     *
     * This is the one "users" table — see App\Models\User. Columns up to
     * `organization_id` are RobotClock's own; the rest (email_verified_at
     * onward) are this app's additive Fortify columns.
     */
    public function up(): void
    {
        if (app()->environment('testing') || Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('email')->nullable()->unique();
            $table->string('password_hash')->nullable();
            $table->foreignUuid('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('role')->default('member');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->restrictOnDelete();

            $table->timestamp('email_verified_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();

            $table->index('team_id', 'users_team_id_idx');
            $table->index('organization_id', 'users_organization_id_idx');
        });

        DB::statement(<<<'SQL'
            alter table users
                add constraint users_full_name_check check (length(trim(full_name)) > 0)
            SQL);

        DB::statement(<<<'SQL'
            alter table users
                add constraint users_email_check check (email = lower(email))
            SQL);

        DB::statement(<<<'SQL'
            alter table users
                add constraint users_role_check check (role in ('owner', 'admin', 'member'))
            SQL);

        DB::statement(<<<'SQL'
            alter table users
                add constraint users_signin_has_email check (role = 'member' or email is not null)
            SQL);

        DB::statement(<<<'SQL'
            alter table users
                add constraint users_org_unless_owner
                check (role = 'owner' or organization_id is not null)
            SQL);

        DB::statement(<<<'SQL'
            comment on column users.password_hash is
                'bcrypt: extensions.crypt(pw, extensions.gen_salt(''bf'')). Null for card-only staff who never log in.'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('users');
    }
};
