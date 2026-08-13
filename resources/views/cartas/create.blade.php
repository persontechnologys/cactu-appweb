@extends('layouts.app')
@section('breadcrumbs')
{{ Breadcrumbs::render('cartas.create') }}
@endsection
@section('content')

<form action="{{ route('cartas.store') }}" method="POST" autocomplete="off" id="formCuentaUser" enctype="multipart/form-data">
    @csrf
    <div class="card">
        <div class="card-body row">
            <input type="hidden" name="ninio" id="id" value="{{ old('ninio') }}" required>
            
            <div class="col-md-6 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-user"></i>
                    </div>
                    <input name="apellidos_nombres" id="txt_apellidos_nombres" value="{{ old('apellidos_nombres') }}" type="text" class="form-control @error('apellidos_nombres') is-invalid @enderror" data-bs-toggle="modal" data-bs-target="#modal_full" required readonly>
                    <label>Seleccionar niño<i class="text-danger">*</i></label>
                    @error('apellidos_nombres')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-user-switch"></i>
                    </div>
                    <select onchange="seleccionarCarta(this)" class="form-select @error('tipo') is-invalid @enderror" name="tipo" id="tipo" required>
                        <option value="Contestación" {{ old('tipo'==='Contestación'?'selected':'') }}>Contestación</option>
                        <option value="Presentación" {{ old('tipo'==='Presentación'?'selected':'') }}>Presentación</option>
                        <option value="Agradecimiento" {{ old('tipo'==='Agradecimiento'?'selected':'') }}>Agradecimiento</option>
                        <option value="Iniciada" {{ old('tipo'==='Iniciada'?'selected':'') }}>Iniciada</option>
                        
                    </select>
                    <label>Tipo de carta<i class="text-danger">*</i></label>
                    @error('tipo')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <div class="col-md-12 mb-1">
                <div class="form-floating form-control-feedback form-control-feedback-start">
                    <div class="form-control-feedback-icon">
                        <i class="ph-article"></i>
                    </div>
                    <input name="asunto" value="{{ old('asunto') }}" type="text" class="form-control @error('asunto') is-invalid @enderror" required>
                    <label>Asunto</label>
                    @error('asunto')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            

            <div class="col-md-6 mb1" id="contenedor-boleta">
                <p>Seleccione la imagen de la boleta.</p>
                <div class="file-loading">
                    <input id="boleta" multiple name="boleta[]" type="file" accept="image/png, image/jpeg, image/jpeg" required>
                </div>
            </div>
            <div class="col-md-6 mb1" id="contenedor-carta">
                <p>Seleccione el pdf de la carta.</p>
                <div class="file-loading">
                    <input id="carta" name="carta" type="file" accept="application/pdf" required>
                </div>
            </div>

        </div>
        <div class="card-footer text-muted">
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </div>
</form>

    <!-- Full width modal -->
	<div id="modal_full" class="modal fade" tabindex="-1">
		<div class="modal-dialog modal-dialog-scrollable modal-full">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Lista de niños activos</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body">
					<div class="table-responsive">
                        {{$dataTable->table()}}
                    </div>
				</div>

				<div class="modal-footer">
					<button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cerrar</button>
				</div>
			</div>
		</div>
	</div>
	<!-- /full width modal -->

@endsection


@push('scriptsHeader')
{{-- fileinput --}}
<link href="{{ asset('assets/js/vendor/uploaders/fileinput/css/fileinput.min.css') }}" media="all" rel="stylesheet" type="text/css" />
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/plugins/buffer.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/plugins/filetype.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/plugins/piexif.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/plugins/sortable.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/fileinput.min.js') }}"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/js/locale/es.js') }}"></script>
<script src="{{ asset('assets/js/vendor/uploaders/fileinput/themes/fa6/theme.min.js') }}"></script>
@endpush




@push('scripts')
    {{$dataTable->scripts()}}
    <script>
        function selecionarNinio(arg){
            $('#modal_full').modal('hide');
            $('#txt_apellidos_nombres').val($(arg).data('nombres_completos'));
            $('#id').val($(arg).data('id'));
        }
        // Comprime una imagen (respetando su orientación EXIF) antes de subirla.
        // El resize del plugin fileinput (abajo) solo afecta la vista previa,
        // no los bytes reales del <input>, así que sin esto se sube la foto
        // original de cámara (varios MB) en cada carta.
        async function comprimirImagenBoleta(file, maxDimension = 1280, calidad = 0.75) {
            try {
                const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
                let { width, height } = bitmap;
                if (width > maxDimension || height > maxDimension) {
                    const ratio = Math.min(maxDimension / width, maxDimension / height);
                    width = Math.round(width * ratio);
                    height = Math.round(height * ratio);
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                canvas.getContext('2d').drawImage(bitmap, 0, 0, width, height);
                bitmap.close();

                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', calidad));
                if (!blob) return file;

                const nombre = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                return new File([blob], nombre, { type: 'image/jpeg', lastModified: Date.now() });
            } catch (e) {
                return file;
            }
        }

        async function comprimirBoletasAntesDeEnviar() {
            const input = document.getElementById('boleta');
            if (!input.files || input.files.length === 0) return;

            const comprimidas = await Promise.all(
                Array.from(input.files).map(file => comprimirImagenBoleta(file))
            );

            const dt = new DataTransfer();
            comprimidas.forEach(file => dt.items.add(file));
            input.files = dt.files;
        }

        $('#formCuentaUser').validate({
            rules:{
                asunto:{
                    maxlength:120
                }
            },
            submitHandler: function(form) {

                $.confirm({
                    title: 'CONFIRMAR TRANSACCIÓN',
                    content: "Esta seguro de enviar carta de "+$('#tipo').find(":selected").val()+" a "+$('#txt_apellidos_nombres').val(),
                    type: 'blue',
                    theme: 'modern',
                    icon: 'fa fa-triangle-exclamation',
                    typeAnimated: true,
                    buttons: {
                        SI: {
                            btnClass: 'btn btn-primary',
                            keys: ['enter'],
                            action: async function(){
                                block.open()
                                await comprimirBoletasAntesDeEnviar();
                                form.submit();

                            }
                        },
                        NO: function () {
                        }
                    }
                });
            }
        });

        $("#boleta").fileinput({
            browseClass: "btn btn-outline-primary",
            mainClass: "d-grid",
            showCaption: false,
            showRemove: false,
            showUpload: false,
            allowedFileExtensions: ["jpg", "jpeg", "png"],
            maxImageWidth: 520,
            maxImageHeight: 340,
            resizePreference: 'height',
            // maxFileCount: 1,
            resizeImage: true,
            resizeIfSizeMoreThan: 1000,
            language: "es",
            theme: "fa6",
            browseLabel: 'Cargar boleta',
            browseIcon: "<i class=\"ph ph-image mx-2\"></i> ",
            showClose: false,
            maxFileSize: 2050,
        });

        $("#carta").fileinput({
            browseClass: "btn btn-outline-success",
            mainClass: "d-grid",
            showCaption: false,
            showRemove: false,
            showUpload: false,
            allowedFileExtensions: ["pdf"],
            resizePreference: 'height',
            maxFileCount: 1,
            resizeIfSizeMoreThan: 1000,
            language: "es",
            theme: "fa6",
            browseLabel: 'Cargar carta',
            browseIcon: "<i class=\"ph ph-file-pdf mx-2\"></i> ",
            showClose: false,
            maxFileSize: 2050,
        });

        function seleccionarCarta(arg){
            if($(arg).val()!='Contestación'){
                $('#contenedor-carta').hide();
            }else{
                $('#contenedor-carta').show();
            }
        }

    </script>
@endpush