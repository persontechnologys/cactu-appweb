<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
	<!-- Favicon -->
	<link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
	<link rel="icon" href="{{ asset('img/favicon.ico') }}" type="image/x-icon">
	<!-- Global stylesheets -->
	<link href="{{ asset('assets/fonts/inter/inter.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/icons/phosphor/styles.min.css') }}" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/css/all.min.css') }}" id="stylesheet" rel="stylesheet" type="text/css">
	<link href="{{ asset('assets/icons/fontawesome/css/all.min.css') }}" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->

	<!-- Core JS files -->
	<script src="{{ asset('assets/demo/demo_configurator.js') }}"></script>
	<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="{{ asset('assets/js/jquery/jquery.min.js') }}"></script>
	<script src="{{ asset('assets/js/vendor/tables/datatables/datatables.min.js') }}"></script>


	{{-- validate --}}
	<script src="{{ asset('assets/js/vendor/validate/jquery.validate.min.js') }}"></script>
	<script src="{{ asset('assets/js/vendor/validate/messages_es.min.js') }}"></script>


	{{-- jquery confirm --}}
	<link rel="stylesheet" href="{{ asset('assets/js/vendor/jquery-confirm/jquery-confirm.min.css') }}">
	<script src="{{ asset('assets/js/vendor/jquery-confirm/jquery-confirm.min.js') }}"></script>

	{{-- plugins extras --}}
	@stack('scriptsHeader')

	<script src="{{ asset('assets/js/app.js') }}"></script>

	<!-- /theme JS files -->
	{{-- @vite(['resources/sass/app.scss', 'resources/js/app.js']) --}}

	<script>

		var block= $.dialog({
            title: '',
            content: 'Procesando solicitud. Por favor, espera.',
            type: 'blue',
            theme: 'modern',
            icon: 'fas fa-spinner fa-spin',
            typeAnimated: true,
            closeIcon: false,
			lazyOpen: true,

        });

		$.validator.setDefaults( {
			submitHandler: function(form) {
				block.open();
				form.submit();
			},
			errorElement: "strong",
			errorPlacement: function ( error, element ) {
				error.addClass( "invalid-feedback" );

				if ( element.prop( "type" ) === "checkbox" ) {
					error.insertAfter( element.next( "label" ) );
				} else {
					error.insertAfter( element );
				}
			},
			highlight: function ( element, errorClass, validClass ) {
				$( element ).addClass( "is-invalid" ).removeClass( "is-valid" );
			},
			unhighlight: function (element, errorClass, validClass) {
				$( element ).addClass( "is-valid" ).removeClass( "is-invalid" );
			}
		} );

		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});
	</script>
</head>
