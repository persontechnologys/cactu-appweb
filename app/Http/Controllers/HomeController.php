<?php

namespace App\Http\Controllers;

use App\Http\Clases\ValidadorEc;
use App\Models\Carta;
use App\Models\Comunidad;
use App\Models\Ninio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        $esAdministrador = $user->hasRole('ADMINISTRADOR');

        $cartasPorResponder = ($esAdministrador ? Carta::query() : $user->cartas())
            ->where('estado', 'Enviado')->count();
        $cartasRespondidas = ($esAdministrador ? Carta::query() : $user->cartas())
            ->where('estado', 'Respondida')->count();

        $totalNinios = ($esAdministrador ? Ninio::query() : $user->ninios())->count();
        $niniosSinCarta = ($esAdministrador ? Ninio::query() : $user->ninios())
            ->whereDoesntHave('cartas')->count();

        $niniosPorComunidad = ($esAdministrador ? Comunidad::query() : $user->comunidades())
            ->withCount('ninios')->get();

        return view('home', compact(
            'cartasPorResponder',
            'cartasRespondidas',
            'totalNinios',
            'niniosSinCarta',
            'niniosPorComunidad'
        ));
    }

    public function validarec(Request $request)
    {
        $validatorEC = new ValidadorEc();
        $res= $validatorEC->validarIdentificacion($request->identificacion);
        return json_encode($res);
    }
}
