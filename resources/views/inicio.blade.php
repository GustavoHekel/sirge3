<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html>
<head>
    <meta charset="UTF-8">
    <title>SIRGe Web</title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- Bootstrap 3.3.2 -->
    <link href="{{ asset("/bower_components/admin-lte/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css" />
    <!-- Font Awesome Icons -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Ionicons -->
    <link href="http://code.ionicframework.com/ionicons/2.0.0/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- Fullcalendar -->
    <link href="{{ asset("/bower_components/admin-lte/plugins/fullcalendar/fullcalendar.css") }}" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="{{ asset("/bower_components/admin-lte/dist/css/AdminLTE.css")}}" rel="stylesheet" type="text/css" />
    <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
          page. However, you can choose any other skin. Make sure you
          apply the skin class to the body tag so the changes take effect.
    -->
    <link href="{{ asset("/bower_components/admin-lte/dist/css/skins/skin-red-light.min.css")}}" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->
</head>
<body class="skin-red-light">
<div class="wrapper">

    <!-- Header -->
    @include('header')

    <!-- Sidebar -->
    @include('sidebar')

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper"></div><!-- /.content-wrapper -->

    <!-- Footer -->
    @include('footer')

</div><!-- ./wrapper -->

<!-- REQUIRED JS SCRIPTS -->

<!-- jQuery 2.1.4 -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/jQuery/jQuery-2.1.4.min.js") }}"></script>
<!-- Bootstrap 3.3.2 JS -->
<script src="{{ asset ("/bower_components/admin-lte/bootstrap/js/bootstrap.min.js") }}" type="text/javascript"></script>
<!-- Highcharts -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/highcharts/js/highcharts.js") }}" type="text/javascript"></script>
<!-- Highcharts exporting server -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/highcharts/js/modules/exporting.js") }}" type="text/javascript"></script>
<!-- Highmaps -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/highmaps/js/modules/map.js") }}" type="text/javascript"></script>
<!-- Highmaps Argentina -->
<script src="http://code.highcharts.com/mapdata/countries/ar/ar-all.js" type="text/javascript"></script>
<!-- Jquery Sparkline -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/sparkline/jquery.sparkline.min.js") }}" type="text/javascript"></script>
<!--- MomentJs -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/fullcalendar/moment.js") }}" type="text/javascript"></script>
<!-- Fullcalendar -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/fullcalendar/fullcalendar.min.js") }}" type="text/javascript"></script>
<!-- Google Calendar -->
<script src="{{ asset ("/bower_components/admin-lte/plugins/fullcalendar/gcal.js") }}" type="text/javascript"></script>
<!-- AdminLTE App -->
<script src="{{ asset ("/bower_components/admin-lte/dist/js/app.min.js") }}" type="text/javascript"></script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience -->
<script>
$(document).ready(function(){
    
    $.get('dashboard', function(data){
        $('.content-wrapper').html(data);
    });

    $('.sidebar-menu a[href!="#"]').click(function(event){
        event.preventDefault();
        var modulo = $(this).attr('href');
        $.get(modulo , function (data){
            $('.content-wrapper').html(data);
        });
    });
});
</script>
</body>
</html>
