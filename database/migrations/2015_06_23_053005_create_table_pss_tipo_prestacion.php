<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTablePssTipoPrestacion extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('pss.tipo_prestacion', function (Blueprint $table) {
			$table->string('tipo_prestacion', 2)->primary();
			$table->string('descripcion',50)->nullable();
			$table->string('icono',40)->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('pss.tipo_prestacion');
	}
}
