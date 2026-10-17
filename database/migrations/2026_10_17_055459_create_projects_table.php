<?php

declare(strict_types=1);

use App\Models\Team;
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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Team::class)->constrained();
            $table->string('name');
            $table->string('identifier', 5);
            $table->string('color');
            $table->text('description')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedInteger('next_task_number')->default(1);
            $table->timestamps();

            $table->unique(['team_id', 'identifier']);
        });
    }
};
