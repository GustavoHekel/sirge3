<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
 */

// Main route ...
Route::get('/' , 'HomeController@index');

// Authentication routes ...
Route::get('login' , 'Auth\AuthController@getLogin');
Route::post('login' , 'AuthController@authenticate');
Route::get('logout' , 'Auth\AuthController@getLogout');

// Registration routes ...
Route::get('registrar' , 'RegistrationController@index');
Route::post('registrar' , 'RegistrationController@register');
Route::get('checkemail' , 'RegistrationController@email');

// Password recovery routes ...
Route::get('password' , 'PasswordController@index');
Route::post('password' , 'PasswordController@recover');
Route::get('checkemail-exists' , 'PasswordController@email');


//Inicio route ...
Route::get('inicio' , 'HomeController@inicio');

/********************************************************************************
 *								 MENU ROUTES 									*
 ********************************************************************************/

/**
 * DASHBOARD
 */
Route::get('dashboard' , 'HomeController@dashboard');
Route::get('nuevos-mensajes' , 'InboxController@mensajesNoLeidos');

/**
 * SOLICITUDES
 */
Route::get('nueva-solicitud' , 'SolicitudController@getNuevaSolicitud');
Route::post('nueva-solicitud' , 'SolicitudController@postNuevaSolicitud');
Route::get('mis-solicitudes' , 'SolicitudController@getMisSolicitudes');
Route::get('mis-solicitudes-table' , 'SolicitudController@myRequestsTable');
Route::get('asignacion-solicitud' , 'SolicitudController@getSolicitudesNoAsignadas');
Route::get('asignacion-solicitud-table' , 'SolicitudController@asignacionSolicitudesTable');
Route::get('solicitudes-pendientes' , 'SolicitudController@getPendientes');
Route::get('solicitudes-pendientes-table' , 'SolicitudController@solicitudesPendientesTable');
Route::get('cerrar-solicitud/{id}' , 'SolicitudController@getCerrar');
Route::post('cerrar-solicitud/{id}' , 'SolicitudController@postCerrar');
Route::get('listado-solicitudes' , 'SolicitudController@listado');
Route::get('listado-solicitudes-table' , 'SolicitudController@listadoTable');
Route::post('asignar-operador' , 'SolicitudController@postOperador');
Route::get('ver-solicitud/{id}/{back}' , 'SolicitudController@getSolicitud');
Route::get('tipo-solicitud/{id}' , 'SolicitudController@getTipos');
Route::get('operadores/{id}' , 'SolicitudController@getOperadores');
Route::get('notificar-cierre/{id}' , 'SolicitudController@notificarCierre');
Route::get('solicitud-final/{id}/{hash}' , 'SolicitudController@finalizarSolicitud');

/**
 * PADRONES
 */
Route::get('prestaciones' , 'PadronController@prestacion');
Route::get('comprobantes' , 'PadronController@comprobante');
Route::get('fondos' , 'PadronController@fondo');
Route::get('osp' , 'PadronController@osp');
Route::get('profe' , 'PadronController@profe');
Route::get('sss' , 'PadronController@sss');

/**
 * BENEFICIARIOS
 */
Route::get('listado-beneficiarios' , 'BeneficiariosController@index');
Route::get('listado' , 'BeneficiariosController@tabla');

/**
 * EFECTORES
 */
Route::get('efectores-listado' , 'EfectoresController@listado');
Route::get('efectores-listado-table' , 'EfectoresController@listadoTabla');
Route::get('efectores-detalle/{id}' , 'EfectoresController@detalle');
Route::get('efectores-alta' , 'EfectoresController@alta');


/**
 * USUARIO
 */
Route::get('perfil' , 'UserController@getProfile');
Route::get('ajustes' , 'UserController@getEditProfile');
Route::post('ajustes' , 'UserController@postEditProfile');
Route::get('new-password' , 'UserController@getNewPassword');
Route::post('new-password' , 'UserController@postNewPassword');
Route::get('usuario-imagen' , 'UserController@getAvatar');
Route::post('usuario-imagen' , 'UserController@postAvatar');

/**
 * CONTACTOS
 */
Route::get('contactos' , 'ContactosController@index');
Route::get('listado/{nombre?}' , 'ContactosController@listado');
Route::get('tarjeta/{id}' , 'ContactosController@tarjeta');
Route::get('mensajes/{id_from}/{id_to}' , 'ContactosController@chat');
Route::post('mensajes' , 'ContactosController@nuevoMensaje');

/**
 * INBOX
 */
Route::get('inbox' , 'InboxController@index');
Route::get('mensajes-inbox/{id_from}/{id_to}' , 'InboxController@chat');

/**
 * ADMIN
 */
Route::get('usuarios' , 'UserController@index');
Route::get('usuarios-table' , 'UserController@tabla');
Route::get('edit-usuario/{id}' , 'UserController@getEdit');
Route::post('edit-usuario/{id}' , 'UserController@postEdit');
Route::post('baja-usuario/{id}' , 'UserController@postBaja');
Route::post('unblock-usuario/{id}' , 'UserController@postUnblock');

Route::get('areas' , 'AreasController@index');
Route::get('areas-table' , 'AreasController@tabla');
Route::get('new-area' , 'AreasController@getNew');
Route::post('new-area' , 'AreasController@postNew');
Route::get('edit-area/{id}' , 'AreasController@getEdit');
Route::post('edit-area/{id}' , 'AreasController@postEdit');

Route::get('menues' , 'MenuesController@index');
Route::get('menues-table' , 'MenuesController@tabla');
Route::get('new-menu' , 'MenuesController@getNew');
Route::post('new-menu' , 'MenuesController@postNew');
Route::get('edit-menu/{id}' , 'MenuesController@getEdit');
Route::post('edit-menu/{id}' , 'MenuesController@postEdit');
Route::get('tree/{id}' , 'MenuesController@getTree');
Route::post('check-tree/{modulo}/{menu}' , 'MenuesController@check');
Route::post('uncheck-tree/{modulo}/{menu}' , 'MenuesController@uncheck');

Route::get('modulos' , 'ModulosController@index');
Route::get('modulos-table' , 'ModulosController@tabla');
Route::get('new-modulo' , 'ModulosController@getNew');
Route::post('new-modulo' , 'ModulosController@postNew');
Route::get('edit-modulo/{id}' , 'ModulosController@getEdit');
Route::post('edit-modulo/{id}' , 'ModulosController@postEdit');

Route::get('operadores' , 'OperadoresController@index');
Route::get('operadores-table' , 'OperadoresController@tabla');
Route::post('habilitar-operador' , 'OperadoresController@enable');
Route::post('deshabilitar-operador' , 'OperadoresController@disable');
Route::get('new-operador' , 'OperadoresController@getNew');
Route::post('new-operador' , 'OperadoresController@postNew');

/********************************************************************************
 *							 	   MISC ROUTES 									*
 ********************************************************************************/
Route::get('departamentos/{provincia}' , 'GeoController@departamentos');
Route::get('localidades/{provincia}/{departamento}' , 'GeoController@localidades');

/********************************************************************************
 *								 	WS ROUTES 									*
 ********************************************************************************/
Route::resource('prestaciones' , 'Ws\SIISAController' , [
	'only' => ['index']]);

Route::get('php-info' , function(){
	phpinfo();
});


