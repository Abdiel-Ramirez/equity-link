<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('folio', 50)->nullable();
        $table->dateTime('fecha')->nullable()->after('folio');
        $table->string('emisor', 13);
        $table->string('receptor', 13);
        $table->string('moneda', 3);
        $table->decimal('total', 15, 2);
        $table->decimal('tipo_cambio', 10, 4)->nullable();
        $table->text('xml_path')->nullable();
        $table->timestamps();

        $table->index('folio');
        });

    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
};
