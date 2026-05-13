<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('uploaded_pdf_candidate')) {
            return;
        }

        Schema::create('uploaded_pdf_candidate', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('can_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('file_name');
            $table->unsignedTinyInteger('file_for')->default(1)->comment('1=Economy, 2=Criminal, 3=Social');
            $table->unsignedTinyInteger('is_trash')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploaded_pdf_candidate');
    }
};
