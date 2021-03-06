<?php

use Illuminate\Database\Seeder;

class PssCodigosMujer extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		\DB::statement("INSERT INTO pss.codigos_mujer(codigo_prestacion,id_linea_cuidado,id_grupo_etario,embarazo_riesgo,embarazo_normal)
	(
		SELECT *
		FROM dblink('dbname=sirge host=192.6.0.118 user=postgres password=PN2012\$',
		    'SELECT codigo_prestacion,id_linea_cuidado,id_grupo_etario,embarazo_riesgo,embarazo_normal
			    FROM pss.codigos_mujer')
		    AS migracion(codigo_prestacion character varying(11),
				  id_linea_cuidado smallint,
				  id_grupo_etario smallint,
				  embarazo_riesgo character varying(1),
				  embarazo_normal character varying(1))
	);");
	}
}
