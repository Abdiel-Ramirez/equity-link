<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class InvoiceController extends Controller
{
    public function index()
    {
        $this->authorize('view-invoices');
        return Invoice::all();
    }

    public function store(Request $request)
{
    $this->authorize('upload-invoices');

    // Validación del archivo
    $request->validate([
        'xml' => 'required|file|mimes:xml',
    ]);

    $xmlFile = $request->file('xml');
    $path = $xmlFile->store('invoices');

    try {
        // Parseo del XML
        $xmlContent = simplexml_load_file(storage_path('app/' . $path));
        if (!$xmlContent) {
            return response()->json(['message' => 'XML inválido'], 422);
        }

        $xmlContent->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xmlContent->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $comprobante = $xmlContent->xpath('//cfdi:Comprobante')[0] ?? null;
        $emisor = $xmlContent->xpath('//cfdi:Emisor')[0] ?? null;
        $receptor = $xmlContent->xpath('//cfdi:Receptor')[0] ?? null;
        $timbre = $xmlContent->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;

        if (!$comprobante || !$emisor || !$receptor) {
            return response()->json(['message' => 'XML incompleto: faltan nodos requeridos'], 422);
        }

        $uuid = $timbre ? (string) $timbre['UUID'] : (string) Str::uuid();
        $folio = (string) $comprobante['Folio'] ?: Str::afterLast($uuid, '-');
        $moneda = (string) $comprobante['Moneda'];
        $total = (float) $comprobante['Total'];
        $fechaString = (string) $comprobante['Fecha'];
        $fecha = Carbon::parse($fechaString);
        
        
        // Tipo de cambio
        $tipoCambio = null;
        $fechaDOF = $fecha->copy();
        $fechaParaDOF = $fechaDOF->format('Y-m-d');
        $intentos = 0;

        while ($intentos < 3 && is_null($tipoCambio)) {
            try {
                $response = Http::get("https://sidofqa.segob.gob.mx/dof/sidof/indicadores/158/{$fechaParaDOF}/{$fechaParaDOF}");
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['ListaIndicadores'])) {
                        $tipoCambio = floatval(str_replace(',', '.', $data['ListaIndicadores'][0]['valor']));
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al consultar tipo de cambio para {$fechaParaDOF}: " . $e->getMessage());
            }

            if (is_null($tipoCambio)) {
                // Probar con el día anterior
                $fecha->subDay();
                $fechaParaDOF = $fecha->format('Y-m-d');
                $intentos++;
            }
        }

        if (is_null($tipoCambio)) {
            Log::warning("No se encontró tipo de cambio para la factura con fecha {$fecha->format('Y-m-d')} después de {$intentos} intentos.");
        }


        // Guardar en DB
        $invoice = Invoice::create([
            'uuid' => $uuid,
            'folio' => $folio,
            'fecha' => $fecha,
            'emisor' => (string) $emisor['Rfc'],
            'receptor' => (string) $receptor['Rfc'],
            'moneda' => $moneda,
            'total' => $total,
            'tipo_cambio' => $tipoCambio,
            'xml_path' => $path,
        ]);

        return response()->json([
            'message' => 'Factura guardada correctamente',
            'invoice' => $invoice,
        ]);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Error procesando el XML', 'error' => $e->getMessage()], 500);
    }
}


}
