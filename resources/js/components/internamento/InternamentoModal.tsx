import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';

const INITIAL_TABS = ['paciente', 'internamento', 'diagnosticos'];

export default function InternamentoModal({ open, onClose, item, onSave }: any) {
    const [tab, setTab] = useState('internamento');
    const [editMode, setEditMode] = useState(false);
    const [form, setForm] = useState(item || {});
    const [backendErrors, setBackendErrors] = useState<any>({});
    const [searchComplicacao, setSearchComplicacao] = useState('');
    const [searchResolucao, setSearchResolucao] = useState('');

    const [tabs, setTabs] = useState(INITIAL_TABS);
    const [openComplicacao, setOpenComplicacao] = useState<number | null>(null);
    const [openResolucaoModal, setOpenResolucaoModal] = useState(false);
    const [currentResolucaoCi, setCurrentResolucaoCi] = useState<any>(null);
    const [currentResolucaoSelection, setCurrentResolucaoSelection] = useState<number[]>([]);

    function openResolucaoEditor(ci: any) {
        setCurrentResolucaoCi(ci);
        setCurrentResolucaoSelection(ci.resolucaos?.map((r: any) => Number(r.id)) ?? []);
        setOpenResolucaoModal(true);
    }

    function closeResolucaoEditor() {
        setOpenResolucaoModal(false);
        setCurrentResolucaoCi(null);
    }

    function saveResolucaoEditor() {
        if (!currentResolucaoCi) {
            return;
        }

        const resolucaoOptions = selectFields.resolucao_id ?? [];

        updateComplicacaoInternamento(currentResolucaoCi, {
            resolucaos: currentResolucaoSelection.map((id: number) => {
                const option = resolucaoOptions.find((opt: any) => opt.id === id || opt.id == id);
                return {
                    id,
                    nome: option?.nome ?? '',
                };
            }),
        });
        closeResolucaoEditor();
    }

    useEffect(() => {
        if (!item) {
            setForm({});
            setTabs([...INITIAL_TABS]); // força novo array
            return;
        }

        setForm(item);

        if (item.bloco_operatorios_count > 0) {
            setTabs((prev) =>
                prev.includes('clavien') ? prev : [...prev, 'bloco_operatorios', 'complicacoes', 'clavien', 'destino', 'observacoes', 'responsavel'],
            );
        } else {
            setTabs((prev) => (prev.includes('clavien') ? prev : [...prev, 'destino', 'observacoes', 'responsavel']));
        }
    }, [item]);

    const pageProps: any = usePage().props;

    if (!open) return null;

    const editableFields = ['observacoes', 'mortalidade_esperada', 'bloquear', 'data_alta', 'principal'];
    const booleanFields = ['mortalidade_esperada', 'falecido', 'bloquear', 'principal'];
    const dataFields = ['data_alta'];
    const textFields = ['observacoes'];

    const optionsSource: any = form || item;

    const getOptionValue = (key: string) => optionsSource?.[key] ?? pageProps?.[key];

    // 🔥 Converte objetos em arrays automaticamente
    const selectFields: any = {
        destino_id: getOptionValue('destino_options') ? Object.entries(getOptionValue('destino_options')).map(([nome, id]) => ({ id, nome })) : null,

        origem_id: getOptionValue('origem_options') ? Object.entries(getOptionValue('origem_options')).map(([nome, id]) => ({ id, nome })) : null,

        responsavel_id: getOptionValue('responsavel_options')
            ? Object.entries(getOptionValue('responsavel_options')).map(([nome, id]) => ({ id, nome }))
            : null,

        clavien_dindo_id: getOptionValue('clavien_options')
            ? Object.entries(getOptionValue('clavien_options')).map(([nome, id]) => ({ id, nome }))
            : null,

        resolucao_id: getOptionValue('resolucao_options')
            ? Object.entries(getOptionValue('resolucao_options')).map(([nome, id]) => ({ id, nome }))
            : null,

        complicacao_id: getOptionValue('complicacao_options')
            ? Object.entries(getOptionValue('complicacao_options')).map(([nome, id]) => ({ id, nome }))
            : null,
    };

    const complicacaoInternamentos =
        form.complicacaoInternamentos ?? form.complicacao_internamentos ?? item?.complicacaoInternamentos ?? item?.complicacao_internamentos ?? [];

    function setComplicacaoInternamentos(updated: any[]) {
        setForm({
            ...form,
            complicacaoInternamentos: updated,
            complicacao_internamentos: updated,
        });
    }

    function getComplicacaoKey(ci: any) {
        return ci.id ?? ci._tempId;
    }

    function addComplicacao() {
        setComplicacaoInternamentos([...complicacaoInternamentos, { _tempId: Date.now(), complicacao_id: null, resolucaos: [] }]);
    }

    function removeComplicacao(ci: any) {
        setComplicacaoInternamentos(complicacaoInternamentos.filter((item: any) => getComplicacaoKey(item) !== getComplicacaoKey(ci)));
    }

    function updateComplicacaoInternamento(ci: any, patch: any) {
        setComplicacaoInternamentos(
            complicacaoInternamentos.map((item: any) => (getComplicacaoKey(item) === getComplicacaoKey(ci) ? { ...item, ...patch } : item)),
        );
    }

    function toggleMode() {
        setEditMode(!editMode);
    }

    function handleChange(key: string, value: any) {
        setForm({
            ...form,
            [key]: value,
        });
    }

    function save() {
        const payload = {
            ...form,
            complicacao_internamentos: complicacaoInternamentos.map((ci: any) => ({
                id: ci.id,
                complicacao_id: ci.complicacao_id,
                resolucaos: ci.resolucaos?.map((r: any) => ({ id: r.id })) ?? [],
            })),
        };

        router.put(`/internamentos/${form.id}`, payload, {
            preserveState: true,
            preserveScroll: true,

            onError: (errors) => {
                setBackendErrors(errors); // ✔️ mostra erros no modal
                toast.error('Existem erros no formulário.');
            },

            onSuccess: () => {
                setBackendErrors({});
                setEditMode(false);
                toast.success('Internamento atualizado com sucesso!');

                if (onSave) onSave(payload); // ✔️ devolve dados reais
            },
        });
    }
    const filteredComplicacoes = (selectFields.complicacao_id ?? []).filter((opt: any) =>
        opt.nome.toLowerCase().includes(searchComplicacao.toLowerCase()),
    );

    const filteredResolucoes = (selectFields.resolucao_id ?? []).filter((opt: any) => opt.nome.toLowerCase().includes(searchResolucao.toLowerCase()));
    function renderField(label: string, key: string, value: any, info?: string) {
        const isBoolean = value === 0 || value === 1 || typeof value === 'boolean';
        const isBooleanField = booleanFields.includes(key);
        const isDataField = dataFields.includes(key);
        const isTextField = textFields.includes(key);
        const isEditable = editableFields.includes(key);
        const hasSelectOptions = Array.isArray(selectFields[key]);
        const fieldErrors = backendErrors?.[key];

        return (
            <div className="flex border-b border-neutral-300 py-2 dark:border-neutral-700">
                <span className="w-50 text-center font-semibold">{label}</span>
                {!editMode && (
                    <span className="ml-4">
                        {isBooleanField ? (value ? 'Sim' : 'Não') : (value ?? '-')}
                        {hasSelectOptions && value && selectFields[key].find((opt: any) => opt.id === value)?.nome}
                    </span>
                )}

                {editMode &&
                    (!isEditable ? (
                        <span className="ml-4 opacity-70">{isBooleanField ? (value ? 'Sim' : 'Não') : (value ?? '-')}</span>
                    ) : isBoolean ? (
                        <div>
                            <div className="flex w-full flex-col">
                                <input
                                    type="checkbox"
                                    className="ml-4 h-5 w-5"
                                    checked={Number(form[key]) === 1}
                                    onChange={(e) => handleChange(key, e.target.checked ? 1 : 0)}
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ) : hasSelectOptions ? (
                        <div>
                            <div className="flex w-full flex-col">
                                <select
                                    className="ml-4 rounded-md border border-neutral-300 bg-white px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                    value={form[key] ?? ''}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                >
                                    <option value="">Selecione...</option>
                                    {selectFields[key].map((opt: any) => (
                                        <option key={opt.id} value={opt.id}>
                                            {opt.nome}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ) : isDataField ? (
                        <div>
                            <div className="flex w-full flex-col">
                                <input
                                    type="date"
                                    className="ml-4 rounded-md border border-neutral-300 bg-white px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                    value={form[key] ?? ''}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ) : isTextField ? (
                        <div>
                            <div className="w-full">
                                <textarea
                                    value={form[key] ?? ''}
                                    cols={100}
                                    rows={10}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    className="w-full rounded-md border border-neutral-300 p-2 dark:border-neutral-700 dark:bg-neutral-800"
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors.join(', ')}</span>}
                        </div>
                    ) : (
                        <div>
                            <div className="flex w-full flex-col">
                                <input
                                    className="ml-4 rounded-md border border-neutral-300 bg-white px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                    value={form[key] ?? ''}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ))}
                {info && <span className="text-muted-foreground ml-2 px-2 py-1">{info}</span>}
            </div>
        );
    }

    const togglePrincipal = async (diagnosticoId: number) => {
        await router.post(
            `/registos-cirurgicos/${item.id}/diagnosticos/${diagnosticoId}/principal`,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    toast.success('Diagnóstico principal atualizado com sucesso!');
                    if (onSave)
                        onSave({
                            ...form,
                            diagnosticos: form.diagnosticos.map((d: any) => ({ ...d, pivot: { ...d.pivot, principal: d.id === diagnosticoId } })),
                        });
                },
                onError: () => {
                    toast.error('Erro ao atualizar diagnóstico principal.');
                },
            },
        );
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="relative h-[60vh] w-[80vw] overflow-y-auto rounded-xl border bg-white p-6 dark:bg-neutral-900">
                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/10 dark:stroke-neutral-100/10" />

                <div className="relative z-10 space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-semibold">{editMode ? 'Editar Internamento' : 'Detalhes do Internamento'}</h2>

                        <div className="flex gap-2">
                            <button onClick={toggleMode} className="rounded-md bg-blue-600 px-3 py-1 text-white">
                                {editMode ? 'Ver' : 'Editar'}
                            </button>

                            <button onClick={onClose} className="rounded-md bg-red-600 px-3 py-1 text-white">
                                Fechar
                            </button>
                        </div>
                    </div>

                    <div className="flex gap-2 border-b border-neutral-300 pb-2 dark:border-neutral-700">
                        {tabs.map((t) => (
                            <button
                                key={t}
                                onClick={() => setTab(t)}
                                className={
                                    'rounded-md px-3 py-1 ' +
                                    (tab === t
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-neutral-200 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300')
                                }
                            >
                                {t.charAt(0).toUpperCase() + t.slice(1)}
                            </button>
                        ))}
                    </div>

                    <div className="space-y-2">
                        {tab === 'internamento' && (
                            <>
                                {renderField('Processo', 'processo', item?.patient.processo)}
                                {renderField('Entrada', 'data_entrada', item.data_entrada)}
                                {renderField('Alta', 'data_alta', item.data_alta, 'Colocar aqui a data da alta clínica, se for o caso.')}
                                {renderField('Saída', 'data_saida', item.data_saida)}
                                {renderField('Dias Internamento', 'dias_internamento', item.dias_internamento)}
                                {renderField('Falecido', 'falecido', item.falecido)}
                                {item.falecido ? renderField('Mortalidade Esperada', 'mortalidade_esperada', item.mortalidade_esperada) : null}
                            </>
                        )}

                        {tab === 'paciente' && (
                            <>
                                {renderField('Processo', 'patient.processo', item.patient?.processo)}
                                {renderField('Nascimento', 'patient.data_nascimento', item.patient?.data_nascimento)}
                                {renderField('Sexo', 'patient.sexo_id', item.patient?.sexo?.nome)}
                            </>
                        )}

                        {tab === 'destino' && (
                            <>
                                {renderField('Origem', 'origem_id', item.origem?.nome)}
                                {renderField('Destino', 'destino_id', item.destino?.nome)}
                            </>
                        )}

                        {tab === 'responsavel' && <>{renderField('Responsável', 'responsavel_id', item.responsavel?.name)}</>}

                        {tab === 'clavien' && item?.bloco_operatorios_count > 0 && (
                            <>{renderField('Clavien-Dindo', 'clavien_dindo_id', item.clavien_dindo?.nome)}</>
                        )}

                        {tab === 'bloco_operatorios' && item?.bloco_operatorios_count > 0 && (
                            <>
                                <h3 className="text-lg font-semibold">Blocos Operatórios</h3>
                                <ul className="list-disc pl-5">
                                    {item.bloco_operatorios?.map((bo: any) => (
                                        <li key={bo.id}>
                                            {bo.data_intervencao}
                                            {bo.bloco_operatorio_procedimentos && bo.bloco_operatorio_procedimentos.length > 0 && (
                                                <ul className="list-disc pl-5">
                                                    {bo.bloco_operatorio_procedimentos.map((bop: any) => (
                                                        <li key={bop.id}>{bop.procedimento?.nome}</li>
                                                    ))}
                                                </ul>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}

                        {tab === 'diagnosticos' && (
                            <>
                                <h3 className="text-lg font-semibold">Diagnósticos</h3>

                                <ul className="list-disc space-y-1 pl-5">
                                    {item.diagnosticos?.map((di: any) => {
                                        return (
                                            <li
                                                key={di.id}
                                                onClick={() => togglePrincipal(di.id)}
                                                className={`cursor-pointer ${di.pivot?.principal ? 'font-bold text-green-600' : ''}`}
                                            >
                                                <div className="flex items-center gap-2">
                                                    {renderField('Diagnóstico', 'diagnostico', di.nome)}

                                                    {di.pivot?.principal && (
                                                        <span className="rounded bg-green-100 px-2 py-1 text-xs text-green-700">Principal</span>
                                                    )}
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </>
                        )}

                        {tab === 'observacoes' && <>{renderField('Observações', 'observacoes', item.observacoes ?? '-')}</>}

                        {tab === 'complicacoes' && (
                            <>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 className="text-lg font-semibold">Complicações</h3>
                                    {editMode && (
                                        <button type="button" onClick={addComplicacao} className="rounded-md bg-blue-600 px-3 py-1 text-white">
                                            Adicionar Complicação
                                        </button>
                                    )}
                                </div>

                                <div className="space-y-5">
                                    {complicacaoInternamentos.map((ci: any, index: number) => {
                                        const ciId = getComplicacaoKey(ci);

                                        const selectedComplicacaoId = ci.complicacao_id ?? ci.complicacao?.id ?? '';

                                        const selectedResolucoes = ci.resolucaos?.map((r: any) => Number(r.id)) ?? [];

                                        return (
                                            <div
                                                key={ciId}
                                                className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md"
                                            >
                                                {/* Header */}

                                                <div className="flex items-center justify-between border-b bg-slate-50 px-5 py-3">
                                                    <div>
                                                        <h4 className="font-semibold text-slate-800">Complicação #{index + 1}</h4>

                                                        {!editMode && <p className="text-sm text-slate-500">{ci.complicacao?.nome ?? '-'}</p>}
                                                    </div>

                                                    {editMode && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeComplicacao(ci)}
                                                            className="rounded-lg p-2 text-red-600 transition hover:bg-red-50"
                                                        >
                                                            <Trash2 className="h-5 w-5" />
                                                        </button>
                                                    )}
                                                </div>

                                                <div className="space-y-6 p-5">
                                                    {/* Complicação */}

                                                    <div>
                                                        <label className="mb-2 block text-sm font-medium text-slate-700">Complicação</label>

                                                        {!editMode ? (
                                                            <div className="rounded-lg border bg-slate-50 px-3 py-2">
                                                                {ci.complicacao?.nome ?? '-'}
                                                            </div>
                                                        ) : (
                                                            <>
                                                                <Popover
                                                                    open={openComplicacao === ciId}
                                                                    onOpenChange={(open) => setOpenComplicacao(open ? ciId : null)}
                                                                >
                                                                    <PopoverTrigger asChild>
                                                                        <Button variant="outline" role="combobox" className="w-full justify-between">
                                                                            {selectedComplicacaoId
                                                                                ? (selectFields.complicacao_id ?? []).find(
                                                                                      (c: any) => c.id == selectedComplicacaoId,
                                                                                  )?.nome
                                                                                : 'Selecionar complicação'}
                                                                            <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                                                                        </Button>
                                                                    </PopoverTrigger>

                                                                    <PopoverContent className="w-full p-0">
                                                                        <div className="space-y-2 rounded-b-md border border-slate-200 bg-white p-3 shadow-sm">
                                                                            <input
                                                                                type="text"
                                                                                placeholder="Pesquisar complicação..."
                                                                                value={searchComplicacao}
                                                                                onChange={(e) => setSearchComplicacao(e.target.value)}
                                                                                className="w-full rounded-md border border-neutral-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                                                            />
                                                                            <div className="max-h-60 overflow-auto">
                                                                                {(filteredComplicacoes.length ?? 0) > 0 ? (
                                                                                    filteredComplicacoes.map((item: any) => {
                                                                                        const isSelected = item.id == selectedComplicacaoId;
                                                                                        return (
                                                                                            <button
                                                                                                key={item.id}
                                                                                                type="button"
                                                                                                className={cn(
                                                                                                    'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition',
                                                                                                    isSelected
                                                                                                        ? 'bg-blue-50 text-blue-700'
                                                                                                        : 'hover:bg-slate-100',
                                                                                                )}
                                                                                                onClick={() => {
                                                                                                    updateComplicacaoInternamento(ci, {
                                                                                                        complicacao_id: item.id,
                                                                                                    });
                                                                                                    setOpenComplicacao(null);
                                                                                                }}
                                                                                            >
                                                                                                <span>{item.nome}</span>
                                                                                                <Check
                                                                                                    className={cn(
                                                                                                        'h-4 w-4',
                                                                                                        isSelected ? 'opacity-100' : 'opacity-0',
                                                                                                    )}
                                                                                                />
                                                                                            </button>
                                                                                        );
                                                                                    })
                                                                                ) : (
                                                                                    <div className="rounded-md bg-slate-50 px-3 py-2 text-sm text-slate-500">
                                                                                        Nenhuma complicação encontrada.
                                                                                    </div>
                                                                                )}
                                                                            </div>
                                                                        </div>
                                                                    </PopoverContent>
                                                                </Popover>
                                                            </>
                                                        )}
                                                    </div>

                                                    {/* Resoluções */}

                                                    <div>
                                                        <label className="mb-2 block text-sm font-medium text-slate-700">Resoluções</label>

                                                        {!editMode ? (
                                                            <div className="flex flex-wrap gap-2">
                                                                {ci.resolucaos?.length ? (
                                                                    ci.resolucaos.map((r: any) => (
                                                                        <span
                                                                            key={r.id}
                                                                            className="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700"
                                                                        >
                                                                            {r.nome}
                                                                        </span>
                                                                    ))
                                                                ) : (
                                                                    <span className="text-slate-500">Nenhuma resolução</span>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <>
                                                                <div className="flex flex-wrap items-center gap-3">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => openResolucaoEditor(ci)}
                                                                        className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"
                                                                    >
                                                                        Editar Resoluções
                                                                    </button>

                                                                    <div className="flex flex-wrap gap-2">
                                                                        {ci.resolucaos?.length ? (
                                                                            ci.resolucaos.map((r: any) => (
                                                                                <span
                                                                                    key={r.id}
                                                                                    className="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700"
                                                                                >
                                                                                    {r.nome}
                                                                                </span>
                                                                            ))
                                                                        ) : (
                                                                            <span className="text-slate-500">Nenhuma resolução</span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </>
                        )}
                    </div>

                    <Dialog open={openResolucaoModal} onOpenChange={setOpenResolucaoModal}>
                        <DialogContent className="max-w-4xl">
                            {' '}
                            {/* modal mais largo */}
                            <DialogHeader>
                                <DialogTitle className="text-lg">Editar Resoluções</DialogTitle>
                                <DialogDescription className="text-sm">Selecione as resoluções aplicáveis para esta complicação.</DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4 text-sm">
                                {' '}
                                {/* letra mais pequena */}
                                <input
                                    type="text"
                                    placeholder="Pesquisar resolução..."
                                    value={searchResolucao}
                                    onChange={(e) => setSearchResolucao(e.target.value)}
                                    className="w-full rounded-md border border-neutral-300 bg-slate-50 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                />
                                <div className="max-h-[55vh] overflow-auto">
                                    <div className="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                        {filteredResolucoes.map((opt: any) => {
                                            const checked = currentResolucaoSelection.includes(opt.id);
                                            return (
                                                <label
                                                    key={opt.id}
                                                    className={`flex cursor-pointer items-center gap-3 rounded-lg border p-3 text-xs transition ${
                                                        checked ? 'border-emerald-500 bg-emerald-50' : 'hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={checked}
                                                        onChange={(e) => {
                                                            const next = e.target.checked
                                                                ? [...currentResolucaoSelection, opt.id]
                                                                : currentResolucaoSelection.filter((id) => id !== opt.id);
                                                            setCurrentResolucaoSelection(next);
                                                        }}
                                                    />
                                                    <span>{opt.nome}</span>
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                            <DialogFooter className="mt-4 flex justify-end gap-2">
                                <DialogClose asChild>
                                    <button
                                        type="button"
                                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                                    >
                                        Cancelar
                                    </button>
                                </DialogClose>
                                <button type="button" onClick={saveResolucaoEditor} className="rounded-md bg-blue-600 px-4 py-2 text-sm text-white">
                                    Guardar
                                </button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                    {editMode && (
                        <div className="flex justify-end">
                            <button onClick={save} className="rounded-md bg-green-600 px-4 py-2 text-white">
                                Guardar Alterações
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
