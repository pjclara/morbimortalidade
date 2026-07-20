import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { useEffect, useState } from 'react';

export default function InternamentoModal({ open, onClose, item, onSave }: any) {
    const [tab, setTab] = useState('internamento');
    const [editMode, setEditMode] = useState(false);
    const [form, setForm] = useState(item || {});

    const INITIAL_TABS = ['internamento', 'paciente', 'diagnosticos', 'destino', 'responsavel'];

    const [tabs, setTabs] = useState(INITIAL_TABS);

    useEffect(() => {
        if (!item) {
            setForm({});
            setTabs([...INITIAL_TABS]); // força novo array
            return;
        }

        setForm(item);

        if (item.bloco_operatorios_count > 0) {
            setTabs((prev) => (prev.includes('clavien') ? prev : [...prev, 'clavien', 'bloco_operatorios']));
        }
    }, [item]);

    if (!open) return null;

    const editableFields = ['observacoes', 'clavien_dindo_id', 'falecido', 'bloquear'];

    // 🔥 Converte objetos em arrays automaticamente
    const selectFields: any = {
        destino_id: item?.destino_options ? Object.entries(item.destino_options).map(([nome, id]) => ({ id, nome })) : null,

        origem_id: item?.origem_options ? Object.entries(item.origem_options).map(([nome, id]) => ({ id, nome })) : null,

        responsavel_id: item?.responsavel_options ? Object.entries(item.responsavel_options).map(([nome, id]) => ({ id, nome })) : null,

        clavien_dindo_id: item?.clavien_options ? Object.entries(item.clavien_options).map(([nome, id]) => ({ id, nome })) : null,
    };

    const booleanFields = ['falecido', 'bloquear'];

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
        if (onSave) onSave(form);
        setEditMode(false);
    }

    function renderField(label: string, key: string, value: any) {
        const isBoolean = value === 0 || value === 1 || typeof value === 'boolean';
        const isBooleanField = booleanFields.includes(key);
        const isEditable = editableFields.includes(key);
        const hasSelectOptions = Array.isArray(selectFields[key]);

        return (
            <div className="flex justify-between border-b border-neutral-300 py-2 dark:border-neutral-700">
                <span className="font-semibold">{label}</span>

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
                        <input
                            type="checkbox"
                            className="ml-4 h-5 w-5"
                            checked={Number(form[key]) === 1}
                            onChange={(e) => handleChange(key, e.target.checked ? 1 : 0)}
                        />
                    ) : hasSelectOptions ? (
                        <select
                            className="ml-4 w-1/2 rounded-md border border-neutral-300 bg-white px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
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
                    ) : (
                        <input
                            className="ml-4 w-1/2 rounded-md border border-neutral-300 bg-white px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800"
                            value={form[key] ?? ''}
                            onChange={(e) => handleChange(key, e.target.value)}
                        />
                    ))}
            </div>
        );
    }

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div className="border-sidebar-border/70 dark:border-sidebar-border relative w-full max-w-4xl rounded-xl border bg-white p-6 dark:bg-neutral-900">
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
                                {renderField('Processo', 'episodio', item.episodio)}
                                {renderField('Entrada', 'data_entrada', item.data_entrada)}
                                {renderField('Alta', 'data_alta', item.data_alta)}
                                {renderField('Saída', 'data_saida', item.data_saida)}
                                {renderField('Dias Internamento', 'dias_internamento', item.dias_internamento)}
                                {renderField('Observações', 'observacoes', item.observacoes ?? '-')}
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
