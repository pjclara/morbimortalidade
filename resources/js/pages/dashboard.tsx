import AppLayout from '@/layouts/app-layout';
import { Head, usePage, router } from '@inertiajs/react';
import { Line } from 'react-chartjs-2';
import { CategoryScale, Chart as ChartJS, Legend, LinearScale, LineElement, PointElement, Title, Tooltip, Filler } from 'chart.js';
import { Activity, BedDouble, ClipboardList, Stethoscope, Filter, X } from 'lucide-react';
import { useState, useMemo } from 'react';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

interface Stats {
    totalCirurgias: number;
    totalAmbulatorio: number;
    totalInternamento: number;
    totalInternamentos?: number;
}

interface TipoCirurgia {
    id: number;
    nome: string;
    total: number;
}

interface CirurgiaMes {
    mes: string;
    total: number;
}

interface UltimaCirurgia {
    id: number;
    data_intervencao: string;
    bloco_num: string;
    ambulatorio: string;
    tipoDeCirurgia?: { nome: string };
}

interface Patient {
    id: number;
    nome: string;
}

interface Equipa {
    id: number;
    nome: string;
}

interface BlocoOperatorioItem {
    id: number;
    bloco_num: string;
    data_intervencao: string;
    ambulatorio: string;
    tipoDeCirurgia?: { nome: string };
}

interface Internamento {
    id: number;
    episodio: string;
    data_entrada: string;
    data_saida: string;
    dias_internamento: number;
    patient?: Patient;
    equipa?: Equipa;
    blocoOperatorios?: BlocoOperatorioItem[];
}

interface Filtros {
    data_inicio?: string;
    data_fim?: string;
    tipo_filtro?: string;
    tipo_cirurgia?: string;
    bloco?: string;
}

interface PageProps {
    stats: Stats;
    totalStats: Stats;
    topTipos: TipoCirurgia[];
    cirurgiasPorMes: CirurgiaMes[];
    ultimas: UltimaCirurgia[];
    internamentos: Internamento[];
    blocos: string[];
    tiposCirurgia: TipoCirurgia[];
    filtros: Filtros;
}

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];

function DashboardCard({ title, value, icon, color }: any) {
    return (
        <div className="rounded-xl border bg-white p-6 shadow dark:bg-neutral-900">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">{title}</p>
                    <p className="mt-2 text-3xl font-bold">{value.toLocaleString('pt-PT')}</p>
                </div>
                <div className={`${color} rounded-lg p-4 text-white`}>{icon}</div>
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { stats, totalStats, topTipos = [], cirurgiasPorMes = [], ultimas = [], internamentos = [], blocos = [], tiposCirurgia = [], filtros } =
        usePage<PageProps>().props;

    const [showFilters, setShowFilters] = useState(false);
    const [localFiltros, setLocalFiltros] = useState<Filtros>(filtros);

    const handleFilterChange = (key: string, value: string) => {
        setLocalFiltros((prev) => ({ ...prev, [key]: value }));
        applyFilters();
    };

    const applyFilters = () => {
        router.get('/dashboard', localFiltros, { preserveState: true });
    };

    const clearFilters = () => {
        setLocalFiltros({});
        router.get('/dashboard', {}, { preserveState: true });
    };

    const chartData = {
        labels: cirurgiasPorMes.map((m) => m.mes),
        datasets: [
            {
                label: 'Cirurgias',
                data: cirurgiasPorMes.map((m) => m.total),
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.2)',
                fill: true,
                tension: 0.4,
            },
        ],
    };

    const chartOptions = {
        responsive: true,
        plugins: { legend: { position: 'top' as const } },
        scales: { y: { beginAtZero: true } },
    };

    const hasActiveFilters = Object.values(localFiltros).some((v) => v);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Cirúrgico" />

         
        </AppLayout>
    );
}
