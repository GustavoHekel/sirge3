<div class="row">

	@foreach ($data as $subindicador)

	<div class="col-md-6">
		<form class="form-horizontal">		
			<div class="form-group">
				<div class="col-md-1">
				</div>
				<div class="col-md-10">
					<h4 class="form-control-static">{{ $subindicador['titulo'] }}</h4>				
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">Entidad</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['entidad'] }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">Beneficarios registrados</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['beneficiarios_registrados'] }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">Beneficarios activos</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['beneficiarios_activos'] }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">Beneficarios ceb</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['beneficiarios_ceb'] }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">% Ceb</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['porcentaje_actual'] }}</p>
				</div>
			</div>

			<div class="form-group">
				<label class="col-md-7 control-label">Periodo</label>
				<div class="col-md-5">
					<p class="form-control-static">{{ $subindicador['periodo'] }}</p>
				</div>
			</div>
		</form>
	</div>

	@endforeach

</div>