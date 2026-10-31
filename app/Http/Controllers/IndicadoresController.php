<?php

namespace App\Http\Controllers;

use App\Models\Equipa;
use App\Services\IndicadoresServicoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndicadoresController extends Controller
{
    public function index(Request $request)
    {
        $dataInicio = $request->input('data_inicio', '2026-01-01');
        $dataFim = $request->input('data_fim', '2026-03-31');
        $equipaId = $request->filled('equipa_id') ? (int) $request->input('equipa_id') : null;

        $service = new IndicadoresServicoService($dataInicio, $dataFim, $equipaId);

        return Inertia::render('Indicadores/Index', [
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'equipa_id' => $equipaId,
            ],
            'equipa_options' => Equipa::pluck('id', 'nome'),
            'altas_por_dia_semana' => $service->altasPorDiaSemana(),
            'demora_media' => $service->demoraMedia(),
            'atraso_alta_clinica' => $service->atrasoAltaClinica(),
            'readmissoes_30_dias' => $service->readmissoes30Dias(),
            'ocupacao_diaria' => $service->ocupacaoDiaria(),
        ]);
    }
}
