import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Loader2, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import toast from 'react-hot-toast';
import ClickableLoadingItem from '../ClickableLoadingItem';

const INITIAL_TABS = ['paciente', 'internamento', 'diagnosticos'];

// Nomes amigáveis para os separadores (evita mostrar chaves internas ao utilizador)
const TAB_LABELS: Record<string, string> = {
    paciente: 'Paciente',
    internamento: 'Internamento',
    diagnosticos: 'Diagnósticos',
    bloco_operatorios: 'Bloco Operatório',
    complicacoes: 'Complicações',
    clavien: 'Clavien-Dindo',
    destino: 'Origem / Destino',
    observacoes: 'Observações',
    responsavel: 'Responsável',
};

// Em que separador vive cada campo — usado para assinalar erros do backend
// no separador certo, mesmo que o utilizador esteja a ver outro.
const FIELD_TAB_MAP: Record<string, string> = {
    data_entrada: 'internamento',
    data_alta: 'internamento',
    data_saida: 'internamento',
    dias_internamento: 'internamento',
    falecido: 'internamento',
    mortalidade_esperada: 'internamento',
    'patient.processo': 'paciente',
    'patient.data_nascimento': 'paciente',
    'patient.sexo_id': 'paciente',
    origem_id: 'destino',
    destino_id: 'destino',
    responsavel_id: 'responsavel',
    clavien_dindo_id: 'clavien',
    observacoes: 'observacoes',
};

function tabForErrorKey(key: string): string | undefined {
    if (key.startsWith('complicacao_internamentos')) return 'complicacoes';
    return FIELD_TAB_MAP[key];
}

// Quando a complicação surgiu em relação à data de alta (ver MOMENTO_OPTIONS
// no modelo ComplicacaoInternamento, no backend).
const MOMENTO_OPTIONS: { value: string; label: string; badgeClass: string }[] = [
    { value: 'antes_alta', label: 'Antes da Alta', badgeClass: 'bg-slate-200 text-slate-700' },
    { value: 'ate_30_dias', label: '≤ 30 dias após a alta', badgeClass: 'bg-sky-100 text-sky-700' },
    { value: '31_90_dias', label: '31–90 dias após a alta', badgeClass: 'bg-amber-100 text-amber-700' },
    { value: 'mais_90_dias', label: '> 90 dias após a alta', badgeClass: 'bg-rose-100 text-rose-700' },
];

// Mesma paleta usada em Internamento/Index.tsx — verde-cirúrgico como único
// acento, papel frio, âmbar/vermelho reservados a avisos e desfechos críticos.
const INK = '#17241F';
const PAPER = '#F5F6F3';
const LINE = '#DCE1DC';
const CLINICAL = '#1E6F5C';
const CLINICAL_SOFT = '#E3EFEA';
const ROSE = '#A32F26';
const MONO_FONT = "'IBM Plex Mono', ui-monospace, monospace";
const SANS_FONT = "'IBM Plex Sans', ui-sans-serif, system-ui, sans-serif";
// Campos cujo valor é uma data/contagem — alinhados em mono, como na listagem.
const MONO_FIELDS = ['data_entrada', 'data_alta', 'data_saida', 'dias_internamento'];
const FIELD_INPUT_CLASS =
    'ml-4 rounded-[4px] border border-[#C9D1CB] bg-white px-2.5 py-1.5 text-sm text-[#17241F] outline-none transition focus:border-[#1E6F5C] focus:ring-2 focus:ring-[#1E6F5C]/20 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-100';

export default function InternamentoModal({ open, onClose, item, onSave }: any) {
    const [tab, setTab] = useState('internamento');
    const [editMode, setEditMode] = useState(false);
    const [form, setForm] = useState(item || {});
    const [backendErrors, setBackendErrors] = useState<any>({});
    const [searchComplicacao, setSearchComplicacao] = useState('');
    const [searchResolucao, setSearchResolucao] = useState('');

    const [openComplicacao, setOpenComplicacao] = useState<number | null>(null);
    const [openResolucaoModal, setOpenResolucaoModal] = useState(false);
    const [currentResolucaoCi, setCurrentResolucaoCi] = useState<any>(null);
    const [currentResolucaoSelection, setCurrentResolucaoSelection] = useState<number[]>([]);
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const [isDirty, setIsDirty] = useState(false);
    const [saving, setSaving] = useState(false);

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

    // Calculado a cada render a partir do `item` atual — o modal é montado uma
    // única vez pela página e reutilizado para internamentos diferentes, por
    // isso os separadores não podem ficar "congelados" num useState inicial.
    const tabs =
        item?.bloco_operatorios_count > 0
            ? [...INITIAL_TABS, 'bloco_operatorios', 'complicacoes', 'clavien', 'destino', 'observacoes', 'responsavel']
            : [...INITIAL_TABS, 'destino', 'observacoes', 'responsavel'];

    const pageProps: any = usePage().props;

    // A página mantém uma única instância deste modal e só troca a prop `item`
    // (ver Internamento/Index.tsx), em vez de o remontar — por isso o estado
    // do formulário tem de ser explicitamente sincronizado sempre que o
    // internamento em edição muda, ou ficaria "preso" nos dados do anterior.
    useEffect(() => {
        setForm(item || {});
        setEditMode(false);
        setIsDirty(false);
        setBackendErrors({});
        setTab('internamento');
    }, [item?.id]);

    // Fechar com a tecla Esc, tal como o clique no botão "Fechar" ou no fundo.
    useEffect(() => {
        if (!open) return;

        function onKeyDown(e: KeyboardEvent) {
            if (e.key === 'Escape' && !openResolucaoModal) {
                handleClose();
            }
        }

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, editMode, isDirty, openResolucaoModal]);

    if (!open) return null;

    const editableFields = ['observacoes', 'mortalidade_esperada', 'bloquear', 'data_alta', 'principal', 'falecido_apos_alta'];
    const booleanFields = ['mortalidade_esperada', 'falecido', 'bloquear', 'principal', 'falecido_apos_alta'];
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
        setIsDirty(true);
    }

    function getComplicacaoKey(ci: any) {
        return ci.id ?? ci._tempId;
    }

    function addComplicacao() {
        setComplicacaoInternamentos([...complicacaoInternamentos, { _tempId: Date.now(), complicacao_id: null, momento: null, resolucaos: [] }]);
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
        // Sair do modo de edição com alterações por guardar exige confirmação,
        // para não perder dados sem querer nem guardar algo indesejado.
        if (editMode && isDirty) {
            const discard = window.confirm('Existem alterações não guardadas. Deseja descartá-las e sair da edição?');
            if (!discard) return;

            setForm(item || {});
            setBackendErrors({});
            setIsDirty(false);
        }

        setEditMode(!editMode);
    }

    function handleClose() {
        if (editMode && isDirty) {
            const discard = window.confirm('Existem alterações não guardadas. Tem a certeza que quer fechar sem guardar?');
            if (!discard) return;
        }

        onClose();
    }

    function handleChange(key: string, value: any) {
        setForm({
            ...form,
            [key]: value,
        });
        setIsDirty(true);
    }

    function save() {
        const payload = {
            ...form,
            complicacao_internamentos: complicacaoInternamentos.map((ci: any) => ({
                id: ci.id,
                complicacao_id: ci.complicacao_id,
                momento: ci.momento,
                resolucaos: ci.resolucaos?.map((r: any) => ({ id: r.id })) ?? [],
            })),
        };

        const targetId = form.id ?? item?.id;

        if (!targetId) {
            console.error('Cannot save internamento: missing id on form and item', { form, item });
            toast.error('Não foi possível guardar: identificador do internamento em falta.');
            return;
        }

        setSaving(true);

        router.put(`/internamentos/${targetId}`, payload, {
            preserveState: true,
            preserveScroll: true,

            onError: (errors) => {
                setBackendErrors(errors); // ✔️ mostra erros no modal
                toast.error('Existem erros no formulário.');

                // Leva o utilizador diretamente ao primeiro separador com erro,
                // em vez de o deixar à procura de qual mudou de cor.
                const firstErroredTab = Object.keys(errors).map(tabForErrorKey).find(Boolean);
                if (firstErroredTab) setTab(firstErroredTab);
            },

            onSuccess: () => {
                setBackendErrors({});
                setIsDirty(false);
                setEditMode(false);
                toast.success('Internamento atualizado com sucesso!');

                if (typeof onSave === 'function') {
                    try {
                        // garantir que o id está presente no payload para o caller
                        const payloadWithId = { id: form.id ?? item?.id, ...payload };
                        onSave(payloadWithId); // ✔️ devolve dados reais
                    } catch (e) {
                        console.error('onSave callback error (save):', e);
                        toast.error('Erro interno ao notificar atualizações.');
                    }
                }
            },

            onFinish: () => {
                setSaving(false);
            },
        });
    }
    const erroredTabs = new Set(Object.keys(backendErrors).map(tabForErrorKey).filter(Boolean) as string[]);

    function ResolucaoChips({ resolucaos }: { resolucaos?: any[] }) {
        if (!resolucaos?.length) {
            return <span className="text-slate-500">Nenhuma resolução</span>;
        }

        return (
            <div className="flex flex-wrap gap-2">
                {resolucaos.map((r: any) => (
                    <span key={r.id} className="rounded-full bg-emerald-100 px-3 py-1 text-sm text-emerald-700">
                        {r.nome}
                    </span>
                ))}
            </div>
        );
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
        const isMonoField = MONO_FIELDS.includes(key);

        return (
            <div className="flex border-b py-2.5" style={{ borderColor: LINE }}>
                <span className="w-44 shrink-0 text-sm font-medium" style={{ color: '#45524C' }}>
                    {label}
                </span>
                {!editMode && (
                    <span className="ml-4 text-sm" style={{ color: INK, fontFamily: isMonoField ? MONO_FONT : undefined }}>
                        {isBooleanField ? (value ? 'Sim' : 'Não') : (value ?? '-')}
                        {hasSelectOptions && value && selectFields[key].find((opt: any) => opt.id === value)?.nome}
                    </span>
                )}

                {editMode &&
                    (!isEditable ? (
                        <span className="ml-4 text-sm opacity-70" style={{ fontFamily: isMonoField ? MONO_FONT : undefined }}>
                            {isBooleanField ? (value ? 'Sim' : 'Não') : (value ?? '-')}
                        </span>
                    ) : isBoolean ? (
                        <div>
                            <div className="flex w-full flex-col">
                                <input
                                    type="checkbox"
                                    className="ml-4 h-4 w-4 accent-[#1E6F5C]"
                                    checked={Number(form[key]) === 1}
                                    onChange={(e) => handleChange(key, e.target.checked ? 1 : 0)}
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ) : hasSelectOptions ? (
                        <div>
                            <div className="flex w-full flex-col">
                                <select className={FIELD_INPUT_CLASS} value={form[key] ?? ''} onChange={(e) => handleChange(key, e.target.value)}>
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
                                    className={FIELD_INPUT_CLASS}
                                    style={{ fontFamily: MONO_FONT }}
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
                                    className="w-full rounded-[4px] border p-2 text-sm outline-none focus:ring-2 focus:ring-[#1E6F5C]/20"
                                    style={{ borderColor: '#C9D1CB' }}
                                />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors.join(', ')}</span>}
                        </div>
                    ) : (
                        <div>
                            <div className="flex w-full flex-col">
                                <input className={FIELD_INPUT_CLASS} value={form[key] ?? ''} onChange={(e) => handleChange(key, e.target.value)} />
                            </div>
                            {fieldErrors && <span className="mt-1 w-full text-sm text-red-600">{fieldErrors}</span>}
                        </div>
                    ))}
                {info && <span className="text-muted-foreground ml-2 px-2 py-1">{info}</span>}
            </div>
        );
    }

    const togglePrincipal = async (diagnosticoId: number) => {
        setLoadingId(diagnosticoId);

        await router.post(
            `/registos-cirurgicos/${item.id}/diagnosticos/${diagnosticoId}/principal`,
            {},
            {
                preserveScroll: true,
                preserveState: true,

                onSuccess: () => {
                    toast.success('Diagnóstico principal atualizado com sucesso!');

                    if (typeof onSave === 'function') {
                        try {
                            // Defensivamente garantir que temos um array antes de mapear
                            const sourceDiagnosticos = Array.isArray(form?.diagnosticos)
                                ? form!.diagnosticos
                                : Array.isArray(item?.diagnosticos)
                                ? item!.diagnosticos
                                : [];

                            const updatedDiagnosticos = sourceDiagnosticos.map((d: any) => ({
                                ...d,
                                pivot: {
                                    ...d?.pivot,
                                    principal: d.id === diagnosticoId,
                                },
                            }));

                            const payloadWithId = { id: form.id ?? item?.id, ...form, diagnosticos: updatedDiagnosticos };

                            onSave(payloadWithId);
                        } catch (e) {
                            console.error('onSave callback error (togglePrincipal):', e);
                            toast.error('Erro interno ao processar atualização do diagnóstico.');
                        }
                    }

                    router.reload({ only: ['item'] });
                },

                onError: () => {
                    toast.error('Erro ao atualizar diagnóstico principal.');
                },

                onFinish: () => {
                    setLoadingId(null);
                },
            },
        );
    };

    const processo = item?.patient?.processo;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            onMouseDown={(e) => {
                if (e.target === e.currentTarget) handleClose();
            }}
            style={{ fontFamily: SANS_FONT }}
        >
            <div
                className="relative flex h-[75vh] w-[80vw] flex-col overflow-hidden rounded-[6px] border bg-white dark:bg-neutral-900"
                style={{ borderColor: LINE }}
            >
                {/* Cabeçalho */}
                <div className="flex items-center justify-between border-b px-6 py-4" style={{ borderColor: LINE, backgroundColor: PAPER }}>
                    <div className="flex items-baseline gap-3">
                        <h2 className="text-lg font-semibold" style={{ color: INK }}>
                            {editMode ? 'Editar Internamento' : 'Detalhes do Internamento'}
                        </h2>
                        {processo && (
                            <span className="text-sm tabular-nums" style={{ color: '#5B685F', fontFamily: MONO_FONT }}>
                                Processo {processo}
                            </span>
                        )}
                        {editMode && isDirty && (
                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                Alterações por guardar
                            </span>
                        )}
                    </div>

                    <div className="flex gap-2">
                        <button
                            onClick={toggleMode}
                            disabled={saving}
                            className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA] disabled:cursor-not-allowed disabled:opacity-60"
                            style={{ borderColor: CLINICAL, color: CLINICAL }}
                        >
                            {editMode ? 'Cancelar Edição' : 'Editar'}
                        </button>

                        <button
                            onClick={handleClose}
                            disabled={saving}
                            className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#FBE7E4] disabled:cursor-not-allowed disabled:opacity-60"
                            style={{ borderColor: '#C9D1CB', color: '#45524C' }}
                        >
                            Fechar
                        </button>
                    </div>
                </div>

                {/* Corpo: navegador lateral + conteúdo do separador */}
                <div className="flex min-h-0 flex-1">
                    <nav className="flex w-48 shrink-0 flex-col gap-0.5 overflow-y-auto border-r p-3" style={{ borderColor: LINE }}>
                        {tabs.map((t) => (
                            <button
                                key={t}
                                onClick={() => setTab(t)}
                                aria-current={tab === t}
                                className="relative rounded-[4px] px-3 py-2 text-left text-sm font-medium transition"
                                style={
                                    tab === t
                                        ? { backgroundColor: CLINICAL_SOFT, color: CLINICAL }
                                        : { color: '#45524C' }
                                }
                            >
                                {TAB_LABELS[t] ?? t}
                                {erroredTabs.has(t) && (
                                    <span
                                        title="Este separador tem campos com erros"
                                        className="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-neutral-900"
                                    />
                                )}
                            </button>
                        ))}
                    </nav>

                    <div className="min-w-0 flex-1 space-y-2 overflow-y-auto p-6">
                        {tab === 'internamento' && (
                            <>
                                {renderField('Processo', 'processo', item?.patient.processo)}
                                {renderField('Entrada', 'data_entrada', item.data_entrada)}
                                {renderField('Alta', 'data_alta', item.data_alta, 'Colocar aqui a data da alta clínica, se for o caso.')}
                                {renderField('Saída', 'data_saida', item.data_saida)}
                                {renderField('Dias Internamento', 'dias_internamento', item.dias_internamento)}
                                {renderField('Falecido', 'falecido', item.falecido)}
                                {item.falecido ? renderField('Mortalidade Esperada', 'mortalidade_esperada', item.mortalidade_esperada) : null}
                                {renderField(
                                    'Faleceu Após a Alta',
                                    'falecido_apos_alta',
                                    item.falecido_apos_alta,
                                    'Distingue de quem faleceu ainda no serviço (destino Falecido, antes da alta).',
                                )}
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
                                <h3 className="text-base font-semibold" style={{ color: "#17241F" }}>Blocos Operatórios</h3>
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
                                <h3 className="text-base font-semibold" style={{ color: "#17241F" }}>Diagnósticos</h3>

                                <ul className="list-disc space-y-1 pl-5">
                                    {item.diagnosticos?.map((di: any) => (
                                        <ClickableLoadingItem
                                            key={di.id}
                                            id={di.id}
                                            isPrincipal={di.pivot?.principal}
                                            isLoading={loadingId === di.id}
                                            onClick={togglePrincipal}
                                        >
                                            {renderField('Diagnóstico', 'diagnostico', di.nome)}
                                        </ClickableLoadingItem>
                                    ))}
                                </ul>
                            </>
                        )}

                        {tab === 'observacoes' && <>{renderField('Observações', 'observacoes', item.observacoes ?? '-')}</>}

                        {tab === 'complicacoes' && (
                            <>
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 className="text-base font-semibold" style={{ color: "#17241F" }}>Complicações</h3>
                                    {editMode && (
                                        <button
                                            type="button"
                                            onClick={addComplicacao}
                                            className="rounded-[4px] border px-3 py-1.5 text-sm font-medium transition hover:bg-[#E3EFEA]"
                                            style={{ borderColor: CLINICAL, color: CLINICAL }}
                                        >
                                            Adicionar Complicação
                                        </button>
                                    )}
                                </div>

                                <div className="space-y-4">
                                    {complicacaoInternamentos.map((ci: any, index: number) => {
                                        const ciId = getComplicacaoKey(ci);

                                        const selectedComplicacaoId = ci.complicacao_id ?? ci.complicacao?.id ?? '';

                                        // `ci.complicacao` é o objeto relacionado tal como veio do servidor;
                                        // ao escolher outra complicação só o id muda localmente, por isso o
                                        // nome mostrado fora do modo de edição tem de vir sempre da lista de
                                        // opções (mais recente) e não do objeto aninhado, que ficaria desatualizado.
                                        const selectedComplicacaoNome = selectedComplicacaoId
                                            ? ((selectFields.complicacao_id ?? []).find((c: any) => c.id == selectedComplicacaoId)?.nome ??
                                              ci.complicacao?.nome)
                                            : ci.complicacao?.nome;

                                        return (
                                            <div
                                                key={ciId}
                                                className="overflow-hidden rounded-[6px] border transition"
                                                style={{ borderColor: LINE }}
                                            >
                                                {/* Header */}

                                                <div
                                                    className="flex items-center justify-between border-b px-5 py-3"
                                                    style={{ borderColor: LINE, backgroundColor: PAPER }}
                                                >
                                                    <div>
                                                        <h4 className="text-sm font-semibold" style={{ color: INK }}>
                                                            Complicação #{index + 1}
                                                        </h4>

                                                        {!editMode && <p className="text-sm text-[#5B685F]">{selectedComplicacaoNome ?? '-'}</p>}
                                                    </div>

                                                    {editMode && (
                                                        <button
                                                            type="button"
                                                            onClick={() => removeComplicacao(ci)}
                                                            className="rounded-[4px] p-2 transition hover:bg-[#FBE7E4]"
                                                            style={{ color: ROSE }}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>

                                                <div className="space-y-6 p-5">
                                                    {/* Complicação */}

                                                    <div>
                                                        <label className="mb-2 block text-xs font-medium" style={{ color: "#45524C" }}>Complicação</label>

                                                        {!editMode ? (
                                                            <div className="rounded-[4px] border px-3 py-2 text-sm" style={{ borderColor: LINE, backgroundColor: PAPER, color: INK }}>
                                                                {selectedComplicacaoNome ?? '-'}
                                                            </div>
                                                        ) : (
                                                            <>
                                                                <Popover
                                                                    open={openComplicacao === ciId}
                                                                    onOpenChange={(open) => setOpenComplicacao(open ? ciId : null)}
                                                                >
                                                                    <PopoverTrigger asChild>
                                                                        <Button variant="outline" role="combobox" className="w-full justify-between">
                                                                            {selectedComplicacaoId ? selectedComplicacaoNome : 'Selecionar complicação'}
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
                                                                                    filteredComplicacoes.map((opt: any) => {
                                                                                        const isSelected = opt.id == selectedComplicacaoId;
                                                                                        return (
                                                                                            <button
                                                                                                key={opt.id}
                                                                                                type="button"
                                                                                                className={cn(
                                                                                                    'flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm transition',
                                                                                                    isSelected
                                                                                                        ? 'bg-blue-50 text-blue-700'
                                                                                                        : 'hover:bg-slate-100',
                                                                                                )}
                                                                                                onClick={() => {
                                                                                                    updateComplicacaoInternamento(ci, {
                                                                                                        complicacao_id: opt.id,
                                                                                                        // Mantém o objeto relacionado sincronizado com o id
                                                                                                        // escolhido, para qualquer sítio que ainda o leia
                                                                                                        // diretamente mostrar sempre o nome certo.
                                                                                                        complicacao: { id: opt.id, nome: opt.nome },
                                                                                                    });
                                                                                                    setOpenComplicacao(null);
                                                                                                }}
                                                                                            >
                                                                                                <span>{opt.nome}</span>
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

                                                    {/* Momento (tempo até à complicação) */}

                                                    <div>
                                                        <label className="mb-2 block text-xs font-medium" style={{ color: "#45524C" }}>Momento</label>

                                                        {!editMode ? (
                                                            <span
                                                                className={cn(
                                                                    'inline-block rounded-full px-3 py-1 text-sm',
                                                                    MOMENTO_OPTIONS.find((m) => m.value === ci.momento)?.badgeClass ??
                                                                        'bg-slate-100 text-slate-500',
                                                                )}
                                                            >
                                                                {MOMENTO_OPTIONS.find((m) => m.value === ci.momento)?.label ?? 'Não definido'}
                                                            </span>
                                                        ) : (
                                                            <select
                                                                className={FIELD_INPUT_CLASS.replace('ml-4 ', '')}
                                                                value={ci.momento ?? ''}
                                                                onChange={(e) =>
                                                                    updateComplicacaoInternamento(ci, { momento: e.target.value || null })
                                                                }
                                                            >
                                                                <option value="">Não definido</option>
                                                                {MOMENTO_OPTIONS.map((m) => (
                                                                    <option key={m.value} value={m.value}>
                                                                        {m.label}
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        )}
                                                    </div>

                                                    {/* Resoluções */}

                                                    <div>
                                                        <label className="mb-2 block text-xs font-medium" style={{ color: "#45524C" }}>Resoluções</label>

                                                        {!editMode ? (
                                                            <ResolucaoChips resolucaos={ci.resolucaos} />
                                                        ) : (
                                                            <div className="flex flex-wrap items-center gap-3">
                                                                <button
                                                                    type="button"
                                                                    onClick={() => openResolucaoEditor(ci)}
                                                                    className="rounded-md border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 transition hover:border-slate-400 hover:bg-slate-100"
                                                                >
                                                                    Editar Resoluções
                                                                </button>

                                                                <ResolucaoChips resolucaos={ci.resolucaos} />
                                                            </div>
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
                                <button
                                    type="button"
                                    onClick={saveResolucaoEditor}
                                    className="rounded-[4px] px-4 py-2 text-sm font-medium text-white"
                                    style={{ backgroundColor: CLINICAL }}
                                >
                                    Guardar
                                </button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>

                {editMode && (
                    <div className="flex items-center justify-end gap-3 border-t px-6 py-3" style={{ borderColor: LINE, backgroundColor: PAPER }}>
                        {!isDirty && <span className="text-sm text-[#5B685F]">Sem alterações por guardar</span>}
                        <button
                            onClick={save}
                            disabled={saving || !isDirty}
                            className="flex items-center gap-2 rounded-[4px] px-4 py-2 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-50"
                            style={{ backgroundColor: CLINICAL }}
                        >
                            {saving && <Loader2 className="h-4 w-4 animate-spin" />}
                            {saving ? 'A guardar…' : 'Guardar Alterações'}
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}
