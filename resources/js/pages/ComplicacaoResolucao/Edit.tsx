import { useForm } from '@inertiajs/react';

interface ComplicacaoResolucaoForm {
  complicacao_internamento_id: string;
  created_at: string;
  id: number;
  resolucao_id: number;
  updated_at: string;
}

export default function Edit({ item }: { item: ComplicacaoResolucaoForm }) {
  const { data, setData, put } = useForm<ComplicacaoResolucaoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/ComplicacaoResolucao/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.complicacao_internamento_id}
        onChange={e => setData('complicacao_internamento_id', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="number"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="number"
        value={data.resolucao_id}
        onChange={e => setData('resolucao_id', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}