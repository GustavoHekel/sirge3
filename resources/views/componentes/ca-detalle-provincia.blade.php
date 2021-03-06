<div class="row">	
	<div class="col-md-12">
		<form class="form-horizontal">		
			<div class="form-group">
				<div class="col-md-1">
				</div>
				<div class="col-md-10">
					<h4 class="form-control-static">{{ 'Control prenatal en embarazadas' }}</h4>				
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-6 control-label">Entidad</label>
				<div class="col-md-6">
					<p class="form-control-static">{{ 'País' }}</p>
				</div>
			</div>			

			<div class="form-group">
				<label class="col-md-6 control-label">% Observado Actual</label>
				<div class="col-md-6">
					<p class="form-control-static">{{ '34.1' }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-6 control-label">% Meta Anual</label>
				<div class="col-md-6">
					<p class="form-control-static">{{ '36' }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-6 control-label">Planificado Abril 2016</label>
				<div class="col-md-6">
					<p class="form-control-static">{{ '33' }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-6 control-label">Planificado Agosto 2016</label>
				<div class="col-md-6">
					<p class="form-control-static">{{ '36' }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-6 control-label">Estado a&ntilde;o anterior </label>
				<div class="btn-group col-md-6" data-toggle="tooltip" title="Desde esta opción podrá ver el estado del último año del O.D.P 2 a nivel nacional y regional" role="group">
					<button type="button" href="componentes-odp1-evolucion/{{'2'}}" class="detalle btn btn-info">Ver detalles</button>
				</div>		
			</div>
		</form>
	</div>	
</div>

<script type="text/javascript">
	$(document).ready(function(){

		$('.detalle').click(function(event){
		        event.preventDefault();
		        var modulo = $(this).attr('href');
		        $.get(modulo, function(data){
					$('.content-wrapper').html(data);
				});						
	    	});
	});
</script>
