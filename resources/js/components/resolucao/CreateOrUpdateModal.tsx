import { useEffect, useState } from "react";
import { router } from "@inertiajs/react";
import toast from "react-hot-toast";

export default function CreateOrUpdateModal({ open, onClose, resolucao, clavienDindos }) {
    const isEditing = !!resolucao;

    const [form, setForm] = useState({
        nome: "",
        descricao: "",
        clavien_dindo_id: "",
    });

    useEffect(() => {
        if (isEditing) {
            setForm({
                nome: resolucao.nome,
                descricao: resolucao.descricao ?? "",
                clavien_dindo_id: resolucao.clavien_dindo_id ?? "",
            });
        } else {
            setForm({ nome: "", descricao: "", clavien_dindo_id: "" });
        }
    }, [resolucao]);

    const submit = () => {
        const payload = {
            ...form,
            clavien_dindo_id: form.clavien_dindo_id || null,
        };

        if (isEditing) {
            router.put(`/admin/resolucoes/${resolucao.id}`, payload, {
                onSuccess: () => {
                    toast.success("Resolução atualizada com sucesso");
                    onClose();
                },
                onError: () => {
                    toast.error("Erro ao atualizar a resolução");
                },
            });
        } else {
            router.post("/admin/resolucoes", payload, {
                onSuccess: () => {
                    toast.success("Resolução criada com sucesso");
                    onClose();
                },
                onError: () => {
                    toast.error("Erro ao criar a resolução");
                },
            });
        }
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white dark:bg-neutral-900 p-6 rounded-lg w-full max-w-md shadow-lg">
                <h2 className="text-lg font-semibold mb-4">
                    {isEditing ? "Editar Resolução" : "Criar Resolução"}
                </h2>

                <div className="space-y-4">
                    <input
                        type="text"
                        placeholder="Nome"
                        value={form.nome}
                        onChange={(e) => setForm({ ...form, nome: e.target.value })}
                        className="w-full border rounded p-2"
                    />

                    <textarea
                        placeholder="Descrição"
                        value={form.descricao}
                        onChange={(e) => setForm({ ...form, descricao: e.target.value })}
                        className="w-full border rounded p-2"
                        rows={3}
                    />

                    <select
                        value={form.clavien_dindo_id}
                        onChange={(e) => setForm({ ...form, clavien_dindo_id: e.target.value })}
                        className="w-full border rounded p-2"
                    >
                        <option value="">Sem grau Clavien-Dindo</option>
                        {clavienDindos.map((cd: any) => (
                            <option key={cd.id} value={cd.id}>
                                {cd.nome}
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
