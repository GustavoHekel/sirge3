<?php

use Illuminate\Database\Seeder;

class EfectoresDatosGeograficos extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		\DB::statement(" INSERT INTO efectores.datos_geograficos(id_efector,id_provincia,id_departamento,id_localidad,created_at,updated_at,ciudad,latitud,longitud)
(
	SELECT *
	FROM dblink('dbname=sirge3 host=192.6.0.118 user=postgres password=LatinoSandwich007',
	    'SELECT id_efector, id_provincia, id_departamento, id_localidad, now() as created_at, now() as updated_at, ciudad, latitud, longitud
		    FROM efectores.datos_geograficos')
	    AS migracion(id_efector integer,
  id_provincia character(2),
  id_departamento integer,
  id_localidad integer,
  created_at timestamp(0) without time zone,
  updated_at timestamp(0) without time zone,
  ciudad character varying(200),
  latitud numeric,
  longitud numeric)
);");
	}
}
