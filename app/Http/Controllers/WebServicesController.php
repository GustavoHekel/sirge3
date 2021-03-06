<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp;
use SimpleXMLElement;
use App\Models\Beneficiario;
use App\Models\InscriptosPadronSisa;
use App\Models\ErrorPadronSisa;


class WebServicesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return new GuzzleHttp\Client(['base_uri' => 'https://sisa.msal.gov.ar/sisa/services/rest/cmdb/obtener']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Devuelve una respuesta con los parámetros consultados
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function makeRequest($id)
    {
        //
    }

    /**
     * Devuelve una respuesta enviando los parámetros a consultar en siisa 
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function siisaXMLRequest($nrdoc, $sexo = null)
    {
        $client = $this->create();

        $url = 'https://sisa.msal.gov.ar/sisa/services/rest/cmdb/obtener?nrodoc='.$nrdoc.'&usuario=fnunez&clave=fernandonunez';
        
        if($sexo){
            $url = $url . '&sexo=' . $sexo;
        }

        $response = $client->get($url);

        /*echo $response->getStatusCode();

        echo '</br></br>';*/

        $datos = get_object_vars(new SimpleXMLElement($response->getBody()));

        echo json_encode($datos);        
    }

     /**
     * Devuelve una respuesta enviando los parámetros a consultar en siisa 
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function cruceSiisaXMLRequest($nrdoc, $client)
    {
        
        if(!InscriptosPadronSisa::find($nrdoc)){
            
            //$url = 'https://sisa.msal.gov.ar/sisa/services/rest/cmdb/obtener?nrodoc=$nrodoc&usuario=fnunez&clave=fernandonunez';

            $url = 'https://sisa.msal.gov.ar/sisa/services/rest/cmdb/obtener?nrodoc='.$nrdoc.'&usuario=fnunez&clave=fernandonunez';                    

            try {
                $response = $client->get($url);                
            } catch (Exception $e) {
                if($e->getCode() == 500){
                    return var_dump($nrodoc);
                }
                else{
                    return $e->getMessage();   
                }
                
            }        
            /*echo $response->getStatusCode();

            echo '</br></br>';*/

            $datos = get_object_vars(new SimpleXMLElement($response->getBody()));

            return json_encode($datos);        
        }
        else{
            return null;
        }
    }

     /**
     * Devuelve una respuesta enviando los parámetros a consultar en siisa 
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertirEnTexto($valor){
        if(gettype($valor) == "object"){
            if(isset($valor->{'0'})){
                if($valor->{'0'} == ' ' || $valor->{'0'} == ''){
                    return null;
                }
                else{
                    return $valor->{'0'};   
                }
            }
            else{
                return null;
            }                
        }        
        else{
            if($valor == 'NULL'){
                return null;
            }
            else{
                return $valor;
            }       
        }
    }

     /**
     * Busca los documentos de los beneficiarios que no están cruzados con siisa y guarda sus datos.
     *     
     * @return "Resultado"
     */
    public function cruzarBeneficiariosConSiisa(){

        $ch = curl_init();      

        set_time_limit(0);
        curl_setopt($ch, CURLOPT_TIMEOUT,30000);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        curl_close($ch);

        $client = $this->create();

        $documentos = Beneficiario::leftjoin('siisa.inscriptos_padron as i' , 'beneficiarios.beneficiarios.numero_documento' , '=' , 'i.nrodocumento')
                                  ->leftjoin('siisa.error_padron_siisa as e' , 'beneficiarios.beneficiarios.numero_documento' , '=' , 'e.numero_documento')          
                                  ->where('id_provincia_alta' , '05')
                                  ->where('clase_documento' , 'P')
                                  //->where('numero_documento','22584419')
                                  ->whereNull('i.nrodocumento')
                                  ->where(function($query) {                                        
                                        return $query->whereNull('e.numero_documento')
                                            ->orWhere('error', '!=', 'REGISTRO_NO_ENCONTRADO');
                                    })                                  
                                  ->take(30000)
                                  ->lists('beneficiarios.beneficiarios.numero_documento');                 

        foreach ($documentos as $key => $documento){
            $datos_benef = $this->cruceSiisaXMLRequest($documento, $client);
            if($datos_benef){
                $data = json_decode($datos_benef);
                if ($data->resultado == 'OK') {
                    $resultado = $this->guardarDatos($data);
                    if($resultado != TRUE){
                        echo $resultado;
                    }                        
                }
                else{
                    try {                        
                        $this->guardarError($data, $documento);
                    } catch (Exception $e) {
                        echo $e->getCode(); 
                    }
                }    
            }  
            unset($datos_benef);
            unset($data);                      
        }
        unset($documento);
        echo "Los beneficiarios se han insertado correctamente";
    }

     /**
     * Guarda los datos encontrados en el webservice del siisa
     *
     * @param  object  $datos
     * @return json_encode($datos)
     */
    public function guardarDatos($datos){
                
        $inscripto = new InscriptosPadronSisa();
        $inscripto->id = $this->convertirEnTexto($datos->id);                
        $inscripto->codigosisa = $this->convertirEnTexto($datos->codigoSISA);
        $inscripto->identificadorenaper = $this->convertirEnTexto($datos->identificadoRenaper);
        $inscripto->padronsisa = $this->convertirEnTexto($datos->PadronSISA);
        $inscripto->tipodocumento = $this->convertirEnTexto($datos->tipoDocumento);
        $inscripto->nrodocumento = $this->convertirEnTexto($datos->nroDocumento);
        $inscripto->apellido = $this->convertirEnTexto($datos->apellido);
        $inscripto->nombre = $this->convertirEnTexto($datos->nombre);
        $inscripto->sexo = $this->convertirEnTexto($datos->sexo);
        $inscripto->fechanacimiento = $this->convertirEnTexto($datos->fechaNacimiento);
        $inscripto->estadocivil = $this->convertirEnTexto($datos->estadoCivil);
        $inscripto->provincia = $this->convertirEnTexto($datos->provincia);
        $inscripto->departamento = $this->convertirEnTexto($datos->departamento);
        $inscripto->localidad = $this->convertirEnTexto($datos->localidad);
        $inscripto->domicilio = $this->convertirEnTexto($datos->domicilio);
        $inscripto->pisodpto = $this->convertirEnTexto($datos->pisoDpto);
        $inscripto->codigopostal = $this->convertirEnTexto($datos->codigoPostal);
        $inscripto->paisnacimiento = $this->convertirEnTexto($datos->paisNacimiento);
        $inscripto->provincianacimiento = $this->convertirEnTexto($datos->provinciaNacimiento);
        $inscripto->localidadnacimiento = $this->convertirEnTexto($datos->localidadNacimiento);
        $inscripto->nacionalidad = $this->convertirEnTexto($datos->nacionalidad);
        $inscripto->fallecido = $this->convertirEnTexto($datos->fallecido);
        $inscripto->fechafallecido = $this->convertirEnTexto($datos->fechaFallecido);
        $inscripto->donante = $this->convertirEnTexto($datos->donante);
        try {
            $inscripto->save();
            unset($inscripto);
            return TRUE;
        } catch (QueryException $e) {
            return json_encode($e);
        }                        
    }

     /**
     * Guarda el error de la búsqueda del beneficiario.
     *
     * @param  object $datos
     * @return bool
     */
    public function guardarError($datos, $documento){
            
        if($noEncontrado = ErrorPadronSisa::find($documento)){
            $noEncontrado->error = $this->convertirEnTexto($datos->resultado);    
        }
        else{
            $noEncontrado = new ErrorPadronSisa();
            $noEncontrado->numero_documento = $documento;                
            $noEncontrado->error = $this->convertirEnTexto($datos->resultado);            
        }            
        try {
            $noEncontrado->save();
            unset($noEncontrado);
            return TRUE;
        } catch (QueryException $e) {
            return json_encode($e);
        }                        
    }
}
