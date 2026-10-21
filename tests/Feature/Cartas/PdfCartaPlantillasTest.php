<?php

namespace Tests\Feature\Cartas;

use App\Models\Boleta;
use App\Models\Carta;
use App\Models\Ninio;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Plantillas PDF de cartas (cartas.verpdf → pdf-*): bloque de boletas en
 * filas de 2 sin desbordar, y fotos como <img> que no se deforman.
 *
 * Renderiza el HTML que recibe wkhtmltopdf, sin generar el PDF.
 * Usa modelos sin guardar, así no necesita base de datos.
 */
class PdfCartaPlantillasTest extends TestCase
{
    private const TIPOS = ['Iniciada', 'Agradecimiento', 'Contestación', 'Presentación'];

    private function cartaDePrueba(string $tipo, int $cantidadBoletas): Carta
    {
        Storage::fake('local');
        Storage::put('public/cartas/archivo_imagen/1.jpg', 'x');
        Storage::put('public/cartas/archivo_imagen_ninio/1.jpg', 'x');
        Storage::put('public/cartas/archivo_familia_ninio/1.jpg', 'x');

        $boletas = collect($cantidadBoletas > 0 ? range(1, $cantidadBoletas) : [])->map(function (int $i) {
            Storage::put("public/cartas/boletas/$i.jpg", 'x');

            return (new Boleta())->forceFill(['archivo_imagen' => "public/cartas/boletas/$i.jpg"]);
        });

        $carta = (new Carta())->forceFill([
            'tipo' => $tipo,
            'detalle' => '<p>Texto de prueba</p>',
            'fecha_respondida' => now(),
            'archivo_imagen' => 'public/cartas/archivo_imagen/1.jpg',
            'archivo_imagen_ninio' => 'public/cartas/archivo_imagen_ninio/1.jpg',
            'archivo_familia_ninio' => 'public/cartas/archivo_familia_ninio/1.jpg',
        ]);
        $carta->setRelation('ninio', new Ninio(['numero_child' => '00000000']));
        $carta->setRelation('gestor', (new User())->forceFill(['provincia' => 'COTOPAXI']));
        $carta->setRelation('boletas', $boletas);

        return $carta;
    }

    private function renderizar(Carta $carta): string
    {
        return view('cartas.verpdf', ['carta' => $carta, 'title' => 'prueba'])->render();
    }

    /** @return string[] HTML de cada fila del bloque de boletas */
    private function filasDeBoletas(string $html): array
    {
        $partes = explode('class="boletas-pdf-fila"', $html);
        array_shift($partes);

        return array_map(fn (string $parte) => strstr($parte, '</table>', true), $partes);
    }

    public function test_la_imagen_principal_cuenta_en_las_filas_de_dos_y_ninguna_fila_tiene_mas_de_dos(): void
    {
        foreach (self::TIPOS as $tipo) {
            // 1 imagen principal + 2 boletas = 3 imágenes → filas de 2 y 1.
            // Antes la primera fila quedaba con 3 y la tercera se salía de la hoja.
            $filas = $this->filasDeBoletas($this->renderizar($this->cartaDePrueba($tipo, 2)));

            $this->assertCount(2, $filas, "Tipo $tipo");
            $this->assertSame(2, substr_count($filas[0], '<img'), "Tipo $tipo");
            $this->assertSame(1, substr_count($filas[1], '<img'), "Tipo $tipo");
        }
    }

    public function test_cada_fila_de_boletas_es_un_bloque_que_no_se_corta_entre_hojas(): void
    {
        $html = $this->renderizar($this->cartaDePrueba('Contestación', 9));

        $this->assertCount(5, $this->filasDeBoletas($html));
        $this->assertMatchesRegularExpression('/\.boletas-pdf-fila\s*\{[^}]*page-break-inside:\s*avoid/', $html);
        // El título va dentro de la primera fila, así no queda solo al final de una hoja.
        $this->assertSame(1, substr_count($html, '>Boletas</span>'));
        $filas = $this->filasDeBoletas($html);
        $this->assertStringContainsString('>Boletas</span>', $filas[0]);
    }

    public function test_sin_imagenes_no_se_muestra_el_bloque_de_boletas(): void
    {
        $carta = $this->cartaDePrueba('Agradecimiento', 0);
        $carta->archivo_imagen = null;

        $this->assertStringNotContainsString('>Boletas</span>', $this->renderizar($carta));
    }

    public function test_las_fotos_del_ninio_son_img_y_no_fondos_con_tamano_forzado(): void
    {
        foreach (self::TIPOS as $tipo) {
            $html = $this->renderizar($this->cartaDePrueba($tipo, 1));

            $this->assertStringNotContainsString('background-size: 300px 400px', $html, "Tipo $tipo");
            $this->assertStringNotContainsString('archivo_imagen_ninio/1.jpg)', $html, "Tipo $tipo");
            $this->assertMatchesRegularExpression('#<img src="[^"]*archivo_imagen_ninio/1\.jpg"#', $html, "Tipo $tipo");
        }

        $presentacion = $this->renderizar($this->cartaDePrueba('Presentación', 1));
        $this->assertMatchesRegularExpression('#<img src="[^"]*archivo_familia_ninio/1\.jpg"#', $presentacion);
    }

    public function test_la_imagen_de_reemplazo_apunta_a_un_archivo_que_existe(): void
    {
        foreach (self::TIPOS as $tipo) {
            $html = $this->renderizar($this->cartaDePrueba($tipo, 0));

            $this->assertStringNotContainsString('images/no-carta-imagen.png', $html, "Tipo $tipo");
        }
        $this->assertFileExists(public_path('img/no_carta_imagen.png'));
    }

    public function test_fotos_y_boletas_van_sin_fondo_de_color_con_marco_y_titulo_centrado(): void
    {
        $colores = ['Iniciada' => '#18b2ce', 'Agradecimiento' => '#3fa855', 'Contestación' => '#26b686', 'Presentación' => '#cbd818'];

        foreach ($colores as $tipo => $color) {
            $html = $this->renderizar($this->cartaDePrueba($tipo, 1));

            // Título "Aquí está mi foto" fuera de la imagen, con el color de la plantilla.
            $this->assertMatchesRegularExpression('~background: '.preg_quote($color, '~').';[^>]*>Aquí está mi foto</span>~', $html, "Tipo $tipo");
            // La foto va en un marco con sombra, ya no como fondo de una celda.
            $this->assertMatchesRegularExpression('#box-shadow:[^>]*>\s*<img src="[^"]*archivo_imagen_ninio/1\.jpg"#', $html, "Tipo $tipo");
            // Las boletas van sin fondo de color.
            $this->assertMatchesRegularExpression('#table\.boletas-pdf-tabla\s*\{[^}]*background: none#', $html, "Tipo $tipo");
        }
    }
}
