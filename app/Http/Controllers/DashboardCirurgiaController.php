<?php

namespace App\Http\Controllers;

use App\Models\BlocoOperatorio;
use App\Models\Procedimento;
use App\Models\TipoDeCirurgia;
use App\Services\MenuService;
use Inertia\Inertia;

class DashboardCirurgiaController extends Controller
{
    public function index()
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'totalCirurgias' => BlocoOperatorio::count(),
                'totalAmbulatorio' => BlocoOperatorio::where('ambulatorio', 'S')->count(),
                'totalInternamento' => BlocoOperatorio::where('ambulatorio', 'N')->count(),
                'totalProcedimentos' => Procedimento::count(),
            ],

            'topTipos' => TipoDeCirurgia::select('nome')
                ->selectRaw('COUNT(bloco_operatorios.id) as total')
                ->join('bloco_operatorios', 'tipo_de_cirurgias.id', '=', 'bloco_operatorios.tipo_de_cirurgia_id')
                ->groupBy('nome')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),

            'cirurgiasPorMes' => BlocoOperatorio::selectRaw("
                    DATE_FORMAT(data_intervencao, '%Y-%m') as mes,
                    COUNT(*) as total
                ")
                ->groupBy('mes')
                ->orderBy('mes')
                ->get(),

            'ultimas' => BlocoOperatorio::with('tipoDeCirurgia')
                ->orderByDesc('data_intervencao')
                ->limit(10)
                ->get(),
        ]);
    }
}
