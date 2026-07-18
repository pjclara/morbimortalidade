import { useForm } from '@inertiajs/react';

interface ResolucaoForm {
  clavien_dindo_id: number;
  created_at: string;
  descricao: string;
  id: number;
  nome: string;
  updated_at: string;
}

export default function Edit({ item }: { item: ResolucaoForm }) {
  const { data, setData, put } = useForm<ResolucaoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Resolucao/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="number"
        value={data.clavien_dindo_id}
        onChange={e => setData('clavien_dindo_id', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <textarea
        value={data.descricao}
        onChange={e => setData('descricao', e.target.value)}
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