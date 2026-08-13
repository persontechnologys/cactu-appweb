@if ($carta->estado=='Respondida')
<span class="fw-semibold">Carta de {{ $carta->tipo }} ha sido {{ $carta->estado }}!</span>
<a href="{{ route('cartas-ninio.index') }}" class="btn btn-link">
    Regresar a mis cartas
</a>
@else

    <form action="{{ route('cartas-ninio.guardar-presentacion-mayor') }}" id="formCarta" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="imagen" value="{{ old('imagen') }}" id="inputImagen">
        <input type="hidden" name="foto_familia" value="{{ old('foto_familia') }}" id="foto_familia">
        <input type="hidden" name="id" value="{{ Crypt::encryptString($carta->id) }}">
        <input type="hidden" name="ninio" value="{{ Crypt::encryptString($ninio->numero_child) }}">

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Carta de {{ $carta->tipo }} {{ $edad }}</h5>
            </div>
            <div class="card-body">
                <p class="mb-3"><strong>Asunto:</strong> {{ $carta->asunto }}</p>

                <div class="mb-3">
                    <label for="hola" class="form-label">Hola</label>
                    <input type="text" name="hola" value="{{ old('hola') }}" id="hola" autofocus class="form-control" placeholder="Ingresa el nombre de tu patrocinador" required>
                </div>

                <div class="mb-3">
                    <label for="soy" class="form-label">Soy</label>
                    <input type="text" name="soy" value="{{ old('soy') }}" id="soy" class="form-control" placeholder="Ingresa tu nombre" required>
                </div>

                <div class="mb-3">
                    <label for="meDicen" class="form-label">y mis amigos me dicen</label>
                    <input type="text" name="meDicen" value="{{ old('meDicen') }}" id="meDicen" class="form-control" placeholder="Como te dicen" required>
                </div>

                <div class="mb-3">
                    <label for="edad" class="form-label">tengo</label>
                    <input type="text" name="edad" value="{{ old('edad') }}" id="edad" class="form-control" placeholder="Ingresa tu edad Ejm: 11 años" required>
                </div>

                <div class="mb-3">
                    <label for="miMejorAmigo" class="form-label">Mi mejor amigo se llama</label>
                    <input type="text" name="miMejorAmigo" value="{{ old('miMejorAmigo') }}" id="miMejorAmigo" class="form-control" placeholder="Como se llama tu mejor amigo" required>
                </div>

                <div class="mb-3">
                    <label for="esMejorAmigo" class="form-label">es mi mejor amigo porque,</label>
                    <textarea name="esMejorAmigo" id="esMejorAmigo" rows="3" class="form-control" required>{{ old('esMejorAmigo') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="loquehago" class="form-label">Lo que más me gusta hacer es,</label>
                    <textarea name="loquehago" id="loquehago" rows="3" class="form-control" required>{{ old('loquehago') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="miSueno" class="form-label">Cuando sea grande mi sueño es</label>
                    <textarea name="miSueno" id="miSueno" rows="3" class="form-control" required>{{ old('miSueno') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="dondeAprendo" class="form-label">El lugar donde aprendo es,</label>
                    <textarea name="dondeAprendo" id="dondeAprendo" rows="3" class="form-control" required>{{ old('dondeAprendo') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="gustaAprendes" class="form-label">lo que me gusta aprender es,</label>
                    <textarea name="gustaAprendes" id="gustaAprendes" rows="3" class="form-control" required>{{ old('gustaAprendes') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="mePaso" class="form-label">Lo más importante que me pasó últimamente es</label>
                    <textarea name="mePaso" id="mePaso" rows="3" class="form-control" required>{{ old('mePaso') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="meGustaria" class="form-label">Lo que me gustaría aprender en el programa de ChildFund es</label>
                    <textarea name="meGustaria" id="meGustaria" rows="3" class="form-control" required>{{ old('meGustaria') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="miFamilia" class="form-label">Esta es mi familia</label>
                    <textarea name="miFamilia" id="miFamilia" rows="3" class="form-control" required>{{ old('miFamilia') }}</textarea>
                </div>

                <p class="text-muted mb-2">También quiero contarte del lugar donde vivo</p>

                <div class="mb-3">
                    <label for="nuestraPro" class="form-label">Nuestra provincia se llama</label>
                    <input type="text" name="nuestraPro" value="{{ old('nuestraPro') }}" id="nuestraPro" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="idioma" class="form-label">y el idioma que hablamos es</label>
                    <input type="text" name="idioma" value="{{ old('idioma') }}" id="idioma" class="form-control" required>
                </div>

                <p class="text-muted mb-2">Donde nosotros vivimos hay sitios muy hermosos,</p>

                <div class="mb-3">
                    <label for="lugarFavorito" class="form-label">mi lugar favorito es</label>
                    <input type="text" name="lugarFavorito" value="{{ old('lugarFavorito') }}" id="lugarFavorito" class="form-control" required>
                </div>

                <p class="text-muted mb-2">También tenemos comida típica, por ejemplo</p>

                <div class="mb-3">
                    <label for="comidaTipica" class="form-label">La comida típica de esta región es</label>
                    <textarea name="comidaTipica" id="comidaTipica" rows="3" class="form-control" required>{{ old('comidaTipica') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="comer" class="form-label">y a mi me gusta comer</label>
                    <input type="text" name="comer" value="{{ old('comer') }}" id="comer" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="masMeGusta" class="form-label">De nuestras tradiciones, lo que más me gusta es</label>
                    <textarea name="masMeGusta" id="masMeGusta" rows="3" class="form-control" required>{{ old('masMeGusta') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="pregunta" class="form-label">Me gustaría hacerte una pregunta</label>
                    <textarea name="pregunta" id="pregunta" rows="3" class="form-control" required>{{ old('pregunta') }}</textarea>
                </div>

                <div class="mb-0">
                    <label for="despedida" class="form-label">Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida</label>
                    <textarea name="despedida" id="despedida" rows="3" class="form-control" required>{{ old('despedida') }}</textarea>
                </div>
            </div>

            <div class="card-footer d-sm-flex justify-content-sm-between align-items-sm-center py-sm-2">
                <div class="btn-group w-100 w-sm-auto">
                    <button type="button" class="btn btn-link flex-column" onclick="abrirModal(this)">
                        <div class="status-indicator-container">
                            <img id="imgId" src="{{ old('imagen',asset('assets/images/camara.png')) }}" class="w-32px h-32px rounded-pill" alt="">
                            <span class="status-indicator bg-success"></span>
                        </div>
                        Tomar foto personal
                    </button>

                    <button type="button" class="btn btn-link flex-column" onclick="abrirModalFamilia(this)">
                        <div class="status-indicator-container">
                            <img id="imgIdFamilia" src="{{ old('foto_familia',asset('assets/images/camara.png')) }}" class="w-32px h-32px rounded-pill" alt="">
                            <span class="status-indicator bg-success"></span>
                        </div>
                        Tomar foto con familia
                    </button>
                </div>

                <div class="hstack gap-2 mt-3 mt-sm-0">
                    <button type="submit" class="btn btn-primary w-100 w-sm-auto">
                        Responder
                    </button>
                    <a href="{{ route('cartas-ninio.index') }}" class="btn btn-danger w-100 w-sm-auto">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="staticBackdropLabel">Tomar foto personal</h5>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-4x3">
                    <video class="embed-responsive-item" id="videoId" autoplay="true"></video>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="tomarFoto(this)" >TOMAR FOTO PERSONAL</button>
                <button type="button" class="btn btn-danger" onclick="cerrarModal(this);">CANCELAR</button>
            </div>
          </div>
        </div>
    </div>

    <div class="modal fade" id="staticBackdropFamilia" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel2" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="staticBackdropLabel2">Tomar foto con familia</h5>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-4x3">
                    <video class="embed-responsive-item" id="videoIdFamilia" autoplay="true"></video>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="tomarFotoFamilia(this)" >TOMAR FOTO</button>
                <button type="button" class="btn btn-danger" onclick="cerrarModalFamilia(this);">CANCELAR</button>
            </div>
          </div>
        </div>
    </div>

    @push('scriptsHeader')
    <script src="{{ asset('assets/js/jslib-html5-camera-photo.min.js') }}"></script>
    @endpush
    @push('scripts')


    <script>


        let videoElement = document.getElementById('videoId');
        let imgElement = document.getElementById('imgId');
        var FACING_MODES = JslibHtml5CameraPhoto.FACING_MODES;
        var cameraPhoto = new JslibHtml5CameraPhoto.default(videoElement);
        var estado_camara=false;

        let videoElementFamilia = document.getElementById('videoIdFamilia');
        let imgElementFamilia = document.getElementById('imgIdFamilia');
        var FACING_MODES_FAMILIA = JslibHtml5CameraPhoto.FACING_MODES;
        var cameraPhotoFamilia = new JslibHtml5CameraPhoto.default(videoElementFamilia);
        var estado_camara_familia=false;

        function startCamera(){

            cameraPhoto.startCamera(FACING_MODES.ENVIRONMENT)
            .then(() => {
                console.log('Camera started !');
                estado_camara=true;
            })
            .catch((error) => {
                alert('Encontramos un error con la camara del dispositivo.')
                console.error('Camera not started!', error);
            });
        }

        function startCameraFamilia(){
            cameraPhotoFamilia.startCamera(FACING_MODES_FAMILIA.ENVIRONMENT)
            .then(() => {
                console.log('Camera started !');
                estado_camara_familia=true;
            })
            .catch((error) => {
                alert('Encontramos un error con la camara del dispositivo.')
                console.error('Camera not started!', error);
            });
        }


        function abrirModal(){
            startCamera()
            if(estado_camara){
                $('#staticBackdrop').modal('show');
            }else{
                $('#staticBackdrop').modal('hide');
            }

        }

        function abrirModalFamilia(){
            startCameraFamilia()
            if(estado_camara_familia){
                $('#staticBackdropFamilia').modal('show');
            }else{
                $('#staticBackdropFamilia').modal('hide');
            }
        }


        function cerrarModal(arg){
            stopCamera()
            $('#staticBackdrop').modal('hide');
        }

        function cerrarModalFamilia(arg){
            stopCameraFamilia()
            $('#staticBackdropFamilia').modal('hide');
        }

        function tomarFoto(){
            takePhoto();
            stopCamera();
            $('#staticBackdrop').modal('hide');
        }

        function tomarFotoFamilia(){
            takePhotoFamilia();
            stopCameraFamilia();
            $('#staticBackdropFamilia').modal('hide');
        }

        // function called by the buttons.
        function takePhoto () {
            const config = {};
            let dataUri = cameraPhoto.getDataUri(config);
            imgElement.src = dataUri;
            var inputImagen = document.getElementById("inputImagen");
            inputImagen.value = dataUri;

        }

        function takePhotoFamilia () {
            const config = {};
            let dataUri = cameraPhotoFamilia.getDataUri(config);
            imgElementFamilia.src = dataUri;
            var inputImagen = document.getElementById("foto_familia");
            inputImagen.value = dataUri;

        }

        function stopCamera () {
            cameraPhoto.stopCamera()
            .then(() => {
                console.log('Camera stoped!');
            })
            .catch((error) => {
                console.log('No camera to stop!:', error);
            });
        }
        function stopCameraFamilia () {
            cameraPhotoFamilia.stopCamera()
            .then(() => {
                console.log('Camera stoped!');
            })
            .catch((error) => {
                console.log('No camera to stop!:', error);
            });
        }


        $('#formCarta').validate({

            submitHandler: function(form) {

                $.confirm({
                    title: 'CONFIRMAR',
                    content: "Esta seguro de responder carta de {{ $carta->tipo }}.",
                    type: 'blue',
                    theme: 'modern',
                    icon: 'fa fa-triangle-exclamation',
                    typeAnimated: true,
                    escapeKey: 'NO',
                    buttons: {
                        SI: {
                            btnClass: 'btn btn-primary',
                            keys: ['enter'],
                            action: function(){


                                block.open()
                                form.submit()
                            }
                        },
                        NO: function () {

                        }
                    }
                });
            }
        });


        // $(".form-control").val('ola')
    </script>
    @endpush

@endif
