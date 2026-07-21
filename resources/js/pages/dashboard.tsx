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

            <div className="space-y-6 p-6">
                {/* Filtros */}
                <div className="rounded-xl border bg-white p-6 shadow dark:bg-neutral-900">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold flex items-center gap-2">
                            <Filter size={20} /> Filtros
                        </h2>
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400"
                        >
                            {showFilters ? 'Ocultar' : 'Mostrar'}
                        </button>
                    </div>

                    {showFilters && (
                        <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                            <div>
                                <label className="block text-sm font-medium">Data Início</label>
                                <input
                                    type="date"
                                    value={localFiltros.data_inicio || ''}
                                    onChange={(e) => handleFilterChange('data_inicio', e.target.value)}
                                    className="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-800 dark:border-neutral-700"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium">Data Fim</label>
                                <input
                                    type="date"
                                    value={localFiltros.data_fim || ''}
                                    onChange={(e) => handleFilterChange('data_fim', e.target.value)}
                                    className="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-800 dark:border-neutral-700"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium">Tipo</label>
                                <select
                                    value={localFiltros.tipo_filtro || ''}
                                    onChange={(e) => handleFilterChange('tipo_filtro', e.target.value)}
                                    className="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-800 dark:border-neutral-700"
                                >
                                    <option value="">Todos</option>
                                    <option value="ambulatorio">Ambulatório</option>
                                    <option value="internamento">Internamento</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium">Tipo de Cirurgia</label>
                                <select
                                    value={localFiltros.tipo_cirurgia || ''}
                                    onChange={(e) => handleFilterChange('tipo_cirurgia', e.target.value)}
                                    className="mt-1 w-full rounded-lg border px-3 py-2 dark:bg-neutral-800 dark:border-neutral-700"
                                >
                                    <option value="">Todos</option>
                                    {tiposCirurgia.map((tipo) => (
                                        <option key={tipo.id} value={tipo.id}>
                                            {tipo.nome}
                                        </option>
                                    ))}
                                </select>
                            </div>

                        </div>
                    )}

                    {hasActiveFilters && (
                        <div className="mt-3 text-sm text-blue-600 dark:text-blue-400">
                            ✓ Filtros activos - A mostrar dados filtrados
                        </div>
                    )}
                </div>

                {/* Cards */}
                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <DashboardCard title="Total Cirurgias" value={stats.totalCirurgias} icon={<Activity size={28} />} color="bg-blue-500" />
                    <DashboardCard title="Ambulatório" value={stats.totalAmbulatorio} icon={<Stethoscope size={28} />} color="bg-green-500" />
                    <DashboardCard title="Internamento" value={stats.totalInternamento} icon={<BedDouble size={28} />} color="bg-orange-500" />
                    <DashboardCard title="Total Internamentos" value={stats.totalInternamentos || 0} icon={<ClipboardList size={28} />} color="bg-purple-500" />
                </div>

                {/* Gráfico e Ranking */}
                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="rounded-xl border bg-white p-6 shadow lg:col-span-2 dark:bg-neutral-900">
                        <h2 className="mb-5 text-xl font-semibold">Cirurgias por Mês</h2>
                        {cirurgiasPorMes.length ? (
                            <Line data={chartData} options={chartOptions} />
                        ) : (
                            <div className="py-20 text-center text-gray-500">Sem dados disponíveis.</div>
                        )}
                    </div>

                    <div className="rounded-xl border bg-white p-6 shadow dark:bg-neutral-900">
                        <h2 className="mb-5 text-xl font-semibold">Top Tipos de Cirurgia</h2>
                        {topTipos.length === 0 ? (
                            <p className="text-center text-gray-500">Sem dados.</p>
                        ) : (
                            <div className="space-y-4">
                                {topTipos.map((item, index) => (
                                    <div key={item.id} className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-neutral-800">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-bold">
                                                {index + 1}
                                            </div>
                                            <span className="text-sm">{item.nome}</span>
                                        </div>
                                        <span className="font-bold">{item.total}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
