
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
@include('sections.head')

<body>

	<!-- Main navbar -->
	<div class="navbar navbar-dark navbar-expand-lg navbar-static border-bottom border-bottom-white border-opacity-10">
		<div class="container-fluid">

			<div class="navbar-brand flex-1 flex-lg-0">
				<a href=" @auth {{ route('home') }} @else {{ route('welcome') }} @endauth " class="d-inline-flex align-items-center">
					<img src="{{ asset('assets/images/logo_icon.svg') }}" alt="">
					<img src="{{ asset('assets/images/logo_text_light.svg') }}" class="d-none d-sm-inline-block h-16px ms-3" alt="">
				</a>
			</div>
            
			@auth
                @include('sections.menu-header-auth')
            @else
            <div class="d-flex justify-content-end align-items-center ms-auto">
				<ul class="navbar-nav flex-row">
					<li class="nav-item">
						<a href="{{ route('login') }}" class="navbar-nav-link navbar-nav-link-icon rounded ms-1 {{ Route::is('login')?'active':'' }}">
							<div class="d-flex align-items-center mx-md-1">
							<i class="ph-user-circle"></i>
							<span class="d-none d-md-inline-block ms-2">Ingresar</span>
						</div>
						</a>
					</li>
				</ul>
			</div>
            @endauth

            
		</div>
	</div>
	<!-- /main navbar -->


	<!-- Page content -->
	<div class="page-content">

		<!-- Main sidebar -->
        
		@auth
		@include('sections.menu')
		@endauth
			
		
		<!-- /main sidebar -->


		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">

				<!-- Page header -->
				<div class="page-header page-header-light shadow">
					{{-- <div class="page-header-content d-lg-flex">
						<div class="d-flex">
							<h4 class="page-title mb-0">
								Datatables - <span class="fw-normal">Basic Examples</span>
							</h4>

							<a href="#page_header" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
								<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
							</a>
						</div>

						<div class="collapse d-lg-block my-lg-auto ms-lg-auto" id="page_header">
							<div class="d-sm-flex align-items-center mb-3 mb-lg-0 ms-lg-3">
								<div class="dropdown w-100 w-sm-auto">
									<a href="#" class="d-flex align-items-center text-body lh-1 dropdown-toggle py-sm-2" data-bs-toggle="dropdown" data-bs-display="static">
										<img src="../../../assets/images/brands/tesla.svg" class="w-32px h-32px me-2" alt="">
										<div class="me-auto me-lg-1">
											<div class="fs-sm text-muted mb-1">Customer</div>
											<div class="fw-semibold">Tesla Motors Inc</div>
										</div>
									</a>

									<div class="dropdown-menu dropdown-menu-lg-end w-100 w-lg-auto wmin-300 wmin-sm-350 pt-0">
										<div class="d-flex align-items-center p-3">
											<h6 class="fw-semibold mb-0">Customers</h6>
											<a href="#" class="ms-auto">
												View all
												<i class="ph-arrow-circle-right ms-1"></i>
											</a>
										</div>
										<a href="#" class="dropdown-item active py-2">
											<img src="../../../assets/images/brands/tesla.svg" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Tesla Motors Inc</div>
												<div class="fs-sm text-muted">42 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="../../../assets/images/brands/debijenkorf.svg" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">De Bijenkorf</div>
												<div class="fs-sm text-muted">49 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="../../../assets/images/brands/klm.svg" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Royal Dutch Airlines</div>
												<div class="fs-sm text-muted">18 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="../../../assets/images/brands/shell.svg" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">Royal Dutch Shell</div>
												<div class="fs-sm text-muted">54 users</div>
											</div>
										</a>
										<a href="#" class="dropdown-item py-2">
											<img src="../../../assets/images/brands/bp.svg" class="w-32px h-32px me-2" alt="">
											<div>
												<div class="fw-semibold">BP plc</div>
												<div class="fs-sm text-muted">23 users</div>
											</div>
										</a>
									</div>
								</div>

								<div class="vr d-none d-sm-block flex-shrink-0 my-2 mx-3"></div>

								<div class="d-inline-flex mt-3 mt-sm-0">
									<a href="#" class="status-indicator-container ms-1">
										<img src="../../../assets/images/demo/users/face24.jpg" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-warning"></span>
									</a>
									<a href="#" class="status-indicator-container ms-1">
										<img src="../../../assets/images/demo/users/face1.jpg" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-success"></span>
									</a>
									<a href="#" class="status-indicator-container ms-1">
										<img src="../../../assets/images/demo/users/face3.jpg" class="w-32px h-32px rounded-pill" alt="">
										<span class="status-indicator bg-danger"></span>
									</a>
									<a href="#" class="btn btn-outline-primary btn-icon w-32px h-32px rounded-pill ms-3">
										<i class="ph-plus"></i>
									</a>
								</div>
							</div>
						</div>
					</div> --}}

					<div class="page-header-content d-lg-flex border-top">
						<div class="d-flex">
							@hasSection('breadcrumbs')
								<div class="breadcrumb py-2">
									@yield('breadcrumbs')
								</div>	
							@endif
							

							@hasSection ('breadcrumb_elements')
                                
                            <a href="#breadcrumb_elements" class="btn btn-light align-self-center collapsed d-lg-none border-transparent rounded-pill p-0 ms-auto" data-bs-toggle="collapse">
								<i class="ph-caret-down collapsible-indicator ph-sm m-1"></i>
							</a>
                                
                            @endif

						</div>

						<div class="collapse d-lg-block ms-lg-auto" id="breadcrumb_elements">
							@yield('breadcrumb_elements')
						</div>
					</div>
				</div>
				<!-- /page header -->


				<!-- Content area -->
				<div class="content">
					
					@include('sections.errors-alert')

                    @yield('content')
				</div>
				<!-- /content area -->


				<!-- Footer -->
				@include('sections.footer')
				<!-- /footer -->

			</div>
			<!-- /inner content -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->


	<!-- Notifications -->
	@auth
        {{-- @include('sections.notifications') --}}
    @endauth
	<!-- /notifications -->


	<!-- Demo config -->
	@include('sections.config')
	<!-- /demo config -->


	<form action="" method="POST" id="formEliminar">
        @csrf
        @method("DELETE")
    </form>



	<script>
        
        $('table').on('draw.dt', function() {
			$('[data-bs-popup="tooltip"]').tooltip();
			$('[data-bs-popup="popover"]').popover();
		})
       
        function eliminar(arg){
            var url = $(arg).attr('href');
            var msg = $(arg).data('msg')??'...';
            $.confirm({
                title: 'Está seguro de eliminar.!',
                content: String(msg),
                type: 'blue',
                theme: 'modern',
                icon: 'fa fa-trash fa-2x',
                typeAnimated: true,
                buttons: {
                    SI: {
                        
                        action: function(){
							block.open();
                            $('#formEliminar').attr('action', url);
                            $("#formEliminar").submit();
							
                        }
                    },
                    NO: function () {
                    }
                }
            });
        }
		// cuando se cambia el estado del checkbox en config.blade.php
		// menu_redimensionar checkbox
		// div id= menu_responsive add class sidebar-main-resized when checked and remove when unchecked
		$('input[name="menu_redimensionar"]').on('change', function() {
			if($(this).is(':checked')){
				$('#menu_responsive').addClass('sidebar-main-resized');
			}else{
				$('#menu_responsive').removeClass('sidebar-main-resized');
			}

			// ajax post to route perfil.menu_responsivo
			$.ajax({
				url: "{{ route('perfil.menu_responsivo') }}",
				type: 'POST',
				success: function(response) {
					console.log(response);
				},
				error: function(xhr) {
					console.log(xhr.responseText);
				}
			});
		});

    </script>
	
	@stack('scripts')
</body>
</html>
