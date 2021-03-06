@extends('content')
@section('content')
<div class="row" id="request-container">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header">
                <h2 class="box-title">Estado de mis requerimientos</h2>
            </div>
            <div class="box-body">
                <table class="table table table-hover" id="my-requests-table">
                    <thead>
                      <tr>
                        <th>Nº</th>
                        <th>Fecha ingreso</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Detalles</th>
                      </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        
         $('#my-requests-table').DataTable({
            processing: true,
            serverSide: true,
            ajax : 'mis-solicitudes-table',
            columns: [
                { data: 'id', name: 'id' },
                { data: 'fecha_solicitud', name: 'fecha_solicitud' },
                { data: 'tipos.descripcion', name: 'tipo' },
                { data: 'estado_label', name: 'estado_label' },
                { data: 'action', name: 'action'}
            ],
            order : [[0,'desc']]
        });


        $('#my-requests-table').on('click','.view-solicitud' , function(){
            var id = $(this).attr('id-solicitud');
            $.get('ver-solicitud/' + id + '/mis-solicitudes', function(data){
                $('#request-container').html(data);
            })
        });
    }); 
</script>
@endsection