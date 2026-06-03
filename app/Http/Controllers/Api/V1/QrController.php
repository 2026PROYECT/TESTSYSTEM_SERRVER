<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuizAssignment; 
use SimpleSoftwareIO\QrCode\Facades\QrCode; // <--- Esta es la clase que acabas de instalar
use Barryvdh\DomPDF\Facade\Pdf;

class QrController extends Controller
{
    public function generatePdf($id)
{
    // 1. Buscamos el registro. 
    // Usamos find() en lugar de findOrFail para controlar el error nosotros
    $result = \App\Models\QuizAssignment::find($id);

    // 2. Si el ID no existe en la tabla, creamos un objeto vacío para que no explote la vista
    if (!$result) {
        $result = (object)[
            'id' => $id,
            'test_type' => 'No encontrado',
            'active' => 0
        ];
    }

    // 3. Generamos el QR con una URL real basada en el ID
    // Quitamos mergeConfig para usar la configuración por defecto que ya vimos que te funciona
    $qrCodeData = QrCode::format('png')
        ->size(200)
        ->margin(1)
        ->generate(url("/verificar/quiz/" . $id));

    $qrcode = base64_encode($qrCodeData);

    // 4. Enviamos a la vista
    return Pdf::loadView('pdf.pfd', [
        'result' => $result, 
        'qrcode' => $qrcode
    ])->stream("Resultado_{$id}.pdf");
}
}