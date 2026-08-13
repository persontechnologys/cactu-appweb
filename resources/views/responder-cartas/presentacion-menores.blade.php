@if ($carta->estado=='Respondida')
<span class="fw-semibold">Carta de {{ $carta->tipo }} ha sido {{ $carta->estado }}!</span>
<a href="{{ route('cartas-ninio.index') }}" class="btn btn-link">
    Regresar a mis cartas
</a>
@else

    <form action="{{ route('cartas-ninio.guardar-presentacion-menor') }}" id="formCarta" method="POST" enctype="multipart/form-data">
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
                    <input type="text" name="hola" value="{{ old('hola') }}" id="hola" class="form-control" placeholder="Ingresa el nombre a quién escribes" required>
                </div>

                <div class="mb-3">
                    <label for="escribo" class="form-label">Escribo a nombre de</label>
                    <input type="text" name="escribo" value="{{ old('escribo') }}" id="escribo" class="form-control" placeholder="Ingresa el nombre de quien representa" required>
                </div>

                <div class="mb-3">
                    <label for="mi" class="form-label">mi</label>
                    <input type="text" name="mi" value="{{ old('mi') }}" id="mi" class="form-control" placeholder="Ingresa el parentesco" required>
                </div>

                <div class="mb-3">
                    <label for="queel" class="form-label">, que el</label>
                    <input type="text" name="queel" value="{{ old('queel') }}" id="queel" class="form-control" placeholder="fecha" required>
                </div>

                <div class="mb-3">
                    <label for="cumple" class="form-label">Cumple</label>
                    <input type="text" name="cumple" value="{{ old('cumple') }}" id="cumple" class="form-control" placeholder="Cuantos años" required>
                </div>

                <div class="mb-3">
                    <label for="noSabe" class="form-label">de edad y aún no sabe escribir pero</label>
                    <textarea name="noSabe" id="noSabe" rows="3" class="form-control" required>{{ old('noSabe') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="ademas" class="form-label">Además a</label>
                    <input type="text" name="ademas" value="{{ old('ademas') }}" id="ademas" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="leGusta" class="form-label">le gusta</label>
                    <textarea name="leGusta" id="leGusta" rows="3" class="form-control" required>{{ old('leGusta') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="dondeAprendo" class="form-label">El lugar donde aprende es</label>
                    <textarea name="dondeAprendo" id="dondeAprendo" rows="3" class="form-control" required>{{ old('dondeAprendo') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="gustaAprendes" class="form-label">En este mes aprendimos</label>
                    <textarea name="gustaAprendes" id="gustaAprendes" rows="3" class="form-control" required>{{ old('gustaAprendes') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="mePaso" class="form-label">y lo más importante que nos pasó últimamente es</label>
                    <textarea name="mePaso" id="mePaso" rows="3" class="form-control" required>{{ old('mePaso') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="meGustaria" class="form-label">Lo que esperamos aprender con el programa de ChildFund es</label>
                    <textarea name="meGustaria" id="meGustaria" rows="3" class="form-control" required>{{ old('meGustaria') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="miNombre" class="form-label">Mi nombre es</label>
                    <input type="text" name="miNombre" value="{{ old('miNombre') }}" id="miNombre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="ysoy" class="form-label">y soy</label>
                    <input type="text" name="ysoy" value="{{ old('ysoy') }}" id="ysoy" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="de" class="form-label">de</label>
                    <input type="text" name="de" value="{{ old('de') }}" id="de" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="mifamila" class="form-label">Los otros miembros de nuestra familia son</label>
                    <textarea name="mifamila" id="mifamila" rows="3" class="form-control" required>{{ old('mifamila') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="nuestraPro" class="form-label">Nosotros vivimos en la provincia de</label>
                    <input type="text" name="nuestraPro" value="{{ old('nuestraPro') }}" id="nuestraPro" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="idioma" class="form-label">y el idioma que hablamos es</label>
                    <input type="text" name="idioma" value="{{ old('idioma') }}" id="idioma" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="lugarFavorito" class="form-label">Nuestra provincia tiene sitios muy hermosos, a nosotros nos gusta ir a</label>
                    <input type="text" name="lugarFavorito" value="{{ old('lugarFavorito') }}" id="lugarFavorito" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="comidaTipica" class="form-label">También tenemos comida típica, por ejemplo</label>
                    <textarea name="comidaTipica" id="comidaTipica" rows="3" class="form-control" required>{{ old('comidaTipica') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="ya" class="form-label">y a</label>
                    <input type="text" name="ya" value="{{ old('ya') }}" id="ya" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="comer" class="form-label">le gusta</label>
                    <input type="text" name="comer" value="{{ old('comer') }}" id="comer" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="masMeGusta" class="form-label">De nuestras tradiciones, la que compartimos juntos es</label>
                    <textarea name="masMeGusta" id="masMeGusta" rows="3" class="form-control" required>{{ old('masMeGusta') }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="pregunta" class="form-label">Nos gustaría saber más sobre ti y tu familia y hacerles una pregunta</label>
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
