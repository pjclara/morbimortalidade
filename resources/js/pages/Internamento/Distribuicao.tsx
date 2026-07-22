import { useState } from "react";
import { router } from "@inertiajs/react";
import { Bar } from "react-chartjs-2";

type Responsavel = {
  id: number;
  nome: string;
};

type ResultadoItem = {
  id: number;
  nome: string;
  com_bloco: number;
  sem_bloco: number;
  total: number;
};

type Props = {
  responsaveis: Responsavel[];
  resultadoInicial: ResultadoItem[];
  statsInicial: {
    semResponsavel: number;
    comBloco: number;
    semBloco: number;
  };
  logsInicial: { id: number; data: string; mensagem: string }[];
};

export default function DistribuicaoPage({
  responsaveis,
  resultadoInicial,
  statsInicial,
  logsInicial,
}: Props) {
  const [filters, setFilters] = useState({
    data_entrada_de: "",
    data_entrada_ate: "",
    tipo: "ambos",
    responsaveis: [] as string[],
  });

  const [resultado, setResultado] = useState(resultadoInicial);
  const [stats, setStats] = useState(statsInicial);
  const [logs, setLogs] = useState(logsInicial);
  const [loadingSimular, setLoadingSimular] = useState(false);
  const [loadingExecutar, setLoadingExecutar] = useState(false);

  function updateFilters(partial: Partial<typeof filters>) {
    setFilters((prev) => ({ ...prev, ...partial }));
  }

  function simularDistribuicao() {
    setLoadingSimular(true);

    router.get(
      "/distribuicao/simular",
      {
        data_entrada_de: filters.data_entrada_de,
        data_entrada_ate: filters.data_entrada_ate,
        tipo: filters.tipo,
        responsaveis: filters.responsaveis,
      },
      {
        preserveState: true,
        onSuccess: (page: any) => {
          setResultado(page.props.resultado);
          setStats(page.props.stats);
          setLogs(page.props.logs);
          setLoadingSimular(false);
        },
        onError: () => setLoadingSimular(false),
      }
    );
  }

  function executarDistribuicao() {
    setLoadingExecutar(true);

    router.post(
      "/distribuicao/executar",
      {
        data_entrada_de: filters.data_entrada_de,
        data_entrada_ate: filters.data_entrada_ate,
        tipo: filters.tipo,
        responsaveis: filters.responsaveis,
      },
      {
        preserveState: true,
        onSuccess: (page: any) => {
          setResultado(page.props.resultado);
          setStats(page.props.stats);
          setLogs(page.props.logs);
          setLoadingExecutar(false);
        },
        onError: () => setLoadingExecutar(false),
      }
    );
  }

  const chartData = {
    labels: resultado.map((r) => r.nome),
    datasets: [
      {
        label: "Com bloco",
        backgroundColor: "#2563eb",
        data: resultado.map((r) => r.com_bloco),
      },
      {
        label: "Sem bloco",
        backgroundColor: "#16a34a",
        data: resultado.map((r) => r.sem_bloco),
      },
    ],
  };

  return (
    <div className="max-w-6xl mx-auto py-10 space-y-10">
      {/* Header */}
      <div>
        <h1 className="text-3xl font-bold text-gray-900">
          Distribuição de Internamentos
        </h1>
        <p className="text-gray-600 mt-1">
          Distribuição proporcional por carga, com ou sem bloco, para
          responsáveis ativos.
        </p>
      </div>

      {/* Filtros */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <h2 className="text-lg font-semibold mb-4">Período</h2>
          <div className="space-y-4">
            <div>
              <label className="text-sm text-gray-700">Data início</label>
              <input
                type="date"
                value={filters.data_entrada_de}
                onChange={(e) =>
                  updateFilters({ data_entrada_de: e.target.value })
                }
                className="mt-1 w-full rounded-lg border-gray-300"
              />
            </div>
            <div>
              <label className="text-sm text-gray-700">Data fim</label>
              <input
                type="date"
                value={filters.data_entrada_ate}
                onChange={(e) =>
                  updateFilters({ data_entrada_ate: e.target.value })
                }
                className="mt-1 w-full rounded-lg border-gray-300"
              />
            </div>
          </div>
        </div>

        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <h2 className="text-lg font-semibold mb-4">Responsáveis</h2>
          <select
            multiple
            value={filters.responsaveis}
            onChange={(e) =>
              updateFilters({
                responsaveis: Array.from(e.target.selectedOptions).map(
                  (o) => o.value
                ),
              })
            }
            className="w-full h-40 rounded-lg border-gray-300"
          >
            {responsaveis.map((r) => (
              <option key={r.id} value={String(r.id)}>
                {r.nome}
              </option>
            ))}
          </select>
        </div>

        <div className="bg-white shadow-sm rounded-xl p-6 border border-gray-100">
          <h2 className="text-lg font-semibold mb-4">Tipo de Internamento</h2>
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
      <div className="flex gap-4">
        <button
          onClick={simularDistribuicao}
          disabled={loadingSimular}
          className="px-6 py-3 rounded-xl bg-yellow-500 text-white font-semibold shadow hover:bg-yellow-600 disabled:opacity-60"
        >
          {loadingSimular ? "A simular..." : "Simular distribuição"}
        </button>

        <button
          onClick={executarDistribuicao}
          disabled={loadingExecutar}
          className="px-6 py-3 rounded-xl bg-green-600 text-white font-semibold shadow hover:bg-green-700 disabled:opacity-60"
        >
          {loadingExecutar ? "A executar..." : "Executar distribuição"}
        </button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <p className="text-sm text-gray-500">Internamentos sem responsável</p>
          <p className="text-3xl font-bold">{stats.semResponsavel}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <p className="text-sm text-gray-500">Com bloco</p>
          <p className="text-3xl font-bold">{stats.comBloco}</p>
        </div>
        <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
          <p className="text-sm text-gray-500">Sem bloco</p>
          <p className="text-3xl font-bold">{stats.semBloco}</p>
        </div>
      </div>

      {/* Tabela */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Responsável
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Com bloco
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Sem bloco
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Total
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {resultado.map((r) => (
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
      <div className="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <h2 className="text-lg font-semibold mb-4">Carga por responsável</h2>
        <Bar data={chartData} />
      </div>

      {/* Logs */}
      <div className="bg-gray-50 rounded-xl p-6 border border-gray-200">
        <h2 className="text-lg font-semibold mb-4">Logs de distribuição</h2>
        <ul className="space-y-2 text-gray-700 text-sm">
          {logs.map((log) => (
            <li key={log.id}>
              {log.data} — {log.mensagem}
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
