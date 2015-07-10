<?php

namespace App\Classes;

use Illuminate\Database\Eloquent\Model;

class Entidad extends Model {
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'sistema.provincias';

	/**
	 * Primary key asociated with the table.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id_entidad';

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * Obtener los usuarios asociados a la entidad.
	 */
	public function usuarios() {
		return $this->belongsTo('App\Classes\Usuario', 'id_entidad', 'id_entidad');
	}
}
