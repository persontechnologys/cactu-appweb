
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
@include('sections.head')

<body class="bg-dark">

	<!-- Page content -->
	<div class="page-content">

		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">

				<!-- Content area -->
				<div class="content d-flex justify-content-center align-items-center">

                    <div class="container">
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center my-1">
                                        <img src="{{ asset('assets/images/logo_icon_azul.svg') }}" class="h-48px" alt="">
                                    </div>
                                    <p class="mb-0 fw-bold">
                                        @isset($ninio)
                                            {{ $ninio->genero==='Female'?'Bienvenida':'Bienvenido' }}, {{ $ninio->nombres_completos }}
                                        @else
                                            Cartas de CACTU
                                        @endisset
                                    </p>
                                    @isset($ninio)
                                        <form action="{{ route('cartas-ninio.salir') }}" method="POST" class="mt-1">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm p-0 text-muted">
                                                <i class="ph ph-sign-out me-1"></i>Cerrar sesión
                                            </button>
                                        </form>
                                    @endisset
                                </div>
                            </div>
                            <div class="card-body">
                                @include('sections.errors-alert')
                                @yield('content')
                            </div>
                            <div class="card-footer text-muted">
                                <span class="form-text text-center text-muted  ">
                                    © {{ date('Y') }} <a href="https://persontechnology.com/" target="_blank">Person Technology</a>. Todos los derechos reservados.
                                </span>
                            </div>
                        </div>
                        
                    </div>
					

				</div>
				<!-- /content area -->

			</div>
			<!-- /inner content -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->


	<!-- Demo config -->
	@include('sections.config')
	<!-- /demo config -->
    @stack('scripts')
</body>
</html>
