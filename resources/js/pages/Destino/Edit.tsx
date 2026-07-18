import { useForm } from '@inertiajs/react';

interface DestinoForm {
  created_at: string;
  deleted_at: string;
  id: number;
  nome: string;
  updated_at: string;
}

export default function Edit({ item }: { item: DestinoForm }) {
  const { data, setData, put } = useForm<DestinoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Destino/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.deleted_at}
        onChange={e => setData('deleted_at', e.target.value)}
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