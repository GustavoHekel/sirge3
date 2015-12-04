<?php

namespace App\Http\Controllers;

use Auth;
use Datatables;
use DB;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Salud;
use App\Models\PSS\Grupo;
use App\Models\PSS\CEB;

use App\Models\Dw\Fc002;

class PssController extends Controller
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
	 * Devuelve la vista principal del listado PSS
	 *
	 * @return null
	 */
	public function getListado(){
		$data = [
			'page_title' => 'Plan de Servicios de Salud'
		];
		return view('pss.index' , $data);
	}

	/**
	 * Devuelve el JSON para la datatable
	 *
	 * @return json
	 */
	public function getListadoTabla(){
		$pss = Salud::where('codigo_prestacion' , 'CTC001A97')->get();
		// $pss = Salud::all();
		return Datatables::of($pss)
			->editColumn('descripcion_grupal' , '{!! str_limit($descripcion_grupal, 60) !!}')
			->addColumn('action' , function($ps){
				return '<button codigo="'. $ps->codigo_prestacion .'" class="ver btn btn-info btn-xs"><i class="fa fa-pencil-square-o"></i> Ver</button>';
			})
			->make(true);
	}

	/**
     * Aclara el color base
     * @param int
     *
     * @return string
     */
    protected function alter_brightness($colourstr, $steps) {
        $colourstr = str_replace('#','',$colourstr);
        $rhex = substr($colourstr,0,2);
        $ghex = substr($colourstr,2,2);
        $bhex = substr($colourstr,4,2);

        $r = hexdec($rhex);
        $g = hexdec($ghex);
        $b = hexdec($bhex);

        $r = max(0,min(255,$r + $steps));
        $g = max(0,min(255,$g + $steps));  
        $b = max(0,min(255,$b + $steps));

        return '#'.str_pad(dechex($r) , 2 , '0' , STR_PAD_LEFT).str_pad(dechex($g) , 2 , '0' , STR_PAD_LEFT).str_pad(dechex($b) , 2 , '0' , STR_PAD_LEFT);
    }

	/**
     * Devuelve listado de 12 meses 
     *
     * @return json
     */
    protected function getMesesArray(){

        $dt = new \DateTime();
        $dt->modify('-13 months');
        for ($i = 0 ; $i < 12 ; $i ++){
       		$dt->modify('+1 month');
            $meses[$i] = strftime("%b %Y" , $dt->getTimeStamp());
        }
        return json_encode($meses);
    }

    /**
     * Devuelve el rango de periodos a filtrar
     *
     * @return array
     */
    protected function getDateInterval(){

        $dt = new \DateTime();
        $dt->modify('-1 month');
        $interval['max'] = $dt->format('Ym');
        $dt->modify('-11 months');
        $interval['min'] = $dt->format('Ym');

        return $interval;
    }

	/**
	 * Devuelve la serie para graficar la facturación
	 * @param string $id
	 *
	 * @return json
	 */
	protected function getProgreso($id){
		$interval = $this->getDateInterval();

		$facturacion = Fc002::select('periodo' , DB::raw('sum(cantidad) as c') , DB::raw('sum(monto) as f'))
							->where('codigo_prestacion' , $id)
							->whereBetween('periodo',[$interval['min'],$interval['max']])
							->groupBy('periodo' , 'codigo_prestacion')
							->get();
		foreach ($facturacion as $key => $info){
			$data[0]['name'] = 'Cant. Fact.';
			$data[0]['data'][$key] = $info->c;
			/*
			$data[1]['name'] = 'Monto. Fact.';
			$data[1]['data'][$key] = (int)($info->f/1000);
			*/
		}

		return json_encode($data);
	}

	/**
	 * Devuelve la serie para el gráfico de distribución
	 * @param string $id
	 *
	 * @return json
	 */
	protected function getDistribucion($id){
		$meses = $this->getMesesArray();
		$interval = $this->getDateInterval();
		$i = 0;

		$regiones = Fc002::whereBetween('periodo',[$interval['min'],$interval['max']])
							->where('codigo_prestacion' , $id)
							->join('geo.provincias as p' , 'estadisticas.fc_002.id_provincia' , '=' , 'p.id_provincia')
                    		->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                    		->select('r.id_region' , 'r.nombre' , DB::raw('sum(cantidad) as c'))
                    		->groupBy('r.id_region')
                    		->groupBy('r.nombre')
                    		->get();

        foreach ($regiones as $key => $region){
            $data[$i]['color'] = $this->alter_brightness('#0F467F' , $key * 35);
            $data[$i]['id'] = (string)$region->id_region;
            $data[$i]['name'] = $region->nombre;
            $data[$i]['value'] = (int)$region->c;
            $i++;
        }

        for ($j = 0 ; $j <= 5 ; $j ++){
            $provincias = Fc002::whereBetween('periodo',[$interval['min'],$interval['max']])
                            ->where('r.id_region' , $j)
                            ->where('codigo_prestacion' , $id)
                            ->join('geo.provincias as p' , 'estadisticas.fc_002.id_provincia' , '=' , 'p.id_provincia')
                            ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                            ->select('r.id_region' , 'p.id_provincia' , 'p.nombre' , DB::raw('sum(cantidad) as c'))
                            ->groupBy('r.id_region')
                            ->groupBy('p.id_provincia')
                            ->groupBy('p.nombre')
                            ->get();

            foreach ($provincias as $key => $provincia){
                $data[$i]['id'] = $provincia->id_region . "_" . $provincia->id_provincia;
                $data[$i]['name'] = $provincia->nombre;
                $data[$i]['parent'] = (string)$provincia->id_region;
                $data[$i]['value'] = (int)$provincia->c;
                $i++;
            }
        }


        for ($k = 1 ; $k <= 24 ; $k ++){

	    	$dt = \DateTime::createFromFormat('Ym' , $interval['max']);
	    	$dt->modify('+1 month');

        	for ($l = 0 ; $l < 12 ; $l ++){
	        	
	        	$dt->modify('-1 month');

	            $periodos = Fc002::where('periodo' , $dt->format('Ym'))
	            				->where('codigo_prestacion' , $id)
	            				->where('p.id_provincia' , str_pad($k , 2 , '0' , STR_PAD_LEFT))
	            				->join('geo.provincias as p' , 'estadisticas.fc_002.id_provincia' , '=' , 'p.id_provincia')
                            	->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                            	->select('r.id_region' , 'p.id_provincia' , 'p.nombre' , 'periodo' , DB::raw('sum(cantidad) as c'))
                            	->groupBy('r.id_region')
                            	->groupBy('p.id_provincia')
                            	->groupBy('p.nombre')
                            	->groupBy('periodo')
                            	->get();
                
                foreach ($periodos as $key => $periodo){
                	$data[$i]['id'] = $periodo->id_region . "_" . $periodo->id_provincia . "_" . $periodo->periodo;
	                $data[$i]['name'] = strftime("%b %Y" , $dt->getTimeStamp());
	                $data[$i]['parent'] = (string)$periodo->id_region . "_" . $periodo->id_provincia;
	                $data[$i]['value'] = (int)$periodo->c;
	                $i++;
                }
        	}
        }

        

        return json_encode($data);

	}

	/**
	 * Devuelve el detalle de la prestación seleccionada
	 * @param string $id
	 *
	 * @return null
	 */
	public function getDetalle($id){

		$codigo = Salud::select('*' , 
			DB::raw("case when exists (select 1 from pss.codigos_anexo where codigo_prestacion = '$id') then 1 else 0 end as anexo"),
			DB::raw("case when exists (select 1 from pss.codigos_catastroficos where codigo_prestacion = '$id') then 1 else 0 end as catastrofico"),
			DB::raw("case when exists (select 1 from pss.codigos_ccc where codigo_prestacion = '$id') then 1 else 0 end as ccc"),
			DB::raw("case when exists (select 1 from pss.codigos_priorizadas where codigo_prestacion = '$id') then 1 else 0 end as priorizado"),
			DB::raw("case when exists (select 1 from pss.codigos_estrategicos where codigo_prestacion = '$id') then 1 else 0 end as estrategico"))
		->with(['grupo' => function($q){
			$q->with(['grupoEtario','lineaCuidado']);
		},'ceb' => function($q){
			$q->with('grupoEtario');
		},'odp' => function($q){
			$q->with('grupoEtario');
		}, 'trazadora' => function($q){
			$q->with('grupoEtario');
		}
		])->find($id);


		foreach ($codigo->grupo as $prestacion){
			$array['grupos'][] = $prestacion->grupoEtario->descripcion;
			$array['lineas'][] = $prestacion->lineaCuidado->descripcion;
		}

		foreach ($codigo->ceb as $prestacion){
			$array['ceb'][] = $prestacion->grupoEtario->descripcion;
		}

		foreach ($codigo->odp as $prestacion){
			$array['odp'][$prestacion->id_odp][] = $prestacion->grupoEtario->descripcion;
		}

		foreach ($codigo->trazadora as $prestacion){
			$array['trazadora'][$prestacion->id_trazadora][] = $prestacion->grupoEtario->descripcion;
		}

		$array['grupos'] = array_unique($array['grupos']);
		$array['lineas'] = array_unique($array['lineas']);
		$array['ceb'] = array_unique($array['ceb']);
		
		if ($codigo->anexo){
			$array['atributos'][] = 'ANEXO';
		}

		if ($codigo->ccc){
			$array['atributos'][] = 'CARDIOPATÍAS CONGÉNITAS';
		}

		if ($codigo->catastrofico){
			$array['atributos'][] = 'CATASTRÓFICO';
		}

		if ($codigo->priorizado){
			$array['atributos'][] = 'PRIORIZADO';
		}

		if ($codigo->estrategico){
			$array['atributos'][] = 'ESTRATEGICO';
		}

		$data = [
			'page_title' => $id,
			'informacion' => $array,
			'codigo' => $codigo,
			'meses' => $this->getMesesArray(),
			'series' => $this->getProgreso($id),
			'distribucion' => $this->getDistribucion($id)
		];

		return view('pss.detail' , $data);
	}
}
