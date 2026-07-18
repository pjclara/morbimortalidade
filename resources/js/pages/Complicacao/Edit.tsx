import { useForm } from '@inertiajs/react';

interface ComplicacaoForm {
  created_at: string;
  deleted_at: string;
  grupo_complicacao_id: number;
  id: number;
  nome: string;
  updated_at: string;
}

export default function Edit({ item }: { item: ComplicacaoForm }) {
  const { data, setData, put } = useForm<ComplicacaoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Complicacao/" + item.id);
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
        value={data.grupo_complicacao_id}
        onChange={e => setData('grupo_complicacao_id', e.target.value)}
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