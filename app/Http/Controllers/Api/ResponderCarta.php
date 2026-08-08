<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carta;
use App\Models\Ninio;
use App\Notifications\EnviarCartaRespondidaGestor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class ResponderCarta extends Controller
{

    public function listaCartas (Request $request) {
        // se usa el niño resuelto por el token Sanctum, no el userId enviado
        // por el cliente (ver SEG-004): de lo contrario cualquier niño
        // autenticado podía leer el listado de cartas de otro niño.
        $ninio = $request->user();


        $cartas = $ninio->cartas()
            ->where('eliminado',false)
            ->orderByRaw("CASE WHEN estado = 'Enviado' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $data = array();
        foreach ($cartas as $carta) {

            array_push($data, [
                'created_at' => $carta->created_at->format('d/m/Y H:i:s'),
                'id' => $carta->id,
                'asunto' => $carta->asunto,
                'tipo_carta_nombre' => $carta->tipo,
                'estado'=>$carta->estado,
                'edad' => Carbon::parse($carta->ninio->fecha_nacimiento)->age??0,
                'archivo'=>$carta->archivo_pdf?route('verarchivopdfporninio',Crypt::encryptString($carta->id)):'no',
                'carta'=>$carta->estado=='Respondida'?route('descargarCartaPdf',Crypt::encryptString($carta->id)):'no',
                'carta_nombre'=>$carta->estado=='Respondida'?'Carta-de-'.$carta->tipo.'-'.$carta->id.'.pdf' :'',
                'eliminado'=>$carta->eliminado
            ]);
        }

        return response()->json(['data' => $data]);
    }

    public function responderCartaPresentacionMayor(Request $request) {

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
        return $this->guardarCarta($request);
        

    }

    public function responderCartaPresentacionMenor(Request $request) {

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
        return $this->guardarCarta($request);
    }

    public function guardarCarta(Request $request){
        $request->validate([
            'id'=>'required',
            'imagen'=>'required',
            'detalle'=>'required'
        ]);
        
        try {
            DB::beginTransaction();
            $carta=Carta::findOrFail($request->id);

            // la carta debe pertenecer al niño autenticado (ver SEG-005)
            if($carta->ninio_id!==$request->user()->id){
                DB::rollBack();
                return response()->json(['error'=>'No autorizado'],403);
            }

            if($carta->estado=='Respondida'){
                return response()->json(['info'=>'Carta ya fue respondida']);
            }else{
                if ($request->hasFile('imagen')) {
                    if ($request->file('imagen')->isValid()) {
                        $extension = $request->imagen->extension();
                        $imageName = $carta->id.'.'.$extension;  
                        $path=Storage::putFileAs('public/cartas/archivo_imagen_ninio',$request->file('imagen'),$imageName);
                        $carta->archivo_imagen_ninio=$path;
                    }
                }
                
                if ($request->hasFile('foto_familia')) {
                    if ($request->file('foto_familia')->isValid()) {
                        $extension_f = $request->foto_familia->extension();
                        $imageName_f = $carta->id.'.'.$extension_f;  
                        $path_f=Storage::putFileAs('public/cartas/archivo_familia_ninio',$request->file('foto_familia'),$imageName_f);
                        $carta->archivo_familia_ninio=$path_f;
                    }
                }
                $carta->detalle=$request->detalle;
                $carta->estado='Respondida';
                $carta->fecha_respondida=Carbon::now();
                $carta->save();
                $carta->gestor->notify(new EnviarCartaRespondidaGestor($carta));
                DB::commit();
                return response()->json(['success'=>'Carta resgitrado exitosamente']);
            }


        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Error al guardar carta: '.$th->getMessage());
            return response()->json(['error'=>'Carta no registrado vuelva intentar']);
        }
    }

    public function responderContestacion(Request $request)
    {
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
        return $this->guardarCarta($request);
    }

    public function responderAgradecimineto(Request $request) {
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
        return $this->guardarCarta($request);
    }

    public function responderIniciada(Request $request) {
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
        return $this->guardarCarta($request);
    }
    public function enviarMensaje() {
        return response()->json(['success'=>'Carta registrado exitosamente']);
    }


    public function eliminarCarta(Request $request) {
        $request->validate([
            'id'=>'required'
        ]);

        $carta=Carta::findOrFail($request->id);

        // la carta debe pertenecer al niño autenticado (ver SEG-005)
        if($carta->ninio_id!==$request->user()->id){
            return response()->json(['error'=>'No autorizado'],403);
        }

        $carta->eliminado=true;
        $carta->save();
        return response()->json(['success'=>'Carta eliminado exitosamente.!']);
    }

}
