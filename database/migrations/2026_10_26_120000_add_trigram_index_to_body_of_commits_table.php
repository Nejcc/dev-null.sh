<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a repository's page find the commits linking to it without reading every commit.
     */
    public function up(): void
    {
        Schema::ensureExtensionExists('pg_trgm');

        Schema::table('commits', function (Blueprint $table): void {
            $table->rawIndex('body gin_trgm_ops', 'commits_body_trigram_index')->algorithm('gin');
        });
    }
};
