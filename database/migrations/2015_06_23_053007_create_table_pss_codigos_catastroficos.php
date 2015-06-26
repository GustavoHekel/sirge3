<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTablePssCodigosCatastroficos extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pss.codigos_catastroficos', function(Blueprint $table)
		{
			$table->string('codigo_prestacion', 11);
			$table->smallInteger('id_linea_cuidado');
			$table->smallInteger('id_grupo_etario');
			$table->primary(['codigo_prestacion', 'id_linea_cuidado', 'id_grupo_etario']);
			$table->foreign('codigo_prestacion')
			->references('codigo_prestacion')
			->on('pss.codigos')
			->onUpdate('cascade')
			->onDelete('NO ACTION');
			$table->foreign('id_linea_cuidado')
			->references('id_linea_cuidado')
			->on('pss.lineas_cuidado')
			->onUpdate('NO ACTION')
			->onDelete('NO ACTION');
			$table->foreign('id_grupo_etario')
			->references('id_grupo_etario')
			->on('pss.grupos_etarios')
			->onUpdate('NO ACTION')
			->onDelete('NO ACTION');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schame::drop('pss.codigos_catastroficos');
	}
}
