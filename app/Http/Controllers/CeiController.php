<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Mail;
use Auth;
use Datatables;
use Excel;
use App;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Prestacion;
use App\Models\Geo\Provincia;

use App\Models\CEI\Grupo;
use App\Models\CEI\Linea;
use App\Models\CEI\Resultado;
use App\Models\CEI\Indicador;
use App\Models\CEI\Detalle;
use App\Models\CEI\Tipo;
use App\Models\CEI\Calculo;
use App\Models\CEI\Denominador;
use App\Models\CEI\Oportuno;
use App\Models\Beneficiario;


class CeiController extends Controller
{
    /**
	 * Create a new authentication controller instance.
	 *
	 * @return void
	 */
	public function __construct(){
		$this->middleware('auth');
		setlocale(LC_TIME, 'es_ES.UTF-8');
	}

	/**
	 * Devuelve la vista para el Resumen mensual
	 * 
	 * @return null
	 */
	public function getFiltros(){

		$periodos = Resultado::select('periodo' , DB::raw('round((count(*) / 1728 :: numeric * 100) ,2) as r'))
							->groupBy('periodo')
							->orderBy('periodo','desc')
							->take(3)
							->get();									

		foreach ($periodos as $periodo){
			$dt = \DateTime::createFromFormat('Ym' , $periodo->periodo);
			$periodo->periodo = ucwords(strftime("%b %Y" , $dt->getTimeStamp()));

			$dt->modify('11 months');
			$periodo->periodo_fin = ucwords(strftime("%b %Y" , $dt->getTimeStamp()));

			if ($periodo->r < 100){
				$periodo->css = 'bg-yellow';
			} else {
				$periodo->css = 'bg-lime';
			}
		}

		$data = [
			'page_title' => 'Resumen C.E.I.',
			'provincias' => Provincia::orderBy('id_provincia')->get(),
			'grupos' => Grupo::all(),
			'periodos' => $periodos,

		];

		return view('cei.filtros' , $data);
	}

	/**
	 * Devuelve las lineas de cuidado asociadas a un grupo etario
	 * @param int $grupo
	 * 
	 * @return null
	 */
	public function getLineas($grupo){
		$lineas = Linea::where('grupo_etario' , $grupo)->get();
		return response()->json($lineas);
	}

	/**
	 * Devuelve la información para graficar los mapas
	 * @param int $periodo 
	 * @param int $linea
	 *
	 * @return array
	 */
	protected function getMapSeries($periodo , $linea){

		$indicadores = Indicador::where('indicador' , $linea)->orderBy('id' , 'asc')->lists('id');

		foreach ($indicadores as $key => $indicador) {

			$resultados = Resultado::join('geo.geojson as g' , 'cei.resultados.provincia' , '=' , 'g.id_provincia')
									->where('indicador' , $indicador)
									->where('periodo' , $periodo)
									->orderBy('provincia' , 'asc')
									->get();

			// return '<pre>' . json_encode($resultados , JSON_PRETTY_PRINT) . '</pre>';

			foreach ($resultados as $key_provincia => $resultado){

				if ($resultado->resultados->denominador == 0) {
					$map[$indicador]['map-data'][$key_provincia]['value'] = 0;
				} else {
					$map[$indicador]['map-data'][$key_provincia]['value'] = round($resultado->resultados->beneficiarios_puntuales / $resultado->resultados->denominador , 4) * 100;
				}
				
				$map[$indicador]['map-data'][$key_provincia]['hc-key'] = $resultado->geojson_provincia;
				$map[$indicador]['map-data'][$key_provincia]['indicador'] = $indicador;
				$map[$indicador]['map-data'][$key_provincia]['periodo'] = $periodo;
				$map[$indicador]['map-data'][$key_provincia]['provincia'] = $resultado->provincia;
			}

			$map[$indicador]['map-data'] = json_encode($map[$indicador]['map-data']);
			$map[$indicador]['clase'] = $key;
		}

		return $map;

	}

	/**
	 * Devuelve la información para armar los gráficos de barra
	 * @param int $periodo 
	 * @param int $linea
	 *
	 * @return array
	 */
	protected function getGraficoSeries($periodo , $linea){

		$indicadores = Indicador::where('indicador' , $linea)->orderBy('id' , 'asc')->lists('id');
		$data['categorias'] = Provincia::orderBy('id_provincia')->lists('descripcion');

		foreach ($indicadores as $key => $indicador) {

			$resultados = Resultado::join('geo.provincias as p' , 'cei.resultados.provincia' , '=' , 'p.id_provincia')
									->where('indicador' , $indicador)
									->where('periodo' , $periodo)
									->orderBy('provincia' , 'asc')
									->get();

			foreach ($resultados as $key_provincia => $resultado){

				if ($resultado->resultados->denominador == 0) {
					$data['info'][$indicador]['serie']['data'][] = 0;
				} else {
					$data['info'][$indicador]['serie']['data'][] = round($resultado->resultados->beneficiarios_puntuales / $resultado->resultados->denominador , 4) * 100;
					$data['info'][$indicador]['serie']['name'] = 'Resultados';
				}
			}

			$data['info'][$indicador]['serie'] = json_encode($data['info'][$indicador]['serie']);
			$data['info'][$indicador]['clase'] = $key;
		}

		return $data;

	}

	/**
	 * Devuelve la vista resumen
	 * @param int $periodo
	 * @param int $linea
	 *
	 * @return null
	 */
	public function getResumen($periodo , $linea){

		$dt = \DateTime::createFromFormat('Y-m' , $periodo);
		$periodo = $dt->format('Ym');
		
		// return $this->getGraficoSeries($periodo , $linea);

		$data = [
			'maps' => $this->getMapSeries($periodo , $linea),
			'graficos' => $this->getGraficoSeries($periodo , $linea)
		];

		return view('cei.resumen' , $data);
	}

	/**
	 * Devuelve un pequeño detalle del indicador para una provincia y un período
	 * @param int $periodo
	 * @param int $indicador
	 * @param char $provincia
	 *
	 * @return null
	 */
	public function getDetalleProvincia($periodo , $indicador , $provincia){
		$resultado = Resultado::where('provincia' , $provincia)
							->where('indicador' , $indicador)
							->where('periodo' , $periodo)
							->firstOrFail();
		$provincia = Provincia::find($provincia);

		foreach ($resultado->resultados->prestaciones->codigos as $codigo => $cantidad) {
			$data['serie'][0]['name'] = 'Fact. Prest.';
			$data['serie'][0]['data'][] = $cantidad;
			$data['categorias'][] = $codigo;
			$data['provincia'] = $provincia->descripcion;
		}

		$data = [
			'indicador' => $indicador,
			'serie' => $data,
			'beneficiarios_oportunos' => $resultado->resultados->beneficiarios_oportunos,
			'beneficiarios_puntuales' => $resultado->resultados->beneficiarios_puntuales,
			'denominador' => $resultado->resultados->denominador
		];

		return view('cei.detalle-provincia' , $data);

	}

	/**
	 * Devuelve la vista para descargar reportes
	 *
	 * @return null
	 */
	public function getReportes(){

		$data = [
			'page_title' => 'Resultados C.E.I.'
		];

		return view('cei.reportes' , $data);
	}

	/**
	 * Devuelve el objeto para el Datatable
	 *
	 * @return json
	 */
	public function getReportesTabla(){

		$detalles = Detalle::select(
				'id' 
				, 'nombre' 
				, DB::raw("case when (edad_max = '0 years') then edad_min else (edad_min :: interval - edad_max :: interval + '1 year') :: text	end as edad_min") 
				, DB::raw("edad_min :: interval + '1 year' as edad_max"));
		
		return Datatables::of($detalles)
			->addColumn('action' , function($detalle){
				return '<button id="'. $detalle->id .'" class="ver-indicador btn btn-info btn-xs"><i class="fa fa-pencil-square-o"></i></button>';
			})
			->addColumn('download' , function($detalle){
				return '<a href="cei-reportes-download/'. $detalle->id .'" class="download-indicador btn btn-success btn-xs"><i class="fa fa-cloud-download"></i></button>';	
			})
			->make(true);
	}

	/**
	 * Devuelve la vista con el detalle de los cálculos
	 * @param int $id
	 * 
	 * @return null
	 */
	public function getReporte($id){

		$periodos = Resultado::select('periodo')->groupBy('periodo')->lists('periodo');
		$tipos = Tipo::select('cei.indicadores_tipo.*')->join('cei.indicadores' , 'cei.indicadores_tipo.id' , '=' , 'cei.indicadores.tipo')->where('indicador' , $id)->orderBy('cei.indicadores_tipo.id')->get();
		$detalle = Detalle::findOrFail($id);

		foreach ($periodos as $keyp => $periodo){

			foreach ($tipos as $keyt => $tipo){

				$indicador = Indicador::where('indicador' , $id)->where('tipo' , $tipo->id)->firstOrFail();

				$registros = Resultado::where('indicador' , $indicador->id)
							->where('periodo' , $periodo)
							->get();

				if (! count($registros)){
					$promedio[] = 0;
				}

				foreach ($registros as $registro) {
					if ($registro->resultados->denominador == 0) {
						$promedio[] = 0;
					} else {
						$promedio[] = round(($registro->resultados->beneficiarios_puntuales / $registro->resultados->denominador) * 100,2);
					}
				}

				$array_final[$tipo->id]['resultados'][$keyp]['periodo'] = $periodo;
				$array_final[$tipo->id]['resultados'][$keyp]['valor'] = round(array_sum($promedio) / count($promedio),2);
				$array_final[$tipo->id]['tipo'] = $tipo->descripcion;
				$array_final[$tipo->id]['id_tipo'] = $tipo->id;
				$array_final[$tipo->id]['css'] = $tipo->css;
				$array_final[$tipo->id]['icon'] = $tipo->icon;
				$array_final[$tipo->id]['indicador'] = $indicador->id;
				
				unset ($promedio);
			}

		}

		// return json_encode($promedio);

		$objeto = json_decode(json_encode($array_final));

		$data = [
			'page_title' => $detalle->nombre,
			'objetos' => $objeto
		];

		return view ('cei.reporte-detalle' , $data);

	}

	/**
	 * Descarga toda la información asociada a una línea de cuidado
	 * @param int $id
	 * @param int $periodo
	 * @param int $tipo
	 *
	 * @return resource
	 */
	public function getLineaDownload($id , $periodo = null , $tipo = null){

		$indicadores = Indicador::select('cei.indicadores.*' , 'cei.indicadores_tipo.descripcion')
								->join('cei.indicadores_tipo' , 'cei.indicadores.tipo' , '=' , 'cei.indicadores_tipo.id')
								->where('indicador' , $id);

		if (isset($tipo)){
			$indicadores->where('tipo' , $tipo);
		}

	 	$indicadores = $indicadores->get();

		foreach ($indicadores as $indicador){

			$resultados = Resultado::where('indicador' , $indicador->id)
									->orderBy('periodo')
									->orderBy('provincia');
			if (isset ($periodo)){
				$resultados->where('periodo' , $periodo);
			}
			$resultados = $resultados->get();
			$resultados->tipo = $indicador->descripcion;
			$final[] = $resultados;
			$detalle = Detalle::where('id' , $indicador->indicador)->firstOrFail();
		}


		$data = [
			'resultados' => $final,
			'linea_cuidado' => $detalle->nombre
		];

		// return view('cei.excel-template' , $data);

		
		Excel::create('resultados-cei', function($excel) use ($data) {
			$excel->sheet('New sheet', function($sheet) use ($data) {
				$sheet->setHeight(1, 20);
				$sheet->setColumnFormat([
              		'A' => '@'
            	]);
				$sheet->loadView('cei.excel-template' , $data);
			});
		})->store('xls');

		return response()->download('../storage/exports/resultados-cei.xls');
	}

	/**
     * Método abstracto para el calculo de la mayoría
     * de los indicadores CEI categoria normal.
     *
     * @param $periodo integer
     * @param $id_indicador integer
     * @param $id_categoria
     * @return json
     */
    public function getIndicadorCei($periodo , $id_indicador){

    	$periodo_del = str_replace('-', '', $periodo);

    	$res = Resultado::where('indicador' , $id_indicador)->where('periodo' , $periodo_del)->delete();

    	$indicador = Indicador::join('cei.indicadores_detalle' , 'cei.indicadores.indicador' , '=' , 'cei.indicadores_detalle.id')
    						->where('cei.indicadores.id' , $id_indicador)
    						->firstOrFail();

    	$dt = \DateTime::createFromFormat('Y-m' , $periodo);
    	$dt->modify('first day of this month');
    	$dt->modify("- $indicador->edad_min");
    	$fechas['min'] = $dt->format('Y-m-d');

    	$dt->modify("+ $indicador->edad_max");
    	$dt->modify('last day of this month');
    	$fechas['max'] = $dt->format('Y-m-d');

    	$provincias = Provincia::orderBy('id_provincia')->get();
    	$calculo = Calculo::find($id_indicador);

    	foreach ($provincias as $provincia) {
    		ob_start();
			
			foreach ($calculo->numerador->prestaciones as $prestacion){
				$codigos = implode ('_' , $prestacion->codigos);
				$super_objeto['prestaciones']['codigos'][$codigos] = 0;
			}

			if (isset ($calculo->numerador->sexo)){
				$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
											->where('id_provincia_alta' , $provincia->id_provincia)
											->where('sexo' , $calculo->numerador->sexo)
											->lists('clave_beneficiario');
			} else {
				$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
											->where('id_provincia_alta' , $provincia->id_provincia)
											->lists('clave_beneficiario');
			}

	    	// $oportunos = Oportuno::where('linea_cuidado' , $indicador->indicador)->where('periodo' , $periodo_del)->where('provincia' , '01')->get();
	    	

	    	foreach ($beneficiarios as $key => $beneficiario){

	    		$super_objeto['beneficiarios_oportunos'] = count($beneficiarios);
	    		
	    		// foreach ($beneficiarios->beneficiarios as $beneficiario) {

		    		$cantidad_prestaciones = 0;
		    		if (isset ($calculo->denominador->prestaciones)) {
		    			foreach ($calculo->denominador->prestaciones as $prestacion){

		    				$dt = \DateTime::createFromFormat('Y-m' , $periodo);
			    			$dt->modify('first day of this month');
			    			$fechas['min_prestacion'] = $dt->format('Y-m-d');
			    			$dt->modify("+ $prestacion->lapso");
			    			$fechas['max_prestacion'] = $dt->format('Y-m-d');

			    			$existe = Prestacion::where('clave_beneficiario' , $beneficiario)
			    								->whereIn('codigo_prestacion' , $prestacion->codigos)
			    								->whereBetween('fecha_prestacion' , [$fechas['min_prestacion'] , $fechas['max_prestacion']])
			    								->count();

			    			if (! $existe) {
			    				unset($beneficiarios[$key]);
			    			}

		    			}

		    			$super_objeto['denominador'] = count($beneficiarios);
		    		} else if (isset ($calculo->denominador->id)){
		    			$denominador = Denominador::where('indicador' , $id_indicador)
		    									->where('id_provincia' , $provincia->id_provincia)
		    									->firstOrFail();
		    			$super_objeto['denominador'] = $denominador->denominador;
		    		}
	    		// }
    		}

			foreach ($beneficiarios as $key => $beneficiario) {

				foreach ($calculo->numerador->prestaciones as $prestacion){

					$dt = \DateTime::createFromFormat('Y-m' , $periodo);
					$dt->modify('first day of this month');
					$fechas['min_prestacion'] = $dt->format('Y-m-d');
					$dt->modify("+ $prestacion->lapso");
					$fechas['max_prestacion'] = $dt->format('Y-m-d');

				
					$existe = Prestacion::where('clave_beneficiario' , $beneficiario)
										->whereIn('codigo_prestacion' , $prestacion->codigos)
										->whereBetween('fecha_prestacion' , [$fechas['min_prestacion'] , $fechas['max_prestacion']])
										->count();
					
					$codigos = implode ('_' , $prestacion->codigos);
					
					if ($existe)
						$super_objeto['prestaciones']['codigos'][$codigos] ++;

					$cantidad_prestaciones += $existe;

					if (! isset($calculo->numerador->cantidad)) {
		    			if (! $existe) {
		    				unset($beneficiarios[$key]);
		    			}
					}

				}

				if (isset ($calculo->numerador->cantidad)){
					if ($cantidad_prestaciones < $calculo->numerador->cantidad) {
						unset($beneficiarios[$key]);
					}
				}
			}
	    	

    		$super_objeto['beneficiarios_puntuales'] = count($beneficiarios);


    		$r = new Resultado;
    		$r->indicador = $id_indicador;
    		$r->provincia = $provincia->id_provincia;
    		$r->periodo = str_replace('-', '', $periodo);
    		$r->resultados = json_encode($super_objeto);
    		$r->save();

    		echo '<pre>' , json_encode($super_objeto , JSON_PRETTY_PRINT) , '<pre>';
    	}
		ob_end_flush();
    }


    

    public function nuevoCalculo($linea_cuidado , $periodo){

    	/**
    	 *
    	 * Primero realizo el cálculo de beneficiarios potenciales
    	 *
    	 */

    	$periodo_del = str_replace('-', '', $periodo);

    	Oportuno::where('linea_cuidado' , $linea_cuidado)->where('periodo' , $periodo_del)->delete();

    	$detalle = Detalle::findOrFail($linea_cuidado);
    	$indicador = Indicador::where('indicador' , $linea_cuidado)->where('tipo' , 1)->orderBy('tipo' , 'asc')->firstOrFail();
    	$provincias = Provincia::orderBy('id_provincia' , 'asc')->get();
    	$calculo = Calculo::findOrFail($indicador->id);

    	$dt = \DateTime::createFromFormat('Y-m' , $periodo);
    	$dt->modify('first day of this month');
    	$dt->modify("- $detalle->edad_min");
    	$fechas['min'] = $dt->format('Y-m-d');

    	$dt->modify("+ $detalle->edad_max");
    	$dt->modify('last day of this month');
    	$fechas['max'] = $dt->format('Y-m-d');


    	foreach ($provincias as $provincia) {

	    	if (isset ($calculo->numerador->sexo)){
				$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
											->where('id_provincia_alta' , $provincia->id_provincia)
											->where('sexo' , $calculo->numerador->sexo)
											->lists('clave_beneficiario');
			} else {
				$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
											->where('id_provincia_alta' , $provincia->id_provincia)
											->lists('clave_beneficiario');
			}

			$beneficiarios_oportunos = count($beneficiarios);

			foreach ($beneficiarios as $key => $beneficiario) {

				$coincidence = FALSE;
				foreach ($calculo->numerador->prestaciones as $prestacion){

					$dt = \DateTime::createFromFormat('Y-m' , $periodo);
					$dt->modify('first day of this month');
					$fechas['min_prestacion'] = $dt->format('Y-m-d');
					$dt->modify("+ $prestacion->lapso");
					$fechas['max_prestacion'] = $dt->format('Y-m-d');
				
					$existe = Prestacion::where('clave_beneficiario' , $beneficiario)
										->whereIn('codigo_prestacion' , $prestacion->codigos)
										->whereBetween('fecha_prestacion' , [$fechas['min_prestacion'] , $fechas['max_prestacion']])
										->get();

					if($existe){
				    	$coincidence = TRUE;	
	    			}										
				}
				if ($coincidence) {
						$obj[] = $beneficiario;
				}
			}

			if (! isset ($obj)){
				$obj[] = NULL;
			}

			// return json_encode($obj);

			$op = new Oportuno;
			$op->provincia = $provincia->id_provincia;
			$op->linea_cuidado = $linea_cuidado;
			$op->periodo = $periodo_del;
			$op->beneficiarios_oportunos = $beneficiarios_oportunos;
			$op->beneficiarios = json_encode($obj);
			$op->save();

			unset($obj);
    	}
		
    } 

    
    /**
     * Método abstracto para el calculo de la mayoría
     * de los indicadores CEI categoria normal.
     *
     * @param $periodo integer
     * @param $id_indicador integer
     * @param $id_categoria
     * @return json
     */
    public function getIndicadorCeiNew($periodo , $id_indicador){

    	$periodo_del = str_replace('-', '', $periodo);

    	$res = Resultado::where('indicador' , $id_indicador)->where('periodo' , $periodo_del)->delete();

    	$indicador = Indicador::join('cei.indicadores_detalle' , 'cei.indicadores.indicador' , '=' , 'cei.indicadores_detalle.id')
    						->where('cei.indicadores.id' , $id_indicador)
    						->firstOrFail();

    	$dt = \DateTime::createFromFormat('Y-m' , $periodo);
    	$dt->modify('first day of this month');
    	$dt->modify("- $indicador->edad_min");
    	$fechas['min'] = $dt->format('Y-m-d');

    	$dt->modify("+ $indicador->edad_max");
    	$dt->modify('last day of this month');
    	$fechas['max'] = $dt->format('Y-m-d');

    	$provincias = Provincia::orderBy('id_provincia')->get();
    	$calculo = Calculo::find($id_indicador);

    	foreach ($provincias as $provincia) {
			
			foreach ($calculo->numerador->prestaciones as $prestacion){
				$codigos = implode ('_' , $prestacion->codigos);
				$super_objeto['prestaciones']['codigos'][$codigos] = 0;
			}

			// if (isset ($calculo->numerador->sexo)){
			// 	$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
			// 								->where('id_provincia_alta' , $provincia->id_provincia)
			// 								->where('sexo' , $calculo->numerador->sexo)
			// 								->lists('clave_beneficiario');
			// } else {
			// 	$beneficiarios = Beneficiario::whereBetween('fecha_nacimiento' , [$fechas['min'],$fechas['max']])	
			// 								->where('id_provincia_alta' , $provincia->id_provincia)
			// 								->lists('clave_beneficiario');
			// }

	    	$oportunos = Oportuno::where('linea_cuidado' , $indicador->indicador)->where('periodo' , $periodo_del)->where('provincia' , $provincia->id_provincia)->get();

	    	foreach ($oportunos as $oportuno){

	    		// return json_encode($oportuno);
	    		$super_objeto['beneficiarios_oportunos'] = $oportuno->beneficiarios_oportunos;

	    		$oportuno = $oportuno->beneficiarios;

	    		if (isset ($calculo->denominador->id)){
	    			$denominador = Denominador::where('indicador' , $id_indicador)
	    									->where('id_provincia' , $provincia->id_provincia)
	    									->firstOrFail();
	    			$super_objeto['denominador'] = $denominador->denominador;
	    		}
	    		elseif(Indicador::find($id_indicador)->tipo == 3 && Resultado::where('indicador',$id_indicador-1)
																	->where('provincia',$provincia->id_provincia)
																	->where('periodo',str_replace('-', '', $periodo))
																	->first() )
	    		{
	    			$super_objeto['denominador'] = Resultado::where('indicador',$id_indicador-1)
															->where('provincia',$provincia->id_provincia)
															->where('periodo',str_replace('-', '', $periodo))
															->first()
															->resultados
															->denominador;
	    		}
	    		else{
	    		
		    		foreach ($oportuno as $key => $beneficiario) {
			    		
			    		if (isset ($calculo->denominador->prestaciones)) {
			    			$coincidence = FALSE;
			    			foreach ($calculo->denominador->prestaciones as $prestacion){

			    				$dt = \DateTime::createFromFormat('Y-m' , $periodo);
				    			$dt->modify('first day of this month');
				    			$fechas['min_prestacion'] = $dt->format('Y-m-d');
				    			$dt->modify("+ $prestacion->lapso");
				    			$fechas['max_prestacion'] = $dt->format('Y-m-d');

				    			$existe = Prestacion::where('clave_beneficiario' , $beneficiario)
				    								->whereIn('codigo_prestacion' , $prestacion->codigos)
				    								->whereBetween('fecha_prestacion' , [$fechas['min_prestacion'] , $fechas['max_prestacion']])
				    								->count();
				    			if($existe){
				    				$coincidence = TRUE;	
				    			}			    			
			    			}

			    			if (! $coincidence) {
				    				unset($oportuno[$key]);
			    			}

			    			$super_objeto['denominador'] = count($oportuno);
			    		} 
		    		}
		    	}

    		}

			foreach ($oportunos as $oportuno) {

				$oportuno = $oportuno->beneficiarios;

				foreach ($oportuno as $key => $beneficiario) {

					$cantidad_prestaciones = 0;
					$coincidence = FALSE;
					foreach ($calculo->numerador->prestaciones as $prestacion){

						// return json_encode($calculo);

						$dt = \DateTime::createFromFormat('Y-m' , $periodo);
						$dt->modify('first day of this month');
						$fechas['min_prestacion'] = $dt->format('Y-m-d');
						$dt->modify("+ $prestacion->lapso");
						$fechas['max_prestacion'] = $dt->format('Y-m-d');

					
						$existe = Prestacion::where('clave_beneficiario' , $beneficiario)
											->whereIn('codigo_prestacion' , $prestacion->codigos)
											->whereBetween('fecha_prestacion' , [$fechas['min_prestacion'] , $fechas['max_prestacion']])
											->count();
						
						$codigos = implode ('_' , $prestacion->codigos);
												
						if($existe){
			    				$super_objeto['prestaciones']['codigos'][$codigos] ++;
			    				$coincidence = TRUE;	
			    		}

						$cantidad_prestaciones += $existe;						
					}

					if (isset ($calculo->numerador->cantidad) && $coincidence){
						if ($cantidad_prestaciones < $calculo->numerador->cantidad) {
								unset($oportuno[$key]);
						}
					}
					elseif (!$coincidence) {
						unset($oportuno[$key]);	
					}
				}


   				$super_objeto['beneficiarios_puntuales'] = count($oportuno);

			}

    		$r = new Resultado;
    		$r->indicador = $id_indicador;
    		$r->provincia = $provincia->id_provincia;
    		$r->periodo = str_replace('-', '', $periodo);
    		$r->resultados = json_encode($super_objeto);
    		$r->save();

    		echo '<pre>' , json_encode($super_objeto , JSON_PRETTY_PRINT) , '<pre>';
    	}
    }

   /**
   * Corre todas las lineas de cuidado en el periodo
   * @param character(7) $periodo
   *
   * @return string
   */
  public function nuevoCalculoCompleto($periodo){
    
  	for ($i= 1; $i < 26; $i++) {
  		$this->nuevoCalculo($i,$periodo);
  	}        

    return response()->json("Las lineas de cuidado del CEI para el periodo $periodo fueron procesadas correctamente.");   
  }

   /**
   * Corre todos los indicadores en el periodo
   * @param character(7) $periodo
   *
   * @return string
   */
  public function indicadoresCompleto($periodo){
    
  	for ($i= 3; $i < 73; $i++) {
  		$this->getIndicadorCeiNew($periodo,$i);
  	}        

    return response()->json("Los indicadores fueron del CEI para el periodo $periodo fueron procesados correctamente.");   
  }

}
