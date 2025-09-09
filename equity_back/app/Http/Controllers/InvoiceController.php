<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


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

        // Validar que se reciba un archivo XML
        $request->validate([
            'xml' => 'required|file|mimes:xml',
        ]);

        $xmlFile = $request->file('xml');

        // Guardar en storage/app/invoices/
        $path = $xmlFile->store('invoices');
        try {
            $xmlContent = simplexml_load_file(storage_path('app/' . $path));
            $xmlContent->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
            $xmlContent->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al leer el XML: ' . $e->getMessage()
            ], 400);
        }


        // Extraer datos
        $comprobante = $xmlContent->xpath('//cfdi:Comprobante')[0];
        $emisor = $xmlContent->xpath('//cfdi:Emisor')[0];
        $receptor = $xmlContent->xpath('//cfdi:Receptor')[0];
        $timbre = $xmlContent->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;

        if (!$comprobante || !$emisor || !$receptor) {
            return response()->json([
                'message' => 'XML incompleto o no válido.'
            ], 400);
        }

        $uuid = $timbre ? (string) $timbre['UUID'] : (string) Str::uuid();
        $folio = (string) $comprobante['Folio'] ?: Str::afterLast($uuid, '-');
        $moneda = (string) $comprobante['Moneda'];
        $total = (float) $comprobante['Total'];
        $fechaString = (string) $comprobante['Fecha']; // "2025-09-08T12:34:56"
        $fecha = Carbon::parse($fechaString);
                

        // Tipo de cambio
        $tipoCambio = null;
        try {
            $fechaFormatted = $fecha->format('d-m-Y');
            $response = Http::get("https://sidofqa.segob.gob.mx/dof/sidof/indicadores/158/{$fechaFormatted}/{$fechaFormatted}");
            if ($response->successful()) {
                $data = $response->json();
                $tipoCambio = floatval(str_replace(',', '.', $data['ListaIndicadores'][0]['valor']));
            }
        } catch (\Exception $e) {
            $tipoCambio = null; // fallback
        }

        // Guardar
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
    }

}
