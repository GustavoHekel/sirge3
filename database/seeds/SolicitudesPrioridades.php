<?php

use Illuminate\Database\Seeder;

class SolicitudesPrioridades extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::statement(" INSERT INTO solicitudes.prioridades(id, descripcion, css)
	(
		SELECT *
		FROM dblink('dbname=sirge3 host=192.6.0.118 user=postgres password=LatinoSandwich007',
		    'SELECT id, descripcion, css
			    FROM solicitudes.prioridades')
		    AS migracion( id integer,
						  descripcion character varying(100),
						  css character varying(50) )			
	); ");
    }
}
