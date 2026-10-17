<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Project::class)->constrained();
            $table->unsignedInteger('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status');
            $table->string('priority');
            $table->foreignIdFor(User::class, 'assignee_id')->nullable()->constrained();
            // Nullable so deleting an account keeps the tasks it authored for the rest of the team.
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained();
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'number']);
        });
    }
};
