<?php

/**
 * @file classes/migration/install/SchemaMigration.inc.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SchemaMigration
 * @brief Describe database table structures.
 */

namespace APP\plugins\generic\funding\classes\migration\install;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PKP\install\DowngradeNotSupportedException;

class SchemaMigration extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up() {

        # funders
        if (!Schema::hasTable('funders')) {
            Schema::create('funders', function (Blueprint $table) {
                $table->bigInteger('funder_id')->autoIncrement();
                $table->string('funder_identification', 255);
                $table->bigInteger('submission_id');
                $table->bigInteger('context_id');
            });
        }

        // funder_settings
        if (!Schema::hasTable('funder_settings')) {
            Schema::create('funder_settings', function (Blueprint $table) {
                $table->bigIncrements('funder_setting_id');
                $table->bigInteger('funder_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();
                $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
                $table->index(['funder_id'], 'funder_settings_id');
                $table->unique(['funder_id', 'locale', 'setting_name'], 'funder_settings_f_l_s_pkey');
            });
        }

        # funder_awards
        if (!Schema::hasTable('funder_awards')) {
            Schema::create('funder_awards', function (Blueprint $table) {
                $table->bigInteger('funder_award_id')->autoIncrement();
                $table->bigInteger('funder_id');
                $table->string('funder_award_number', 255);
            });
        }

        // funder_award_settings
        if (!Schema::hasTable('funder_award_settings')) {
            Schema::create('funder_award_settings', function (Blueprint $table) {
                $table->bigIncrements('funding_award_setting_id');
                $table->bigInteger('funder_award_id');
                $table->string('locale', 14)->default('');
                $table->string('setting_name', 255);
                $table->longText('setting_value')->nullable();
                $table->string('setting_type', 6)->comment('(bool|int|float|string|object)');
                $table->index(['funder_award_id'], 'funder_award_settings_id');
                $table->unique(['funder_award_id', 'locale', 'setting_name'], 'funder_award_settings_f_l_s_pkey');
            });
        }

    }

    /**
     * Rollback the migrations.
     */
    public function down(): void
    {
        throw new DowngradeNotSupportedException();
    }
}
