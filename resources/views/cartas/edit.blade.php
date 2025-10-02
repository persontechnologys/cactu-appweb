@extends('layouts.app')

@section('breadcrumbs')
{{ Breadcrumbs::render('cartas.edit',$carta) }}
@endsection

@section('content')
<form action="{{ route('cartas.update',$carta) }}" method="post" id="formCuentaUser">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="card-header">
            Actualizar carta de {{ $carta->tipo }}
        </div>
        <div class="card-body">
            <textarea name="detalle" id="detalle" rows="10" cols="80" required>{{ $carta->detalle }}</textarea>
        </div>
        <div class="card-footer text-muted">
            <button type="submit" class="btn btn-primary">Actualizar</button>
            <a href="{{ route('cartas.show', $carta) }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </div>
</form>
@endsection

@push('scriptsHeader')
<script src="{{ asset('assets/js/vendor/editors/ckeditor/ckeditor_classic.js') }}"></script>
@endpush
@push('scripts')
<script>
   ClassicEditor.create(document.querySelector('#detalle')).catch(error => {
        console.error(error);
    });

    $('#formCuentaUser').validate({
            submitHandler: function(form) {
                
                $.confirm({
                    title: 'CONFIRMAR ACTUALIZACIÓN',
                    content: "Esta seguro de actualizar carta de {{ $carta->tipo }}",
                    type: 'blue',
                    theme: 'modern',
                    icon: 'fa fa-triangle-exclamation',
                    typeAnimated: true,
                    buttons: {
                        SI: {
                            btnClass: 'btn btn-primary',
                            keys: ['enter'],
                            action: function(){
                                block.open()
                                form.submit();
                                
                            }
                        },
                        NO: function () {
                        }
                    }
                });
            }
        });
</script>
@endpush