<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Conversacion;
use App\Models\Mensaje;

class InboxController extends Controller{
    
	/**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct(){
        $this->middleware('auth');
    }

	/**
	 * Levanto todas las conversaciones activas del usuario
	 * 
	 * @return view
	 */
    public function index(){
    	$user = Auth::user()->id_usuario;
    	$usuarios = DB::select("
		select 
            case when
                (select count (*) from chat.mensajes where id_conversacion = a.id and leido = 'N' and id_usuario <> $user) > 0 then 1 else 0 end as nuevos_mensajes ,
                u.* , 
                p.descripcion
        from (
            select 
                id
                , unnest(usuarios) as usuarios 
            from 
                chat.conversaciones
            where usuarios @> '{{$user}}'
            ) a  left join 
            sistema.usuarios u on a.usuarios = u.id_usuario left join
            geo.provincias p on u.id_provincia = p.id_provincia
        where usuarios <> $user
        order by u.last_login");

        foreach ($usuarios as $key => $usuario){
            if ($usuario->nuevos_mensajes){
                $usuario->nombre = '<b>' . $usuario->nombre . '</b>';
            }
        }

    	$data = [
    		'page_title' => 'Conversaciones activas',
    		'usuarios' => $usuarios
		];
    	return view('inbox.main' , $data);
    }

    /**
     * Obtengo la cantidad de conversaciones no leidas
     *
     * @return int
     */
    public function mensajesNoLeidos (){
    	$user = Auth::user()->id_usuario;
    	$mensajes = DB::select("
    		select count (*) as nuevos_mensajes
            from (
                select
                    count (*)
                from
                    chat.conversaciones c inner join
                    chat.mensajes m on c.id = m.id_conversacion
                where
                    c.usuarios @> '{{$user}}'
                    and m.id_usuario <> $user
                    and leido = 'N'
                group by c.id ) a");
    	return $mensajes[0]->nuevos_mensajes;
    }

    /**
     * Obtengo la cantidad de conversaciones no leidas
     *
     * @return int
     */
    public function notificacion (){
        $user = Auth::user()->id_usuario;                      

       $mensajes = DB::select("
            select count (*) as mensaje_no_notificado
            from (
                select
                    count (*)
                from
                    chat.conversaciones c inner join
                    chat.mensajes m on c.id = m.id_conversacion
                where
                    c.usuarios @> '{{$user}}'
                    and m.id_usuario <> $user
                    and leido = 'N'                    
                group by c.id ) a");
        
        $retornar = 0;

        if($mensajes[0]->mensaje_no_notificado){
            $id_conversaciones = DB::select("
                select
                    distinct(c.id)
                from
                    chat.conversaciones c inner join
                    chat.mensajes m on c.id = m.id_conversacion
                where
                    c.usuarios @> '{{$user}}'
                    and m.id_usuario <> $user
                    and leido = 'N'
            ");            
           
            foreach ($id_conversaciones[0] as $id_conversacion) {
                          
                $conversacion = Conversacion::find($id_conversacion);

                $array_conversacion = explode(',',str_replace('}', '', str_replace('{', '', $conversacion->usuarios)));                

                foreach ($array_conversacion as $usuario => $value){
                    if($value <> $user){
                        $mensajero = $value;
                    }
                }

                if(Mensaje::where('id_usuario',$mensajero)
                        ->where('id_conversacion',$id_conversacion)
                        ->where('notificado',0)->first() )
                {

                    Mensaje::where('id_usuario',$mensajero)
                        ->where('id_conversacion',$id_conversacion)
                        ->where('notificado',0)
                        ->update(['notificado' => 1]); 

                    $retornar = 1;
                }                          
            }                                   
        }        
        return $retornar;
    }

    /**
     * Actualizo todos los mensajes como leídos cuando se abre la conversación
     *
     * @return null
     */
    public function updateMensajes($id){
        $user = Auth::user()->id_usuario;
        $mensajes = Mensaje::where('id_conversacion' , '=' , $id)->where('id_usuario' , '<>' , $user)->update(['leido' => 'S']);
    }

    /**
     * Levanto el chat entre usuarios
     * @param int $id_from usuario que envia el mensaje
     * @param int $id_to usuario que recibe el mensaje
     *
     * @return view
     */
    public function chat($id_from , $id_to){
        
        $c = DB::select("select * from chat.conversaciones where usuarios @> '{{$id_from},{$id_to}}'");

        if (! sizeof($c)){
            $chat = new Conversacion;
            $chat->usuarios = "{{$id_from},{$id_to}}";
            $chat->save();
            $id_conversacion = $chat->id;
        } else {
            $id_conversacion = $c[0]->id;
        }

        $this->updateMensajes($id_conversacion);

        $mensajes = Conversacion::with([
            'mensajes' => function ($query){$query->orderBy('fecha','desc');}, 
            'mensajes.usuario' => function($query){}
        ])->where('id' , '=' , $id_conversacion)->get();

        $data = [
            'info' => $mensajes,
            'user_to' => $id_to
        ];
        return view('inbox.mensajes' , $data);
    }
}
