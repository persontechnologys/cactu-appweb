<div class="navbar navbar-sm navbar-footer border-top">
    <div class="container-fluid">
        <span>&copy; {{ date('Y') }} <a href="https://persontechnology.net/" target="_blank">Person Technology</a>, Todos los derechos reservados.</span>

        <ul class="nav">
            <li class="nav-item">
                <a href="{{ route('privacidad') }}" class="navbar-nav-link navbar-nav-link-icon rounded">
                    <div class="d-flex align-items-center mx-md-1">
                        <i class="ph-lifebuoy"></i>
                        <span class="d-none d-md-inline-block ms-2">Privacidad</span>
                    </div>
                </a>
            </li>
            {{-- <li class="nav-item ms-md-1">
                <a href="#!" class="navbar-nav-link navbar-nav-link-icon rounded">
                    <div class="d-flex align-items-center mx-md-1">
                        <i class="ph-file-text"></i>
                        <span class="d-none d-md-inline-block ms-2">Docs</span>
                    </div>
                </a>
            </li> --}}
            <li class="nav-item ms-md-1">
                <a href="{{ asset('apk/CactuApp.apk') }}" class="navbar-nav-link navbar-nav-link-icon text-success bg-success bg-opacity-10 fw-semibold rounded">
                    <div class="d-flex align-items-center mx-md-1">
                        <i class="ph ph-device-mobile"></i>
                        <span class="d-none d-md-inline-block ms-2">DESCARGAR APLICACIÓN ANDROID</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
</div>