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
    /**
     * Listado de facturas
     */
    public function index(Request $request)
    {
        $this->authorize('view-invoices');

        $perPage = $request->query('per_page', 10);

        try {
            $invoices = Invoice::paginate($perPage);
            return response()->json([
                'status' => 'success',
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener facturas: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudieron obtener las facturas'
            ], 500);
        }
    }

    /**
     * Cargar nueva factura desde XML
     */
    public function store(Request $request)
    {
        $this->authorize('upload-invoices');

        $request->validate([
            'xml' => 'required|file|mimes:xml',
        ]);

        $xmlFile = $request->file('xml');
        $path = $xmlFile->store('invoices');

        try {
            $xmlContent = $this->parseXml(storage_path('app/' . $path));
            $invoiceData = $this->extractInvoiceData($xmlContent, $path);

            $invoice = Invoice::create($invoiceData);

            return response()->json([
                'status' => 'success',
                'message' => 'Factura guardada correctamente',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            Log::error('Error procesando XML: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error procesando el XML',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parsear el XML y validar nodos necesarios
     */
    private function parseXml(string $filePath)
    {
        $xml = simplexml_load_file($filePath);
        if (!$xml) {
            throw new \Exception('XML inválido');
        }

        $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        $comprobante = $xml->xpath('//cfdi:Comprobante')[0] ?? null;
        $emisor = $xml->xpath('//cfdi:Emisor')[0] ?? null;
        $receptor = $xml->xpath('//cfdi:Receptor')[0] ?? null;
        $timbre = $xml->xpath('//tfd:TimbreFiscalDigital')[0] ?? null;

        if (!$comprobante || !$emisor || !$receptor) {
            throw new \Exception('XML incompleto: faltan nodos requeridos');
        }

        return compact('xml', 'comprobante', 'emisor', 'receptor', 'timbre');
    }

    /**
     * Extraer datos de la factura y consultar tipo de cambio
     */
    private function extractInvoiceData($xmlContent, string $path): array
    {
        extract($xmlContent);

        $uuid = $timbre ? (string) $timbre['UUID'] : (string) Str::uuid();
        $folio = (string) $comprobante['Folio'] ?: Str::afterLast($uuid, '-');
        $moneda = (string) $comprobante['Moneda'];
        $total = (float) $comprobante['Total'];
        $fecha = Carbon::parse((string) $comprobante['Fecha']);

        $tipoCambio = $this->getTipoCambio($fecha);

        return [
            'uuid' => $uuid,
            'folio' => $folio,
            'fecha' => $fecha,
            'emisor' => (string) $emisor['Rfc'],
            'receptor' => (string) $receptor['Rfc'],
            'moneda' => $moneda,
            'total' => $total,
            'tipo_cambio' => $tipoCambio,
            'xml_path' => $path,
        ];
    }

    /**
     * Obtener tipo de cambio desde la API del DOF
     */
    private function getTipoCambio(Carbon $fecha): ?float
    {
        $intentos = 0;
        $tipoCambio = null;
        $fechaParaDOF = $fecha->format('d-m-Y');

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
                $fecha->subDay();
                $fechaParaDOF = $fecha->format('Y-m-d');
                $intentos++;
            }
        }

        if (is_null($tipoCambio)) {
            Log::warning("No se encontró tipo de cambio para la fecha {$fecha->format('Y-m-d')} después de {$intentos} intentos.");
        }

        return $tipoCambio;
    }
}


