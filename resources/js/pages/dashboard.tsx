import AppLayout from '@/layouts/app-layout';
import { Head, usePage } from '@inertiajs/react';
import { Line } from 'react-chartjs-2';

import { CategoryScale, Chart as ChartJS, Legend, LinearScale, LineElement, PointElement, Title, Tooltip, Filler } from 'chart.js';

import { Activity, BedDouble, ClipboardList, Stethoscope } from 'lucide-react';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler, // ✅ ESTE É O PLUGIN QUE ATIVA O "fill"
);

interface BreadcrumbItem {
    title: string;
    href: string;
}

interface Stats {
    totalCirurgias: number;
    totalAmbulatorio: number;
    totalInternamento: number;
    totalProcedimentos: number;
}

interface TipoCirurgia {
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
    tipo_cirurgia?: {
        nome: string;
    };
}

interface PageProps {
    stats: Stats;
    topTipos: TipoCirurgia[];
    cirurgiasPorMes: CirurgiaMes[];
    ultimas: UltimaCirurgia[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    const { stats = {} as Stats, topTipos = [], cirurgiasPorMes = [], ultimas = [] } = usePage<PageProps>().props;

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

    const options = {
        responsive: true,
        plugins: {
            legend: {
                position: 'top' as const,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Cirúrgico" />

            <div className="space-y-6 p-6">
                {/* Cards */}

                <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                    <DashboardCard title="Total Cirurgias" value={stats.totalCirurgias} icon={<Activity size={28} />} color="bg-blue-500" />

                    <DashboardCard title="Ambulatório" value={stats.totalAmbulatorio} icon={<Stethoscope size={28} />} color="bg-green-500" />

                    <DashboardCard title="Internamento" value={stats.totalInternamento} icon={<BedDouble size={28} />} color="bg-orange-500" />

                    <DashboardCard title="Procedimentos" value={stats.totalProcedimentos} icon={<ClipboardList size={28} />} color="bg-purple-500" />
                </div>

                {/* Linha 2 */}

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Gráfico */}

                    <div className="rounded-xl border bg-white p-6 shadow lg:col-span-2 dark:bg-neutral-900">
                        <h2 className="mb-5 text-xl font-semibold">Cirurgias por mês</h2>

                        {cirurgiasPorMes.length ? (
                            <Line data={chartData} options={options} />
                        ) : (
                            <div className="py-20 text-center text-gray-500">Sem dados disponíveis.</div>
                        )}
                    </div>

                    {/* Ranking */}

                    <div className="rounded-xl border bg-white p-6 shadow dark:bg-neutral-900">
                        <h2 className="mb-5 text-xl font-semibold">Top Tipos de Cirurgia</h2>

                        {topTipos.length === 0 ? (
                            <p className="text-center text-gray-500">Sem dados.</p>
                        ) : (
                            <div className="space-y-4">
                                {topTipos.map((item, index) => (
                                    <div key={item.nome} className="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-neutral-800">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600 text-white">
                                                {index + 1}
                                            </div>

                                            <span>{item.nome}</span>
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

interface CardProps {
    title: string;
    value: number;
    icon: React.ReactNode;
    color: string;
}

function DashboardCard({ title, value, icon, color }: CardProps) {
    return (
        <div className="rounded-xl border bg-white p-5 shadow transition hover:-translate-y-1 hover:shadow-lg dark:bg-neutral-900">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-gray-500">{title}</p>

                    <h2 className="mt-2 text-3xl font-bold">{value}</h2>
                </div>

                <div className={`${color} rounded-full p-4 text-white`}>{icon}</div>
            </div>
        </div>
    );
}
