@extends('content')
@section('content')
<style type="text/css">
.navi li{
    text-align: center;
    padding: 2px;
    width: 150px;
    display:inline-block;
}
.error {
	color:red;
}
</style>
<div class="row">
	<form id="alta-efector">
		<div class="col-md-12">
			<div class="box box-info">
				<div class="box-header">
					<h2 class="box-title">Complete todos los campos</h2>
				</div>
				<div class="box-body">
					<div class="alert alert-danger" id="errores-div">
				        <ul id="errores-form">
				        </ul>
				    </div>
					<div id="rootwizard">
						<div class="navbar navbar-static-top">
							<div class="navbar-inner">
						    	<div class="container navi">
									<ul>
						  				<li><a href="#generales" data-toggle="tab">Generales</a></li>
										<li><a href="#planificacion" data-toggle="tab">Planificado</a></li>
										<li><a href="#observado" data-toggle="tab">Observado</a></li>				
									</ul>
						 		</div>
						  	</div>
						</div>
						<div class="progress progress-xxs">
							<div class="progress-bar progress-bar-red" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
						<div class="tab-content">
						    <div class="tab-pane" id="generales">
						    	<div class="row">
						    		<div class="col-md-4">
										<div class="form-group">
							    			<label for="provincia" class="col-sm-3 control-label">Provincia</label>
							    			<div class="col-sm-9">
								    			<select id="provincia" name="provincia" class="form-control">
								    				<option value="">Seleccione ...</option>
								    				@foreach($provincias as $provincia)   					
								    						<option value="{{ $provincia->id_provincia }}">{{ $provincia->descripcion }}</option>	
								    				@endforeach
								    			</select>
							    			</div>
						    			</div>
						    		</div>
						    		<div class="col-md-8">
						    			<div class="form-group">
							    			<label for="provincia" class="col-sm-3 control-label">Indicador</label>
							    			<div class="col-sm-9">
								    			<select id="provincia" name="provincia" class="form-control">
								    				<option value="">Seleccione ...</option>
								    				@foreach($odp as $unOdp)		    						<option value="{{ $unOdp->id_indicador }}">{{$unOdp->odp . $unOdp->tipo .  ' - ' . $unOdp->descripcion }}</option>
								    													    					
								    				@endforeach
								    			</select>
							    			</div>
						    			</div>
						    		</div>						    		
						    	</div>						    							    	
						    </div>
						    <div class="tab-pane" id="planificacion">
						    	<div class="row">
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="linea_base" class="col-sm-4 control-label">Linea Base Dic 2015</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="linea_base" name="linea_base" placeholder="Ingrese la linea base con la que finalizó el año anterior ...">
		                  					</div>
		                    			</div>
						    		</div>
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="meta_anual" class="col-sm-4 control-label">Meta Anual</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="meta_anual" name="meta_anual">
		                  					</div>
		                    			</div>
						    		</div>
						    	</div>
						    	<br />
						    	<div class="row">
						    	<?php  /*
						    		<!-- @foreach($metas_planificadas as $plan)
						    		<div class="col-md-{{12 / count($metas_planificadas)}}">
						    			<div class="form-group">
		                      				<label for="{{$plan->nombre}}" class="col-sm-4 control-label">{{$plan->nombre}}</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="{{$plan->id}}" name="{{$plan->id}}">
		                  					</div>
		                    			</div>
						    		</div>
						    		@endforeach --> */ ?>
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="6" class="col-sm-4 control-label">Meta a Abril 2016</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="6" name="6">
		                  					</div>
		                    			</div>
						    		</div>
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="6" class="col-sm-4 control-label">Meta a Agosto 2016</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="6" name="6">
		                  					</div>
		                    			</div>
						    		</div>						    		
						    	</div>						    						    	
						    </div>
							<div class="tab-pane" id="observado">
								<div class="row">
								<?php  /*
						    		<!-- @foreach($metas_observadas as $observado)
						    		<div class="col-md-{{12 / count($metas_observadas)}}">
						    			<div class="form-group">
		                      				<label for="{{$observado->nombre}}" class="col-sm-4 control-label">{{$observado->nombre}}</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="{{$observado->id}}" name="{{$observado->id}}">
		                  					</div>
		                    			</div>
						    		</div>
						    		@endforeach	 --> */ ?>
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="6" class="col-sm-4 control-label">Meta a Abril 2016</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="6" name="6">
		                  					</div>
		                    			</div>
						    		</div>
						    		<div class="col-md-6">
						    			<div class="form-group">
		                      				<label for="6" class="col-sm-4 control-label">Meta a Agosto 2016</label>
		                  					<div class="col-sm-8">
		                    					<input type="text" class="form-control" id="6" name="6">
		                  					</div>
		                    			</div>
						    		</div>									    		
						    	</div>    	
						    </div>							
							<ul class="pager wizard">
								<li class="previous"><a href="javascript:;">Anterior</a></li>
							  	<li class="next"><a href="javascript:;">Siguiente</a></li>
							</ul>
						</div>	
					</div>
				</div>
				<div class="box-footer">
					<div class="btn-group " role="group">
					 	<button class="back btn btn-info">Borrar campos</button>
					 	<button type="submit" class="finish btn btn-warning">Cargar datos</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
<div class="modal fade modal-info">
	<div class="modal-dialog">
		<div class="modal-content">
      		<div class="modal-header">
        		<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
    			<h4 class="modal-title">Atención!</h4>
      		</div>
  			<div class="modal-body">
  				<p id="modal-text"></p>
      		</div>
      		<div class="modal-footer">
        		<button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Cerrar</button>
      		</div>
    	</div><!-- /.modal-content -->
  	</div><!-- /.modal-dialog -->
</div>
<script type="text/javascript">
$(document).ready(function() {
	
	$('.finish').hide();
	$('#errores-div').hide();

	var $validator = $('form').validate({
		rules : {
			siisa : {
				required : true,
				digits : true,
				minlength : 14,
				maxlength : 14
			},
			tipo : {
				required : true
			},
			nombre : {
				required : true,
				minlength : 10,
				maxlength : 200
			},
			dep_adm : {
				required : true
			},
			cics : {
				required : true
			},
			rural : {
				required : true
			},
			categoria : {
				required : true
			},
			integrante : {
				required : true
			},
			priorizado : {
				required : true
			},
			compromiso : {
				required : true
			},
			direccion : {
				required : true,
				minlength : 6,
				maxlength : 500
			},
			provincia : {
				required : true
			},
			departamento : {
				required : true
			},
			localidad : {
				required : true
			},
			codigo_postal : {
				minlength : 4,
				maxlength : 8
			},
			numero_compromiso : {
				required : true,
				minlength : 3
			},
			firmante_compromiso : {
				required : true,
				minlength : 8
			},
			indirecto : {
				required : true
			},
			compromiso_fsus : {
				required : true
			},
			compromiso_fini : {
				required : true
			},
			compromiso_ffin : {
				required : true
			},
			convenio_firmante : {
				required : true,
				minlength : 8
			},
			convenio_numero : {
				required : true,
				minlength : 3
			},
			convenio_fsus : {
				required : true
			},
			convenio_fini : {
				required : true
			},
			convenio_ffin : {
				required : true
			},
			refer : {
				required : true
			},			
			correo : {
				email : true
			}
		},
		submitHandler : function(form){
			$.ajax({
				method : 'post',
				url : 'efectores-alta',
				data : $(form).serialize(),
				success : function(data){
					$('#modal-text').html(data);
					$('.modal').modal();
					$('form').trigger('reset');
				},
				error : function(data){
					var html = '';
					var e = JSON.parse(data.responseText);
					$.each(e , function (key , value){
						html += '<li>' + value[0] + '</li>';
					});
					$('#errores-form').html(html);
					$('#errores-div').show();
				}
			})
		}
	});
	

	$('.back').click(function(){
		$('form').trigger('reset');
	});
	
  	$('#rootwizard').bootstrapWizard({
  		onTabShow: function(tab, navigation, index) {
			var $total = navigation.find('li').length;
			var $current = index+1;
			var $percent = ($current/$total) * 100;
			$('.progress-bar').css({width:$percent+'%'});

			if($current >= $total) {
				$('.finish').show()
			} else {
				$('.finish').hide()
			}

		},
		onTabClick : function(tab, navigation, index){
			return false;
		},
		onNext : function(tab, navigation, index){
			var $valid = $('form').valid();
  			if(!$valid) {
  				$validator.focusInvalid();
  				return false;
  			}
		}
	});
});
</script>
@endsection