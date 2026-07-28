import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';

const INITIAL_TABS = ['internamento', 'paciente', 'diagnosticos', 'destino', 'responsavel'];

export default function InternamentoModal({ open, onClose, item, onSave }: any) {
    const [tab, setTab] = useState('internamento');
    const [editMode, setEditMode] = useState(false);
    const [form, setForm] = useState(item || {});
    const [backendErrors, setBackendErrors] = useState<any>({});
    const [searchComplicacao, setSearchComplicacao] = useState('');
    const [searchResolucao, setSearchResolucao] = useState('');

    const [tabs, setTabs] = useState(INITIAL_TABS);

    useEffect(() => {
        if (!item) {
            setForm({});
            setTabs([...INITIAL_TABS]); // força novo array
            return;
        }

        setForm(item);

        if (item.bloco_operatorios_count > 0) {
            setTabs((prev) => (prev.includes('clavien') ? prev : [...prev, 'clavien', 'bloco_operatorios', 'complicacoes', 'observacoes']));
        } else {
            setTabs((prev) => (prev.includes('clavien') ? prev : [...prev, 'observacoes']));
        }
    }, [item]);

    const pageProps: any = usePage().props;

    if (!open) return null;

    const editableFields = ['observacoes', 'clavien_dindo_id', 'falecido', 'bloquear', 'data_alta'];
    const booleanFields = ['falecido', 'bloquear'];
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
    function renderField(label: string, key: string, value: any) {
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
                        <span className="ml-4 opacity-70">{String(value)}</span>
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
            </div>
        );
    }

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
                                {renderField('Alta', 'data_alta', item.data_alta)}
                                {renderField('Saída', 'data_saida', item.data_saida)}
                                {renderField('Dias Internamento', 'dias_internamento', item.dias_internamento)}
                                {renderField('Falecido', 'falecido', item.falecido)}
                                {renderField('Bloquear', 'bloquear', item.bloquear)}
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
                                <ul className="list-disc pl-5">
                                    {item.diagnosticos?.map((di: any) => (
                                        <li key={di.id} className={di.principal ? 'font-bold text-green-600' : ''}>
                                            {di.nome}
                                        </li>
                                    ))}
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

                                {complicacaoInternamentos.length === 0 ? (
                                    <div className="rounded-md border border-neutral-300 bg-neutral-50 p-4 text-sm text-neutral-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-300">
                                        Nenhuma complicação registada.
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {complicacaoInternamentos.map((ci: any) => {
                                            const ciId = getComplicacaoKey(ci);
                                            const selectedComplicacaoId = ci.complicacao_id ?? ci.complicacao?.id ?? '';
                                            const selectedResolucoes = ci.resolucaos?.map((r: any) => Number(r.id)) ?? [];

                                            return (
                                                <div
                                                    key={ciId}
                                                    className="rounded-md border border-neutral-200 bg-neutral-50 p-4 dark:border-neutral-700 dark:bg-neutral-950"
                                                >
                                                    {/* Complicação */}
                                                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                        <span className="font-semibold">Complicação</span>

                                                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-4">
                                                            {!editMode ? (
                                                                <span>
                                                                    {ci.complicacao?.nome ??
                                                                        selectFields.complicacao_id?.find(
                                                                            (opt: any) => opt.id === selectedComplicacaoId,
                                                                        )?.nome ??
                                                                        '-'}
                                                                </span>
                                                            ) : (
                                                                <div className="flex flex-col gap-2">
                                                                    <input
                                                                        type="text"
                                                                        placeholder="Pesquisar complicação..."
                                                                        className="rounded-md border border-neutral-300 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                                                        value={searchComplicacao}
                                                                        onChange={(e) => setSearchComplicacao(e.target.value)}
                                                                    />

                                                                    <select
                                                                        className="w-full rounded-md border border-neutral-300 bg-white px-2 py-1 sm:w-80 dark:border-neutral-700 dark:bg-neutral-800"
                                                                        value={String(selectedComplicacaoId ?? '')}
                                                                        onChange={(e) => {
                                                                            const value = Number(e.target.value);
                                                                            updateComplicacaoInternamento(ci, { complicacao_id: value });
                                                                        }}
                                                                    >
                                                                        <option value="">Selecione...</option>
                                                                        {filteredComplicacoes.map((opt: any) => (
                                                                            <option key={opt.id} value={String(opt.id)}>
                                                                                {opt.nome}
                                                                            </option>
                                                                        ))}
                                                                    </select>
                                                                </div>
                                                            )}

                                                            {editMode && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => removeComplicacao(ci)}
                                                                    className="rounded-md bg-red-600 px-3 py-1 text-white"
                                                                >
                                                                    Remover
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Resoluções */}
                                                    <div className="mt-3 flex flex-col gap-2">
                                                        <span className="font-semibold">Resoluções</span>

                                                        {!editMode ? (
                                                            <span>
                                                                {ci.resolucaos?.length
                                                                    ? ci.resolucaos
                                                                          .map(
                                                                              (r: any) =>
                                                                                  r.nome ??
                                                                                  selectFields.resolucao_id?.find((opt: any) => opt.id === r.id)
                                                                                      ?.nome,
                                                                          )
                                                                          .filter(Boolean)
                                                                    : '-'}
                                                            </span>
                                                        ) : (
                                                            <div className="flex flex-col gap-2">
                                                                {/* Pesquisa (opcional) */}
                                                                <input
                                                                    type="text"
                                                                    placeholder="Pesquisar resolução..."
                                                                    className="rounded-md border border-neutral-300 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                                                                    value={searchResolucao}
                                                                    onChange={(e) => setSearchResolucao(e.target.value)}
                                                                />

                                                                {/* Checkboxes em duas colunas */}
                                                                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                                    {filteredResolucoes.map((opt: any) => {
                                                                        const checked = selectedResolucoes.includes(opt.id);

                                                                        return (
                                                                            <label key={opt.id} className="flex items-center gap-2">
                                                                                <input
                                                                                    type="checkbox"
                                                                                    checked={checked}
                                                                                    onChange={(e) => {
                                                                                        let updated;

                                                                                        if (e.target.checked) {
                                                                                            updated = [...selectedResolucoes, opt.id];
                                                                                        } else {
                                                                                            updated = selectedResolucoes.filter(
                                                                                                (id) => id !== opt.id,
                                                                                            );
                                                                                        }

                                                                                        updateComplicacaoInternamento(ci, {
                                                                                            resolucaos: updated.map((id) => ({ id })),
                                                                                        });
                                                                                    }}
                                                                                    className="h-4 w-4"
                                                                                />
                                                                                <span>{opt.nome}</span>
                                                                            </label>
                                                                        );
                                                                    })}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </>
                        )}
                    </div>

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
