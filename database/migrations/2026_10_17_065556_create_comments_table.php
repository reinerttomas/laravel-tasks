<?php

declare(strict_types=1);

use App\Models\Task;
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
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Task::class)->constrained();
            // Nullable so deleting an account keeps the comments it wrote for the rest of the team.
            $table->foreignIdFor(User::class, 'author_id')->nullable()->constrained();
            $table->text('body');
            $table->timestamps();
        });
    }
};
