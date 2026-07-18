import { useForm } from '@inertiajs/react';

interface SexoForm {
  created_at: string;
  id: number;
  nome: string;
  updated_at: string;
}

export default function Edit({ item }: { item: SexoForm }) {
  const { data, setData, put } = useForm<SexoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Sexo/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="number"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="text"
        value={data.nome}
        onChange={e => setData('nome', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}