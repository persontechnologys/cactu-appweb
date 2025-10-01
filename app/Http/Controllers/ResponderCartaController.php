<?php

namespace App\Http\Controllers;

use App\Models\Carta;
use App\Models\Ninio;
use App\Notifications\EnviarCartaRespondidaGestor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ResponderCartaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($ninio_numero_child)
    {
        
        try {
            // $ninio_decrypt = Crypt::decryptString($ninio_numero_child);
            $ninio=Ninio::where('numero_child',$ninio_numero_child)->firstOrFail();
            $data = array(
                'ninio'=>$ninio,
                'cartas'=>$ninio->cartas->where('estado','Enviado')
            );
            return view('responder-cartas.index',$data);    
        } catch (DecryptException $e) {
         return abort(404);
        }
    }

    public function ver($idcarta,$ninio_numero_child) {
       
        try {
            $idcarta_decrypt = Crypt::decryptString($idcarta);
            $ninio_numero_child_decrypt = Crypt::decryptString($ninio_numero_child);
            $carta=Carta::findOrFail($idcarta_decrypt);
            if($carta->ninio->numero_child===$ninio_numero_child_decrypt){
                return view('responder-cartas.todas-cartas',['carta'=>$carta,'ninio'=>$carta->ninio]);
            }else{
                return abort(404);
            }
            
        } catch (DecryptException $e) {
            return abort(404);
        }
    }

    public function verArchivo($idCarta,$tipo)
    {
        try {
            $carta=Carta::findOrFail(Crypt::decryptString($idCarta));
            switch ($tipo) {
                case 'foto':
                    return Storage::get($carta->archivo_imagen);        
                    break;
                case 'pdf':
                    return Storage::response($carta->archivo_pdf);        
                    break;
                default:
                    return '';
                    break;
            }
        } catch (DecryptException $th) {
            return abort(404);
        }
    }

    public function guardarContestacion(Request $request) {
        $request->validate([
            'id'=>'required',
            'imagen'=>'required',
            'detalle'=>'required'
        ]);
        $detalle= '<p><b>Hola</b> '. $request->nombre_patrocinador.
                    ', agradesco por la '.$request->agradezco_por.'</p>'.
                    '<p>Te cuento que '.$request->te_cuento_que.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$request->pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$request->detalle.'</p>';
        $request['detalle']=$detalle;
        return $this->guardarArchivo($request);
    }

    public function guardarAgradecimiento(Request $request) {
        $request->validate([
            'id'=>'required',
            'imagen'=>'required',
            'detalle'=>'required'
        ]);
        $detalle= '<p><b>Hola</b> '. $request->nombre_patrocinador.
                    ', Agradezco por el valor enviado de '.$request->agradezco_por.'</p>'.
                    '<p>Tu regalo lo voy a usar para '.$request->regalo_usar_para.'</p>'.
                    '<p>Te cuento que '.$request->te_cuento_que.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$request->pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$request->detalle.'</p>';

        $request['detalle']=$detalle;
        return $this->guardarArchivo($request);
    }
    public function guardarIniciada(Request $request)  {
        $request->validate([
            'id'=>'required',
            'imagen'=>'required',
            'detalle'=>'required'
        ]);
        $detalle= '<p><b>Hola</b> '. $request->nombre_patrocinador.'</p>'.
                    '<p>Te cuento que '.$request->te_cuento_que.'</p>'.
                    '<p>Gracias a ChildFund, en CACTU aprendí '.$request->en_cactu_aprendi.'</p>'.
                    '<p><b>Es hora de hacer una pregunta.</b></p>'.
                    '<p>¿ '.$request->pregunta_al_patrocinador.' ?</p>'.
                    '<p><b>Aquí mi despedida.</b></p>'.
                    '<p>'.$request->detalle.'</p>';

        $request['detalle']=$detalle;
        return $this->guardarArchivo($request);
   }
    public function guardarArchivo(Request $request)
    {
        try {
            DB::beginTransaction();
            $carta=Carta::findOrFail(Crypt::decryptString($request->id));
            
            $this->authorize('responderCartaXninio',$carta);
            
            $url_foto_ninio=$this->guardarImagenBase64($carta->id.'.png',$request->input('imagen'),'foto_personal');

            if($request->foto_familia){
                $carta->archivo_familia_ninio=$this->guardarImagenBase64($carta->id.'.png',$request->input('foto_familia'),'foto_familia');
            }

            $carta->archivo_imagen_ninio=$url_foto_ninio;
            $carta->detalle=$request->detalle;
            $carta->estado='Respondida';
            $carta->fecha_respondida=Carbon::now();
            $carta->save();
            $carta->gestor->notify(new EnviarCartaRespondidaGestor($carta));
            DB::commit();
            Session::flash('success','La respuesta a su carta ha sido enviada satisfactoriamente.');
            return redirect()->route('cartas-ninio.index',Crypt::encryptString($carta->ninio->numero_child));
        } catch (DecryptException $e) {
            DB::rollBack();
            return abort(404);
        }
    }

    public function guardarImagenBase64($nombreImagen,$imagenBase64,$tipo) {
        $tempFolderPath='temp';
        if (!Storage::exists($tempFolderPath)) {
            Storage::makeDirectory($tempFolderPath);
        }
        $tempDirectoryPath = Storage::path($tempFolderPath);
        list(, $imagenCodificada) = explode(';', $imagenBase64);
        list(, $imagenCodificada) = explode(',', $imagenCodificada);
        $imagenDecodificada = base64_decode($imagenCodificada);
        // Guardar la imagen decodificada en una ubicación temporal
        $rutaTempImagen = $tempDirectoryPath . '/' . $nombreImagen;
        file_put_contents($rutaTempImagen, $imagenDecodificada);
        
        // Mover la imagen al almacenamiento de Laravel
        
        if($tipo==='foto_personal'){
            $rutaImagen = Storage::putFileAs('public/cartas/archivo_imagen_ninio', new File($rutaTempImagen), $nombreImagen);
        }else{
            $rutaImagen = Storage::putFileAs('public/cartas/archivo_familia_ninio', new File($rutaTempImagen), $nombreImagen);
        }
        
        
        // Eliminar la imagen temporal
        unlink($rutaTempImagen);
        
        return $rutaImagen;
    }


    public function guardarPresentacionMayor(Request $request) {

        $request->validate([
            'imagen'=>'required',
            'foto_familia'=>'required',
            'id'=>'required',
            'hola'=>'required',
            'soy'=>'required',
            'meDicen'=>'required',
            'edad'=>'required',
            'miMejorAmigo'=>'required',
            'esMejorAmigo'=>'required',
            'loquehago'=>'required',
            'miSueno'=>'required',
            'dondeAprendo'=>'required',
            'gustaAprendes'=>'required',
            'mePaso'=>'required',
            'meGustaria'=>'required',
            'miFamilia'=>'required',
            'nuestraPro'=>'required',
            'idioma'=>'required',
            'lugarFavorito'=>'required',
            'comidaTipica'=>'required',
            'comer'=>'required',
            'masMeGusta'=>'required',
            'pregunta'=>'required',
            'despedida'=>'required'
        ]);
        
       $detalle= '<p><b>Hola '. $request->hola.
        '</b></p><p> Soy '.$request->soy.
        ' y mis amigos me dicen '.$request->meDicen.
        '</p><p> tengo '.$request->edad.
        '</p><p> Mi mejor amigo se llama '.$request->miMejorAmigo.
        ' es mi mejor amigo porque, '.$request->esMejorAmigo.
        '</p><p> Lo que maś me gusta hacer es, '.$request->loquehago.
        '</p><p> Cuando sea grande mi sueño es '.$request->miSueno.
        '</p><p> El lugar donde aprendo es, '.$request->dondeAprendo.
        ' lo que me gusta aprender es, '.$request->gustaAprendes.
        '</p><p> Lo más importante que me pasó últimamente es '.$request->mePaso.
        '</p><p> Lo que me gustaría aprender en el programa de ChildFund es '.$request->meGustaria.
        '</p><p> Esta es mi famila '.$request->miFamilia.
        '</p><p><b>También quiero contarte del lugar donde vivo.'.
        '</b></p><p> Nuestra provincia se llama '.$request->nuestraPro.
        ' y el idioma que hablamos es '.$request->idioma.
        '</p><p>Donde nosotros vivimos hay sitios muy hermosos,'.
        ' mi lugar favorito es '.$request->lugarFavorito.
        '</p><p>También tenemos comida típica, por ejemplo:'.
        '</p><p> La comida típica de esta región es '.$request->comidaTipica.
        ' y a mi me gusta comer '.$request->comer.
        '</p><p> De nuestras tradiciones, lo que más me gusta es '.$request->masMeGusta.
        '</p><p><b> Me gustaría hacerte una pregunta</b></p><p>¿ '.$request->pregunta.
        '?</p><p> Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida </p><p>'.$request->despedida.
        '</p>';

        $request['detalle']=$detalle;
        return $this->guardarArchivo($request);
        

    }

    public function guardarPresentacionMenor(Request $request) {

        $request->validate([
            'imagen'=>'required',
            'foto_familia'=>'required',
            'id'=>'required',
            'hola'=>'required',
            'escribo'=>'required',
            'mi'=>'required',
            'queel'=>'required',
            'cumple'=>'required',
            'noSabe'=>'required',
            'ademas'=>'required',
            'leGusta'=>'required',
            'dondeAprendo'=>'required',
            'gustaAprendes'=>'required',
            'mePaso'=>'required',
            'meGustaria'=>'required',
            'miNombre'=>'required',
            'ysoy'=>'required',
            'de'=>'required',
            'mifamila'=>'required',
            'nuestraPro'=>'required',
            'idioma'=>'required',
            'lugarFavorito'=>'required',
            'comidaTipica'=>'required',
            'ya'=>'required',
            'comer'=>'required',
            'masMeGusta'=>'required',
            'pregunta'=>'required',
            'despedida'=>'required',
        ]);
    
        $detalle=
        '<p><b>Hola '.$request->hola.'</b></p>'.
        '<p>Escribo a nombre de '.$request->escribo.''.
        ' mi '.$request->mi.' '.
        ', que el'.$request->queel.'</p>'.
        '<p>Cumple '.$request->cumple.' '.
        'de edad y aún no sabe escribir pero '.$request->noSabe.'</p>'.
        '<p>Además a '.$request->ademas.' '.
        'le gusta'.$request->leGusta.'</p>'.
        '<p>El lugar donde aprende es '.$request->dondeAprendo.'</p>'.
        '<p>En este mes aprendimos '.$request->gustaAprendes.' '.
        'y lo más importante que nos pasó últimamente es '.$request->mePaso.'</p>'.
        '<p>Lo que esperamos aprender con el programa de ChildFund es'.$request->meGustaria.'</p>'.
        '<p>Mi nombre es '.$request->miNombre.' '.
        'y soy'.$request->ysoy.' '.
        'de'.$request->de.'</p>'.
        '<p>Los otros miembros de nuestra familia son '.$request->mifamila.'</p>'.
        '<p>Nosotros vivimos en la provincia de '.$request->nuestraPro.' '.
        'y el idioma que hablamos es'.$request->idioma.'</p>'.
        '<p>Nuestra provincia tiene sitios muy hermosos, a nosotros nos gusta ir a '.$request->lugarFavorito.'</p>'.
        '<p>También tenemos comida típica, por ejemplo '.$request->comidaTipica.' '.
        'y a '.$request->ya.' '.
        'le gusta'.$request->comer.'</p>'.
        '<p>De nuestras tradiciones, la que compartimos juntos es '.$request->masMeGusta.'</p>'.
        '<p>Nos gustaría saber más sobre ti y tu familia y hacerles una pregunta</p><p><b>¿ '.$request->pregunta.'?</b></p>'.
        '<p>Cuéntale por qué quisieras que te conteste tu patrocinador, envíale un mensaje de despedida:</p><p> '.$request->despedida.'</p>';
        
        $request['detalle']=$detalle;
        return $this->guardarArchivo($request);
    }
    

}
