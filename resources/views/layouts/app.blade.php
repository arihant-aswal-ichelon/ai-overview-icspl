<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>

            <link href="{{ url('assets/css/mermaid.min.css') }}" rel="stylesheet" type="text/css" />
           <!-- Layout config Js -->
            <script src="{{ url('assets/js/layout.js') }}"></script>
            <!-- Bootstrap Css -->
            <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
            <!-- Icons Css -->
            <link href="{{ url('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
            <!-- App Css-->
            <link href="{{ url('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
            <!-- custom Css-->
            <link href="{{ url('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
            <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    </head>

    <body class="hold-transition sidebar-mini layout-fixed">
        <div class="auth-page-wrapper pt-5">
            @yield("content")
            <!-- footer -->
            <footer class="footer">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="text-center">
                                <p class="mb-0 text-muted">&copy;
                                    <?php echo date('Y'); ?> Crafted with <i class="mdi mdi-heart text-danger"></i> by Ichelon
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
            <!-- end Footer -->
        </div>
    <!-- JAVASCRIPT -->
    <script src="{{ url('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('assets/js/simplebar.min.js') }}"></script>
    <script src="{{ url('assets/js/waves.min.js') }}"></script>
    <script src="{{ url('assets/js/feather.min.js') }}"></script>
    <script src="{{ url('assets/js/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ url('assets/js/plugins.js') }}"></script>

    <!-- particles js -->
    <script src="{{ url('assets/js/particles.js') }}"></script>
    <!-- particles app js -->
    <script src="{{ url('assets/js/particles.app.js') }}"></script>
    <!-- password-addon init -->
    <script src="{{ url('assets/js/password-addon.init.js') }}"></script>

    <!-- prismjs plugin -->
    <script src="{{ url('assets/js/prism.js') }}"></script>
    <!-- gridjs js -->
    <script src="{{ url('assets/js/gridjs.umd.js') }}"></script>
    <!-- gridjs init -->
    <script src="{{ url('assets/js/pages/gridjs.init.js') }}"></script>
    </body>
</html>
