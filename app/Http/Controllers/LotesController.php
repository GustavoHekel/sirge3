<?php

namespace App\Http\Controllers;

use Auth;
use Datatables;
use DB;
use PDF;
use Mail;
use Storage;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Subida;
use App\Models\Lote;
use App\Models\LoteAceptado;
use App\Models\LoteRechazado;
use App\Models\Rechazo;
use App\Models\GenerarRechazoLote;
use App\Models\DDJJ\Sirge as DDJJSirge;
use App\Models\Geo\Provincia;
use App\Models\Prestacion;
use App\Models\Comprobante;
use App\Models\Fondo;
use App\Models\PUCO\Super;
use App\Models\PUCO\Profe;
use App\Models\PUCO\Osp;

class LotesController extends Controller
{
	/**
	 * Create a new authentication controller instance.
	 *
	 * @return void
 	 */
	public function __construct(){
		$this->middleware('auth');
	}


	/**
	 * Devuelve el listado de lotes
	 *
	 * @return null
	 */
	public function listadoLotes($id){
		$data = [
			'page_title' => 'Administración de Lotes',
			'id_padron' => $id,
			'priority' => Auth::user()->id_entidad
		];
		return view('padrones.admin-lotes' , $data);
	}

	/**
	 * Devuelve el json para armar la datatable
	 *
	 * @return json
	 */
	public function listadoLotesTabla($id){
		$lotes = Lote::with('estado')
			->join('sistema.subidas' , 'sistema.lotes.id_subida' , '=' , 'sistema.subidas.id_subida')
			->where('id_padron' , $id)
			->select('sistema.lotes.*');

		if (Auth::user()->id_entidad == 2) {
			$lotes = $lotes->where('id_provincia' , Auth::user()->id_provincia);
		}
		
		$lotes = $lotes->get();

		return Datatables::of($lotes)
			->addColumn('estado_css' , function($lote){
				return '<span class="label label-' . $lote->estado->css . '">' . $lote->estado->descripcion . '</span>';
			})
			->addColumn('action' , function($lote){
				return '<button lote="'. $lote->lote .'" class="view-lote btn btn-info btn-xs"><i class="fa fa-pencil-square-o"></i> Ver lote</button>';
			})
			->make(true);
	}

	/**
	 * Devuelve el detalle del lote seleccionado
	 * @param int $lote
	 *
	 * @return null
	 */
	public function detalleLote($lote){
		$nro_lote = $lote;
		$lote = Lote::with(['estado' , 'archivo' , 'usuario' , 'provincia'])->findOrFail($nro_lote);
		$rechazo = GenerarRechazoLote::where('lote', $nro_lote)->where('estado',2)->first();
		$rechazo ? $descarga_disponible = TRUE : $descarga_disponible = FALSE;
		
		$minutos_faltantes = 0;
		
		if(!isset($rechazo)){
			
			if($lote->registros_out > 50000){
				$coef_rendimiento = 45;
			}
			else{
				$coef_rendimiento = 80;	
			}

			$minutos_de_procesamiento = round((float) ($lote->registros_out / $coef_rendimiento) / 60);
			$minutos_faltantes = 60 - date("i") + ( $minutos_de_procesamiento == 0 ? 1 : $minutos_de_procesamiento );			
		}				
		
		$data = [
			'page_title' => 'Detalle lote ' . $lote->lote,
			'descarga_disponible' => $descarga_disponible,
			'minutos_faltantes' => intval($minutos_faltantes),
			'lote' => $lote
		];

		return view ('padrones.detail-lote' , $data);
	}

	/**
	 * Devuelve la ruta donde guardar el archivo
	 * @param int $id
	 *
	 * @return string
	 */
	protected function getName($id , $route = FALSE){
		switch ($id) {
			case 1:
				$p = 'prestaciones'; break;
			case 2 :
				$p = 'fondos'; break;
			case 3 :
				$p = 'comprobantes'; break;
			case 4 : 
				$p = 'osp'; break;
			case 5 :
				$p = 'profe'; break;
			case 6 :
				$p = 'sss'; break;
			default:
				break;
		}
		if ($route)
			return '../storage/uploads/' . $p;
		else
			return $p;
	}

	/** 
	 * Sube el archivo al FTP
	 * @param int $lote
	 *
	 * @return bool
	 */
	protected function uploadFTP($lote) {
		/*$l = Lote::with('archivo')->findOrFail($lote);
		$ruta = $this->getName($l->archivo->id_padron , TRUE);
		$fh = fopen($ruta . '/' . $l->archivo->nombre_actual , 'r');
		if (Storage::put('sirg3/' . $this->getName($l->archivo->id_padron) . '/' . $l->archivo->nombre_actual , $fh)){
			unlink('../storage/uploads/' . $this->getName($l->archivo->id_padron) . '/' . $l->archivo->nombre_actual);
		}*/
	}

	/**
	 * Marco el lote como aceptado
	 * @param Request $r
	 *
	 * @return string
	 */
	public function aceptarLote(Request $r){
		$l = Lote::with('archivo')->findOrFail($r->lote);
		$l->id_estado = 3;
		if ($l->save()){
			$la = new LoteAceptado;
			$la->lote = $l->lote;
			$la->id_usuario = Auth::user()->id_usuario;
			$la->fecha_aceptado = 'now';
			if ($la->save()){
				$this->uploadFTP($r->lote);
				
				return 'Se ha aceptado el lote. Recuerde declararlo para imprimir la DDJJ.';
			}
		}
	}

	/**
	 * Elimina los registros de las tablas
	 * @param int $id
	 *
	 * @return null
	 */
	protected function eliminarRegistros($id , $lote){
		switch ($id){
			case 1 :
				Prestacion::where('lote' , $lote)->delete();
				break;
			case 2 : 
				Fondo::where('lote' , $lote)->delete();
				break;
			case 3 : 
				Comprobante::where('lote' , $lote)->delete();
				break;
			case 4 :
				Osp::where('lote' , $lote)->delete();
				break;
			case 5 :
				Profe::where('lote' , $lote)->delete();
				break;
			case 6 :
				Super::where('lote' , $lote)->delete();
				break;
			default : return null;
		}
	}

	/**
	 * Elimina los registros rechazados del lote
	 * @param int $id
	 *
	 * @return null
	 */
	protected function eliminarRegistrosRechazados($lote){
		Rechazo::where('lote' , $lote)->delete();
	}

	/**
	 * Marco el lote como rechazado
	 * @param Request $r
	 *
	 * @return string
	 */
	public function eliminarLote(Request $r){
		$l = Lote::findOrFail($r->lote);
		$l->id_estado = 4;

		try {
			$l->save();
			return 'Se ha rechazado el lote.';
		} catch (Exception $e) {
			return response('Error: ' . $e->getMessage(), $e->getCode());
			
		}
		/*$s = Subida::findOrFail($l->id_subida);
		$this->eliminarRegistros($s->id_padron , $r->lote);
		$this->eliminarRegistrosRechazados($r->lote);
		
		if ($l->save()){
			$lr = new LoteRechazado;
			$lr->lote = $l->lote;
			$lr->id_usuario = Auth::user()->id_usuario;
			$lr->fecha_rechazado = 'now';
			if ($lr->save()){
				return 'Se ha rechazado el lote.';
			}
		}*/
	}

	/**
	 * Devuelve la vista de rechazos
	 * @param int $lote
	 *
	 * @return null
	 */
	public function getRechazos($lote){

		$lote = Lote::findOrFail($lote);

		$data = [
			'page_title' => 'Listado de rechazos lote : ' . $lote->lote,
			'lote' => $lote
		];
		return view('padrones.rechazos' , $data);
	}

	/**
	 * Devuelve el json para armar la datatable
	 * @param int $lote
	 *
	 * @return json
	 */
	public function getRechazosTabla($lote){
		$rechazos = Rechazo::where('lote' , $lote)->get();
		return Datatables::of($rechazos)
			->make(true);
	}

	/**
	 * Devuelve el nombre del padron correspondiente
	 * @param int $id
	 *
	 * @return string
	 */
	protected function getNombrePadron($id){
		switch ($id) {
			case 1:	return 'PRESTACIONES';
			case 2: return 'APLICACIÓN DE FONDOS';
			case 3: return 'COMPROBANTES';
			case 4: return 'OBRA SOCIAL PROVINCIAL';
			case 5: return 'PROGRAMA FEDERAL DE SALUD';
			case 6: return 'SUPERINTENDENCIA DE SERVICIOS DE SALUD';
			default: break;
		}
	}

	/**
	 * Marca los lotes como impresos
	 * @param Request $r
	 *
	 * @return null
	 */
	public function declararLotes(Request $r){
		$lotes_d = [];
		$resumen = [
			'in' => 0,
			'out' => 0,
			'mod' => 0
		];

		$user = Auth::user();
		$lotes = DB::table('sistema.lotes as l')
			->join('sistema.subidas as s' , 'l.id_subida' , '=' , 's.id_subida')
			->join('sistema.lotes_aceptados as a' , 'l.lote' , '=' , 'a.lote')
			->where('s.id_padron' , $r->padron)
			->whereNotIn('l.lote' , function($q){
					$q->select(DB::raw('unnest(lote)'))
					->from('ddjj.sirge');
				})
			->where('l.id_provincia' , $user->id_provincia)
			->get();

		foreach ($lotes as $key => $lote){
			$lotes_d[$key] = $lote->lote;
			$resumen['in'] += $lote->registros_in;
			$resumen['out'] += $lote->registros_out;
			$resumen['mod'] += $lote->registros_mod;
		}
		$param = '{' . implode (',' , $lotes_d) . '}';
		
		DB::insert("insert into ddjj.sirge (lote) values (?)" , [ $param ]);
		$id = DB::getPdo()->lastInsertId('ddjj.sirge_id_impresion_seq');

		$data = [
			'lotes' => $lotes,
			'nombre_padron' => $this->getNombrePadron($r->padron),
			'resumen' => $resumen,
			'jurisdiccion' => Provincia::where('id_provincia' , $user->id_provincia)->firstOrFail(),
			'ddjj' => DDJJSirge::findOrFail($id)
		];

		if ($id){
			$path = base_path() . '/storage/pdf/ddjj/sirge/' . $id . '.pdf';
			$pdf = PDF::loadView('pdf.ddjj.sirge' , $data);
        	$pdf->save($path);
        	Mail::send('emails.ddjj-sirge', ['id' => $id , 'usuario' => $user], function ($m) use ($user , $path , $id) {
	            $m->from('sirgeweb@sumar.com.ar', 'Programa SUMAR');
	            $m->to($user->email);
	            $m->subject('DDJJ Nº ' . $id);
	            $m->attach($path);
	        });
		}

		return 'Se ha enviado la DDJJ a su casilla de correo electónico. (' . Auth::user()->email . ')';
	}
}
