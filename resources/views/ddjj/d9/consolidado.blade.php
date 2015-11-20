@extends('content')
@section('content')

<div class="row">

@foreach($meses as $mes)
	
	<div class="col-md-4">
		<div class="box box-info">
			<div class="box-header">
				<h2 class="box-title">Período : {{ $mes['periodo'] }}</h2>
			</div>
			<div class="box-body">
				<div class="{{ $mes['class'] }}"></div>
			</div>
		</div>
	</div>

@endforeach
	
</div>

<script type="text/javascript">
	$(document).ready(function(){

		@foreach($meses as $mes)

		$('.{{$mes['class']}}').highcharts('Map', {
			title : {
				text : ''
			},
			mapNavigation: {
				enabled: true,
				buttonOptions: {
					verticalAlign: 'bottom'
				}
			},
			colorAxis: {
				min: 0
			},
			series : [{
				data : {!!$mes['data']!!},
				mapData: Highcharts.maps['countries/ar/ar-all'],
				joinBy: 'hc-key',
				name: 'Población',
				states: {
					hover: {
						color: '#BADA55'
					}
				},
				dataLabels: {
					enabled: false,
					format: '{point.name}'
				},/*
				point : {
					events : {
						click : InfoProvincia
					}
				},*/
				cursor : 'pointer'
			}]
		});

		@endforeach

	});
</script>
@endsection