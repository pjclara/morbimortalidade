import { useEffect, useState } from "react";
import { router } from "@inertiajs/react";
import toast from "react-hot-toast";

export default function CreateOrUpdateModal({ open, onClose, user }) {
    const isEditing = !!user;

    const [form, setForm] = useState({
        name: "",
        email: "",
        password: "",
    });

    useEffect(() => {
        if (isEditing) {
            setForm({
                name: user.name,
                email: user.email,
                password: "",
            });
        } else {
            setForm({ name: "", email: "", password: "" });
        }
    }, [user]);

    const submit = () => {
        const action = isEditing
            ? router.put(`/admin/users/${user.id}`, form, {
                  onSuccess: () => {
                      toast.success("Utilizador atualizado com sucesso");
                      onClose();
                  },
                  onError: () => {
                      toast.error("Erro ao atualizar o utilizador");
                  },
              })
            : router.post("/admin/users", form, {
                  onSuccess: () => {
                      toast.success("Utilizador criado com sucesso");
                      onClose();
                  },
                  onError: () => {
                      toast.error("Erro ao criar o utilizador");
                  },
              });
    };

    if (!open) return null;

    return (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div className="bg-white dark:bg-neutral-900 p-6 rounded-lg w-full max-w-md shadow-lg">
                <h2 className="text-lg font-semibold mb-4">
                    {isEditing ? "Editar Utilizador" : "Criar Utilizador"}
                </h2>

                <div className="space-y-4">
                    <input
                        type="text"
                        placeholder="Nome"
                        value={form.name}
                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                        className="w-full border rounded p-2"
                    />

                    <input
                        type="email"
                        placeholder="Email"
                        value={form.email}
                        onChange={(e) => setForm({ ...form, email: e.target.value })}
                        className="w-full border rounded p-2"
                    />

                    <input
                        type="password"
                        placeholder={isEditing ? "Nova Password (opcional)" : "Password"}
                        value={form.password}
                        onChange={(e) => setForm({ ...form, password: e.target.value })}
                        className="w-full border rounded p-2"
                    />
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
