import { useEffect, useState } from "react";
import { router } from "@inertiajs/react";
import toast from "react-hot-toast";

export default function CreateOrUpdateModal({ open, onClose, complicacao, grupos }) {
    const isEditing = !!complicacao;

    const [form, setForm] = useState({
        nome: "",
        grupo_complicacao_id: "",
    });

    useEffect(() => {
        if (isEditing) {
            setForm({
                nome: complicacao.nome,
                grupo_complicacao_id: complicacao.grupo_complicacao_id ?? "",
            });
        } else {
            setForm({ nome: "", grupo_complicacao_id: "" });
        }
    }, [complicacao]);

    const submit = () => {
        const payload = {
            ...form,
            grupo_complicacao_id: form.grupo_complicacao_id || null,
        };

        if (isEditing) {
            router.put(`/admin/complicacoes/${complicacao.id}`, payload, {
                onSuccess: () => {
                    toast.success("Complicação atualizada com sucesso");
                    onClose();
                },
                onError: () => {
                    toast.error("Erro ao atualizar a complicação");
                },
            });
        } else {
            router.post("/admin/complicacoes", payload, {
                onSuccess: () => {
                    toast.success("Complicação criada com sucesso");
                    onClose();
                },
                onError: () => {
                    toast.error("Erro ao criar a complicação");
                },
            });
        }
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white dark:bg-neutral-900 p-6 rounded-lg w-full max-w-md shadow-lg">
                <h2 className="text-lg font-semibold mb-4">
                    {isEditing ? "Editar Complicação" : "Criar Complicação"}
                </h2>

                <div className="space-y-4">
                    <input
                        type="text"
                        placeholder="Nome"
                        value={form.nome}
                        onChange={(e) => setForm({ ...form, nome: e.target.value })}
                        className="w-full border rounded p-2"
                    />

                    <select
                        value={form.grupo_complicacao_id}
                        onChange={(e) => setForm({ ...form, grupo_complicacao_id: e.target.value })}
                        className="w-full border rounded p-2"
                    >
                        <option value="">Sem grupo</option>
                        {grupos.map((grupo: any) => (
                            <option key={grupo.id} value={grupo.id}>
                                {grupo.nome}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="flex justify-end gap-2 mt-6">
                    <button
                        onClick={onClose}
                        className="px-4 py-2 rounded border border-neutral-400"
                    >
                        Cancelar
                    </button>

                    <button
                        onClick={submit}
                        className="px-4 py-2 rounded bg-blue-600 text-white"
                    >
                        {isEditing ? "Guardar" : "Criar"}
                    </button>
                </div>
            </div>
        </div>
    );
}
