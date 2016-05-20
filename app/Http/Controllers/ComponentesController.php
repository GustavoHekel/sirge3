<?php

namespace App\Http\Controllers;

use DB;
use Datatables;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Geo\Provincia;
use App\Models\Dw\CEB\Ceb001;
use App\Models\Dw\CEB\Ceb002;
use App\Models\Dw\CEB\Ceb003;
use App\Models\Dw\CEB\Ceb004;
use App\Models\Dw\CEB\Ceb005;
use App\Models\Dw\FC\Fc001;
use App\Models\TareasResultado;

class ComponentesController extends Controller
{
    /** 
     * Devuelve la vista del resumen de O.D.P 1
     * @param string $periodo
     *
     * @return null
     */
    public function getResumenODP1($periodo = null, $provincia = null){
       /* $periodo = 201601;
        $provincia = '05';*/

        if(isset($periodo)){
            $dt = \DateTime::createFromFormat('Y-m' , substr($periodo, 0,4) . '-' . substr($periodo, 4,2));
            $periodo = $dt->format('Ym');    
        }
        else{
            $periodo = TareasResultado::select(DB::raw('max(periodo)'))->first()->max;
            $dt = \DateTime::createFromFormat('Y-m' , substr($periodo, 0,4) . '-' . substr($periodo, 4,2));                
        }

        if(! isset($provincia)){
            $provincia = null;
            $provincia_descripcion = 'Nivel Pais';
        }
        else{
            $datos_provincia = Provincia::find($provincia);            
            $provincia_descripcion =  $datos_provincia->descripcion;
        }        

        $data = [
            'page_title' => 'Resumen mensual O.D.P 1, '.$provincia_descripcion.', '. ucwords(strftime("%B %Y" , $dt->getTimeStamp())),
            /*'progreso_ceb_series' => $this->getProgresoCeb($periodo),
            'progreso_ceb_categorias' => $this->getMesesArray($periodo),
            'distribucion_provincial_categorias' => $this->getProvinciasArray(),
            'distribucion_provincial_series' => $this->getDistribucionProvincial($periodo),*/
            'map' => $this->getMapSeries($periodo),
            'treemap_data' => $this->getDistribucionCodigos($periodo,$provincia),
            'pie_ceb' => $this->getDistribucionCeb($periodo,$provincia),
            'pie_ceb_hombres' => $this->getDistribucionCebHombres($periodo,$provincia),
            'distribucion_sexos' => $this->getSexosSeries($periodo,$provincia),
            'periodo_calculado' => $periodo,
            'provincia' => $provincia == null ? 'pais' : $provincia,
            'provincia_descripcion' => $provincia_descripcion
        ];

        return view('componentes.odp1' , $data);
    }

    /**
     * Retorna la información para armar el gráfico complicado
     *
     * @return json
     */
    public function getDistribucionCodigos($periodo, $provincia = null){                

        $data = [];

        if(! isset($provincia)){        
            $i = 0;
            $regiones = Ceb003::where('periodo' , $periodo)
                            ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                            ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                            ->select('r.id_region' , 'r.nombre' , DB::raw('sum(cantidad) as cantidad'))
                            ->groupBy('r.id_region')
                            ->groupBy('r.nombre')
                            ->get();

            foreach ($regiones as $key => $region){
                $data[$i]['color'] = $this->alter_brightness('#0F467F' , $key * 35);
                $data[$i]['id'] = (string)$region->id_region;
                $data[$i]['name'] = $region->nombre;
                $data[$i]['value'] = (int)$region->cantidad;
                $i++;
            }

            for ($j = 0 ; $j <= 5 ; $j ++){
                $provincias = Ceb003::where('periodo' , $periodo)
                                ->where('r.id_region' , $j)
                                ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                                ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                                ->select('r.id_region' , 'p.id_provincia' , 'p.nombre' , DB::raw('sum(cantidad) as cantidad'))
                                ->groupBy('r.id_region')
                                ->groupBy('p.id_provincia')
                                ->groupBy('p.nombre')
                                ->get();
                foreach ($provincias as $key => $provincia){
                    $data[$i]['id'] = $provincia->id_region . "_" . $provincia->id_provincia;
                    $data[$i]['name'] = $provincia->nombre;
                    $data[$i]['parent'] = (string)$provincia->id_region;
                    $data[$i]['value'] = (int)$provincia->cantidad;
                    $i++;
                }
            }

            for ($k = 1 ; $k <= 24 ; $k ++){
                $matriz_aux = [];
                $codigos = Ceb003::where('periodo' , $periodo)
                                ->where('p.id_provincia' , str_pad($k , 2 , '0' , STR_PAD_LEFT))
                                ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                                ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                                ->join('pss.codigos as cg' , 'c003.codigo_prestacion' , '=' , 'cg.codigo_prestacion')
                                ->select('r.id_region' , 'p.id_provincia' , 'c003.codigo_prestacion' , 'cg.descripcion_grupal' , DB::raw('sum(cantidad) as cantidad'))
                                ->groupBy('r.id_region')
                                ->groupBy('p.id_provincia')
                                ->groupBy('c003.codigo_prestacion')
                                ->groupBy('cg.descripcion_grupal')
                                ->orderBy(DB::raw('sum(cantidad)') , 'desc')
                                ->take(15)
                                ->get();
                foreach ($codigos as $key => $codigo){
                    $matriz_aux[] = $codigo->codigo_prestacion;
                    $data[$i]['id'] = $codigo->id_region . "_" . $codigo->id_provincia . "_" . $codigo->codigo_prestacion;
                    $data[$i]['name'] = $codigo->codigo_prestacion;
                    $data[$i]['parent'] = $codigo->id_region . "_" . $codigo->id_provincia;
                    $data[$i]['value'] = (int)$codigo->cantidad;
                    $data[$i]['texto_prestacion'] = $codigo->descripcion_grupal;
                    $data[$i]['codigo_prestacion'] = true;
                    $i++;   
                }

                for ($l = 0 ; $l < count($matriz_aux) ; $l ++){
                    $grupos = Ceb003::where('periodo' , $periodo)
                                    ->where('p.id_provincia' , str_pad($k , 2 , '0' , STR_PAD_LEFT))
                                    ->where('codigo_prestacion' , $matriz_aux[$l])
                                    ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                                    ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                                    ->join('pss.grupos_etarios as g' , 'c003.grupo_etario' , '=' , 'g.sigla')
                                    ->select('r.id_region' , 'p.id_provincia' , 'c003.codigo_prestacion' , 'g.descripcion' , DB::raw('sum(cantidad) as cantidad'))
                                    ->groupBy('r.id_region')
                                    ->groupBy('p.id_provincia')
                                    ->groupBy('c003.codigo_prestacion')
                                    ->groupBy('g.descripcion')
                                    ->get();
                    foreach ($grupos as $key => $grupo){
                        $data[$i]['id'] = $grupo->id_region . "_" . $grupo->id_provincia . "_" . $grupo->codigo_prestacion . "_" . $grupo->grupo_etario;
                        $data[$i]['name'] = $grupo->descripcion;
                        $data[$i]['parent'] = $grupo->id_region . "_" . $grupo->id_provincia . "_" . $grupo->codigo_prestacion;
                        $data[$i]['value'] = (int)$grupo->cantidad;
                        $i++;   
                    }
                }
            }
        }
        else{

            $i = 0;
            $matriz_aux = [];            
            $codigos = Ceb003::where('periodo' , $periodo)
                            ->where('p.id_provincia' , $provincia)
                            ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                            ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                            ->join('pss.codigos as cg' , 'c003.codigo_prestacion' , '=' , 'cg.codigo_prestacion')
                            ->select('r.id_region' , 'p.id_provincia' , 'c003.codigo_prestacion' , 'cg.descripcion_grupal' , DB::raw('sum(cantidad) as cantidad'))
                            ->groupBy('r.id_region')
                            ->groupBy('p.id_provincia')
                            ->groupBy('c003.codigo_prestacion')
                            ->groupBy('cg.descripcion_grupal')
                            ->orderBy(DB::raw('sum(cantidad)') , 'desc')
                            ->take(15)
                            ->get();
            foreach ($codigos as $key => $codigo){
                $matriz_aux[] = $codigo->codigo_prestacion;
                $data[$i]['id'] = $codigo->codigo_prestacion;
                $data[$i]['name'] = $codigo->codigo_prestacion;                
                $data[$i]['value'] = (int)$codigo->cantidad;
                $data[$i]['texto_prestacion'] = $codigo->descripcion_grupal;
                $data[$i]['codigo_prestacion'] = true;
                $i++;   
            }

            for ($l = 0 ; $l < count($matriz_aux) ; $l ++){
                $grupos = Ceb003::where('periodo' , $periodo)
                                ->where('p.id_provincia' , $provincia)
                                ->where('codigo_prestacion' , $matriz_aux[$l])
                                ->join('geo.provincias as p' , 'c003.id_provincia' , '=' , 'p.id_provincia')
                                ->join('geo.regiones as r' , 'p.id_region' , '=' , 'r.id_region')
                                ->join('pss.grupos_etarios as g' , 'c003.grupo_etario' , '=' , 'g.sigla')
                                ->select('r.id_region' , 'p.id_provincia' , 'c003.codigo_prestacion' , 'g.descripcion' , DB::raw('sum(cantidad) as cantidad'))
                                ->groupBy('r.id_region')
                                ->groupBy('p.id_provincia')
                                ->groupBy('c003.codigo_prestacion')
                                ->groupBy('g.descripcion')
                                ->get();
                foreach ($grupos as $key => $grupo){
                    $data[$i]['id'] = $grupo->codigo_prestacion . "_" . $grupo->grupo_etario;
                    $data[$i]['name'] = $grupo->descripcion;
                    $data[$i]['parent'] = $grupo->codigo_prestacion;
                    $data[$i]['value'] = (int)$grupo->cantidad;
                    $i++;   
                }
            }
        }
        
        return json_encode($data);
    }

    /**
     * Devuelve la info para el grafico de torta para beneficiarios hombres de 20-64
     * @param string $periodo
     *
     * @return json
     */
    protected function getDistribucionCebHombres($periodo, $provincia = null){
        
        $meta = 7;

        $object = Ceb004::select(DB::raw('sum(beneficiarios_activos) as y'))->where('periodo',$periodo);
        if(isset($provincia)){
            $object->where('id_provincia',$provincia);
        }                                
        $cantidad_total = $object->first()->y;
        $cantidad_para_cumplir = round($object->first()->y * $meta / 100);
                
        $object = Ceb004::select(DB::raw('sum(beneficiarios_ceb) as y'))->where('periodo',$periodo);
        if(isset($provincia)){
            $object->where('id_provincia',$provincia);
        }
        $cantidad_cumplida = round($object->first()->y);        

        if($cantidad_para_cumplir > $cantidad_cumplida){
            $data[] = array_merge(array('y' => $cantidad_total - $cantidad_para_cumplir), array('name' => 'activos s/ceb', 'color' => '#DCDCDC'));
            $data[] = array_merge(array('y' => $cantidad_para_cumplir - $cantidad_cumplida),array('name' => 'faltante', 'color' => '#B00000 ', 'sliced' => true, 'selected' => true));
            $data[] = array_merge($object->first()->toArray(),array('name' => 'ceb', 'color' => '#00FFFF'));            
        }
        else{
            $data[] = array_merge(array('y' => $cantidad_total - $cantidad_cumplida), array('name' => 'activos s/ceb', 'color' => '#DCDCDC'));
            $data[] = array_merge(array('y' => $cantidad_cumplida - $cantidad_para_cumplir),array('name' => 'superado', 'color' => '#00CC00', 'sliced' => true, 'selected' => true));
            $data[] = array_merge(array('y' => $cantidad_para_cumplir),array('name' => 'ceb', 'color' => '#00FFFF'));             
        }         

        $superObjeto = [
                        'titulo' => 'Meta: '. $meta . '%',
                        'data' => json_encode($data) 
                        ];

        return $superObjeto;
    }

    /**
     * Devuelve la info para el grafico de torta para beneficiarios sin hombres de 20-64
     * @param string $periodo
     *
     * @return json
     */
    protected function getDistribucionCeb($periodo, $provincia = null){
         
        $meta = 45;      
        
        $object = Ceb005::select(DB::raw('sum(beneficiarios_activos) as y'))->where('periodo',$periodo);
        if(isset($provincia)){
            $object->where('id_provincia',$provincia);
        }                
        $cantidad_total = $object->first()->y;
        $cantidad_para_cumplir = round($object->first()->y * $meta / 100);
                
        $object = Ceb005::select(DB::raw('sum(beneficiarios_ceb) as y'))->where('periodo',$periodo);
        if(isset($provincia)){
            $object->where('id_provincia',$provincia);
        }
        $cantidad_cumplida = round($object->first()->y);

        if($cantidad_para_cumplir > $cantidad_cumplida){
            $data[] = array_merge(array('y' => $cantidad_total - $cantidad_para_cumplir), array('name' => 'activos s/ceb', 'color' => '#DCDCDC'));
            $data[] = array_merge(array('y' => $cantidad_para_cumplir - $cantidad_cumplida),array('name' => 'faltante', 'color' => '#B00000 ', 'sliced' => true, 'selected' => true));
            $data[] = array_merge($object->first()->toArray(),array('name' => 'ceb', 'color' => '#00FFFF'));            
        }
        else{
            $data[] = array_merge(array('y' => $cantidad_total - $cantidad_cumplida), array('name' => 'activos s/ceb', 'color' => '#DCDCDC'));
            $data[] = array_merge(array('y' => $cantidad_cumplida - $cantidad_para_cumplir),array('name' => 'superado', 'color' => '#00CC00', 'sliced' => true, 'selected' => true));
            $data[] = array_merge(array('y' => $cantidad_para_cumplir),array('name' => 'ceb', 'color' => '#00FFFF'));             
        }        

        $superObjeto = [
                        'titulo' => 'Meta: '. $meta . '%',
                        'data' => json_encode($data) 
                        ];
     
        return $superObjeto;
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
     * Devuelve la info para el gráfico por sexo
     * @param string $periodo
     *
     * @return json
     */
    protected function getSexosSeries($periodo, $provincia = null){                

        $grupos = ['A','B','C','D'];

        foreach ($grupos as $grupo) {

            $sexos = Ceb003::where('periodo' , $periodo)
                            ->where('grupo_etario' , $grupo)
                            ->whereIn('sexo',['M','F'])
                            ->select('sexo' , DB::raw('sum(cantidad) as c'))
                            ->groupBy('sexo')
                            ->orderBy('sexo');
                            
            if(isset($provincia)){
                $sexos = $sexos->where('id_provincia', $provincia);
            }            
            
            $sexos = $sexos->get();

            $data[0]['name'] = 'Hombres';
            $data[1]['name'] = 'Mujeres';

            foreach ($sexos as $sexo){

                if ($sexo->sexo == 'M'){
                    $data[0]['data'][] = (int)(-$sexo->c);
                    $data[0]['color'] = '#3c8dbc';
                } else {
                    $data[1]['data'][] = (int)($sexo->c);
                    $data[1]['color'] = '#D81B60';
                }
            }
        }

        return json_encode($data);
    }

    /**
     * Devuelve la información para graficar los mapas
     * @param int $periodo 
     * @param int $linea
     *
     * @return array
     */
    protected function getMapSeries($periodo){        

        $provincias = Ceb002::where('periodo' , $periodo)->groupBy('id_provincia')->orderBy('id_provincia' , 'desc')->lists('id_provincia');
        

        $resultados = Ceb002::join('geo.geojson as g' , 'estadisticas.ceb_002.id_provincia' , '=' , 'g.id_provincia')
                                ->where('periodo' , $periodo)                                
                                ->orderBy('estadisticas.ceb_002.id_provincia' , 'asc')
                                ->get();

        //return '<pre>' . json_encode($resultados , JSON_PRETTY_PRINT) . '</pre>';

        foreach ($resultados as $key_provincia => $resultado){
            
            $map['map-data'][$key_provincia]['value'] = $resultado->beneficiarios_ceb;            
            $map['map-data'][$key_provincia]['hc-key'] = $resultado->codigo;            
            $map['map-data'][$key_provincia]['periodo'] = $periodo;
            $map['map-data'][$key_provincia]['provincia'] = $resultado->id_provincia;
        }

        $map['map-data'] = json_encode($map['map-data']);
        $map['clase'] = $key_provincia;        

        return $map;
    }

    /**
     * Devuelve un pequeño detalle del indicador para una provincia y un período
     * @param int $periodo
     * @param int $indicador
     * @param char $provincia
     *
     * @return null
     */
    public function getDetalleProvincia($periodo, $provincia){        
       
        $dt = \DateTime::createFromFormat('Y-m' , substr($periodo, 0,4) . '-' . substr($periodo, 4,2));
        $periodo = $dt->format('Ym');               
        $resultado = Ceb005::where('periodo' , $periodo);

        if( $provincia != 'pais' ){
            $resultado = $resultado->where('id_provincia' , $provincia);                   

            $datos_provincia = Provincia::find($provincia);
            $data[0]['entidad'] =  $datos_provincia->descripcion;                      
        }
        else{
            $resultado = $resultado->select(DB::raw('sum(beneficiarios_registrados) as beneficiarios_registrados'),DB::raw('sum(beneficiarios_activos) as beneficiarios_activos'),DB::raw('sum(beneficiarios_ceb) as beneficiarios_ceb'));
            $data[0]['entidad'] =  'País'; 
        }

        $resultado = $resultado->first();        

        $data[0]['titulo'] = 'Niños, adolescentes y mujeres adultas';
        $data[0]['periodo'] = $periodo;
        $data[0]['beneficiarios_registrados'] = number_format($resultado->beneficiarios_registrados);
        $data[0]['beneficiarios_activos'] = number_format($resultado->beneficiarios_activos);
        $data[0]['beneficiarios_ceb'] = number_format($resultado->beneficiarios_ceb);
        $data[0]['porcentaje_actual'] = round($resultado->beneficiarios_ceb / $resultado->beneficiarios_activos , 2) * 100;

        $resultado = Ceb004::where('periodo' , $periodo);

        if($provincia != 'pais'){
            $resultado = $resultado->where('id_provincia' , $provincia);
                       

            $datos_provincia = Provincia::find($provincia);
            $data[1]['entidad'] =  $datos_provincia->descripcion;                      
        }
        else{
            $resultado = $resultado->select(DB::raw('sum(beneficiarios_registrados) as beneficiarios_registrados'),DB::raw('sum(beneficiarios_activos) as beneficiarios_activos'),DB::raw('sum(beneficiarios_ceb) as beneficiarios_ceb'));
            $data[1]['entidad'] =  'País'; 
        }

        $resultado = $resultado->first();

        $data[1]['titulo'] = 'Hombres adultos';
        $data[1]['periodo'] = $periodo;   
        $data[1]['beneficiarios_registrados'] = number_format($resultado->beneficiarios_registrados);
        $data[1]['beneficiarios_activos'] = number_format($resultado->beneficiarios_activos);
        $data[1]['beneficiarios_ceb'] = number_format($resultado->beneficiarios_ceb);
        $data[1]['porcentaje_actual'] = round($resultado->beneficiarios_ceb / $resultado->beneficiarios_activos , 2) * 100;   

        //return var_dump(array('data' => json_encode($data)));

        return view('componentes.ceb-detalle-provincia' , array('data' => $data));
    }
}