<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('name');
            $table->string('type')->default('national'); // national, local, company
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->unique(['date']);
            $table->index(['date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
