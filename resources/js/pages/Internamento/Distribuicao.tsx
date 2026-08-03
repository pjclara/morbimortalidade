import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Bar } from 'react-chartjs-2';

import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

export default function DistribuicaoPage(props: any) {
    const [filters, setFilters] = useState({
        data_entrada_de: '',
        data_entrada_ate: '',
        tipo: 'ambos',
        perfil: '',
        responsaveis: [] as string[],
    });

    console.log('props', props);

    const [resultado, setResultado] = useState(props.resultadoInicial ?? []);
    const [stats, setStats] = useState(props.statsInicial ?? {});
    const [logs, setLogs] = useState(props.logsInicial ?? []);

    const [loadingSimular, setLoadingSimular] = useState(false);
    const [loadingExecutar, setLoadingExecutar] = useState(false);
    const [hasValidSimulation, setHasValidSimulation] = useState(false);

    function updateFilters(partial: any) {
        setFilters((prev) => ({ ...prev, ...partial }));
        setHasValidSimulation(false);
    }

    function simularDistribuicao() {
        setLoadingSimular(true);

        router.get(
            '/distribuicao/simular',
            {
                data_entrada_de: filters.data_entrada_de,
                data_entrada_ate: filters.data_entrada_ate,
                tipo: filters.tipo,
                responsaveis: filters.responsaveis,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page: any) => {
                    setResultado(page.props.resultadoInicial ?? []);
                    setStats(page.props.statsInicial ?? {});
                    setLogs(page.props.logsInicial ?? []);
                    setHasValidSimulation(true);
                    setLoadingSimular(false);
                },
                onError: () => setLoadingSimular(false),
            },
        );
    }

    function executarDistribuicao() {
        setLoadingExecutar(true);

        router.post(
            '/distribuicao/executar',
            {
                data_entrada_de: filters.data_entrada_de,
                data_entrada_ate: filters.data_entrada_ate,
                tipo: filters.tipo,
                responsaveis: filters.responsaveis,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    setLoadingExecutar(false);
                },
                onError: () => setLoadingExecutar(false),
            },
        );
    }

    const chartData = {
        labels: resultado.map((r: any) => r.nome),
        datasets: [
            {
                label: 'Com bloco',
                backgroundColor: '#2563eb',
                data: resultado.map((r: any) => r.com_bloco),
            },
            {
                label: 'Sem bloco',
                backgroundColor: '#16a34a',
                data: resultado.map((r: any) => r.sem_bloco),
            },
        ],
    };

    const responsaveisFiltrados = (props.responsaveis ?? []).filter((user: any) => {
        if (!filters.perfil) {
            return true;
        }

        return user.roles.some((role: any) => role.name === filters.perfil);
    });
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Distribuicao',
            href: '/Distribuicao',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Distribuição" />
            <div className="mx-auto max-w-6xl space-y-10 py-10">
                <div>
                    <h1 className="text-3xl font-bold text-gray-900">Distribuição de Internamentos</h1>
                    <p className="mt-1 text-gray-600">Distribuição proporcional por carga, com ou sem bloco operatório.</p>
                </div>

                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold">Perfil</h2>

                    <select
                        value={filters.perfil}
                        onChange={(e) =>
                            updateFilters({
                                perfil: e.target.value,
                                responsaveis: [], // limpa a seleção
                            })
                        }
                        className="w-full rounded-lg border-gray-300"
                    >
                        <option value="">Todos os perfis</option>

                        {(props.perfis ?? []).map((perfil: any) => (
                            <option key={perfil.id} value={perfil.name}>
                                {perfil.name}
                            </option>
                        ))}
                    </select>
                </div>
                {/* Filtros */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold">Período</h2>

                        <div className="space-y-4">
                            <div>
                                <label className="text-sm text-gray-700">Data início</label>
                                <input
                                    type="date"
                                    value={filters.data_entrada_de}
                                    onChange={(e) => updateFilters({ data_entrada_de: e.target.value })}
                                    className="mt-1 w-full rounded-lg border-gray-300"
                                />
                            </div>

                            <div>
                                <label className="text-sm text-gray-700">Data fim</label>
                                <input
                                    type="date"
                                    value={filters.data_entrada_ate}
                                    onChange={(e) => updateFilters({ data_entrada_ate: e.target.value })}
                                    className="mt-1 w-full rounded-lg border-gray-300"
                                />
                            </div>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold">Responsáveis</h2>

                        <select
                            multiple
                            value={filters.responsaveis}
                            onChange={(e) =>
                                updateFilters({
                                    responsaveis: Array.from(e.target.selectedOptions).map((o) => o.value),
                                })
                            }
                            className="h-52 w-full rounded-lg border-gray-300"
                        >
                            {responsaveisFiltrados.map((user: any) => (
                                <option key={user.id} value={String(user.id)}>
                                    {user.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-lg font-semibold">Tipo de Internamento</h2>

                        <select
                            value={filters.tipo}
                            onChange={(e) => updateFilters({ tipo: e.target.value })}
                            className="w-full rounded-lg border-gray-300"
                        >
                            <option value="ambos">Ambos</option>
                            <option value="com">Com bloco</option>
                            <option value="sem">Sem bloco</option>
                        </select>
                    </div>
                </div>

                {/* Ações */}
                <div className="flex flex-col gap-3 sm:flex-row">
                    <button
                        onClick={simularDistribuicao}
                        disabled={loadingSimular}
                        className="rounded-xl bg-yellow-500 px-6 py-3 font-semibold text-white shadow hover:bg-yellow-600 disabled:opacity-60"
                    >
                        {loadingSimular ? 'A simular...' : 'Simular distribuição'}
                    </button>

                    <button
                        onClick={executarDistribuicao}
                        disabled={loadingExecutar || !hasValidSimulation}
                        className="rounded-xl bg-green-600 px-6 py-3 font-semibold text-white shadow hover:bg-green-700 disabled:opacity-60"
                    >
                        {loadingExecutar ? 'A executar...' : 'Executar distribuição'}
                    </button>
                </div>

                {/* Stats */}
                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <p className="text-sm text-gray-500">Internamentos sem responsável</p>
                        <p className="text-3xl font-bold">{stats.semResponsavel}</p>
                    </div>
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <p className="text-sm text-gray-500">Com bloco</p>
                        <p className="text-3xl font-bold">{stats.comBloco}</p>
                    </div>
                    <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                        <p className="text-sm text-gray-500">Sem bloco</p>
                        <p className="text-3xl font-bold">{stats.semBloco}</p>
                    </div>
                </div>

                {/* Tabela */}
                <div className="rounded-xl border border-gray-100 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsável</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Com bloco</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sem bloco</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-gray-200 bg-white">
                            {resultado.map((r: any) => (
                                <tr key={r.id}>
                                    <td className="px-6 py-4">{r.nome}</td>
                                    <td className="px-6 py-4">{r.com_bloco}</td>
                                    <td className="px-6 py-4">{r.sem_bloco}</td>
                                    <td className="px-6 py-4 font-bold">{r.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Gráfico */}
                <div className="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-semibold">Carga por responsável</h2>
                    <Bar data={chartData} />
                </div>

                {/* Logs */}
                <div className="rounded-xl border border-gray-200 bg-gray-50 p-6">
                    <h2 className="mb-4 text-lg font-semibold">Logs de distribuição</h2>
                    <ul className="space-y-2 text-sm text-gray-700">
                        {logs.map((log: any) => (
                            <li key={log.id}>
                                {log.data} — {log.mensagem}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </AppLayout>
    );
}
