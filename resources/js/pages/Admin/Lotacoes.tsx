import AppLayout from '@/layouts/app-layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import toast from 'react-hot-toast';

interface Lotacao {
    id: number;
    data_inicio: string;
    data_fim: string | null;
    numero_camas: number;
    observacoes: string | null;
}

interface FormState {
    data_inicio: string;
    data_fim: string;
    numero_camas: string;
    observacoes: string;
}

const vazio: FormState = { data_inicio: '', data_fim: '', numero_camas: '', observacoes: '' };

// O backend devolve datas como ISO completo (2026-04-01T00:00:00.000000Z);
// <input type="date"> só aceita "YYYY-MM-DD".
function soData(valor: string | null): string {
    return valor ? valor.slice(0, 10) : '';
}

export default function Lotacoes() {
    const { lotacoes } = usePage().props as unknown as { lotacoes: Lotacao[] };
    const [editando, setEditando] = useState<Lotacao | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm<FormState>(vazio);

    const abrirCriar = () => {
        setEditando(null);
        reset();
        setData(vazio);
    };

    const abrirEditar = (lotacao: Lotacao) => {
        setEditando(lotacao);
        setData({
            data_inicio: soData(lotacao.data_inicio),
            data_fim: soData(lotacao.data_fim),
            numero_camas: String(lotacao.numero_camas),
            observacoes: lotacao.observacoes ?? '',
        });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const opts = {
            onSuccess: () => {
                toast.success(editando ? 'Lotação atualizada.' : 'Lotação criada.');
                abrirCriar();
            },
            onError: () => toast.error('Verifica os dados do formulário.'),
        };

        if (editando) {
            put(`/admin/lotacoes/${editando.id}`, opts);
        } else {
            post('/admin/lotacoes', opts);
        }
    };

    const destroy = (lotacao: Lotacao) => {
        if (!confirm(`Eliminar o período de lotação "${lotacao.observacoes ?? lotacao.data_inicio}"?`)) return;

        router.delete(`/admin/lotacoes/${lotacao.id}`, {
            onSuccess: () => toast.success('Lotação eliminada.'),
            onError: () => toast.error('Erro ao eliminar a lotação.'),
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Admin', href: '/admin' }, { title: 'Lotação', href: '/admin/lotacoes' }]}>
            <Head title="Gestão de Lotação" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-xl font-semibold">Gestão de Lotação</h1>
                    <p className="text-sm text-neutral-500">
                        Número de camas do serviço por período — usado para calcular taxa de ocupação, doentes por cama e dias de cama vazia.
                    </p>
                </div>

                <form onSubmit={submit} className="flex flex-wrap items-end gap-3 rounded-lg border p-4">
                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-neutral-500">Data início</label>
                        <input
                            type="date"
                            required
                            className="rounded border px-2 py-1.5 text-sm dark:bg-neutral-900"
                            value={data.data_inicio}
                            onChange={(e) => setData('data_inicio', e.target.value)}
                        />
                        {errors.data_inicio && <span className="text-xs text-red-600">{errors.data_inicio}</span>}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-neutral-500">Data fim (opcional)</label>
                        <input
                            type="date"
                            className="rounded border px-2 py-1.5 text-sm dark:bg-neutral-900"
                            value={data.data_fim}
                            onChange={(e) => setData('data_fim', e.target.value)}
                        />
                        {errors.data_fim && <span className="text-xs text-red-600">{errors.data_fim}</span>}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-neutral-500">Nº de camas</label>
                        <input
                            type="number"
                            min={1}
                            required
                            className="w-28 rounded border px-2 py-1.5 text-sm dark:bg-neutral-900"
                            value={data.numero_camas}
                            onChange={(e) => setData('numero_camas', e.target.value)}
                        />
                        {errors.numero_camas && <span className="text-xs text-red-600">{errors.numero_camas}</span>}
                    </div>

                    <div className="flex flex-col gap-1">
                        <label className="text-xs font-medium text-neutral-500">Observações</label>
                        <input
                            type="text"
                            className="rounded border px-2 py-1.5 text-sm dark:bg-neutral-900"
                            value={data.observacoes}
                            onChange={(e) => setData('observacoes', e.target.value)}
                        />
                    </div>

                    <button type="submit" disabled={processing} className="rounded bg-green-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                        {editando ? 'Guardar alterações' : 'Adicionar período'}
                    </button>

                    {editando && (
                        <button type="button" onClick={abrirCriar} className="rounded border px-4 py-2 text-sm font-medium">
                            Cancelar
                        </button>
                    )}
                </form>

                <table className="w-full rounded-lg border text-sm">
                    <thead>
                        <tr className="border-b bg-neutral-100 dark:bg-neutral-800">
                            <th className="p-2 text-left">Período</th>
                            <th className="p-2 text-left">Nº de camas</th>
                            <th className="p-2 text-left">Observações</th>
                            <th className="p-2 text-left">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        {lotacoes.map((lotacao) => (
                            <tr key={lotacao.id} className="border-b">
                                <td className="p-2">
                                    {soData(lotacao.data_inicio)} – {lotacao.data_fim ? soData(lotacao.data_fim) : 'em curso'}
                                </td>
                                <td className="p-2">{lotacao.numero_camas}</td>
                                <td className="p-2">{lotacao.observacoes ?? '-'}</td>
                                <td className="p-2">
                                    <div className="flex gap-2">
                                        <button onClick={() => abrirEditar(lotacao)} className="rounded bg-neutral-700 px-3 py-1 text-white">
                                            Editar
                                        </button>
                                        <button onClick={() => destroy(lotacao)} className="rounded bg-red-600 px-3 py-1 text-white">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {lotacoes.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-4 text-center text-neutral-500">
                                    Sem períodos de lotação registados.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
