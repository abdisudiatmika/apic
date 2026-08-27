<?php

namespace App\Http\Controllers;

use App\Models\TravelAssignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class TravelAssignmentPdfController extends Controller
{
    public function show(TravelAssignment $travelAssignment): Response
    {
        abort_unless(auth()->user()->can('downloadPdf', $travelAssignment), 403);

        $travelAssignment->load(['requester.department', 'requester.position', 'employees']);

        $pdf = Pdf::loadView('pdf.travel-assignment', ['travelAssignment' => $travelAssignment]);

        return $pdf->stream("surat-tugas-{$travelAssignment->id}.pdf");
    }
}
