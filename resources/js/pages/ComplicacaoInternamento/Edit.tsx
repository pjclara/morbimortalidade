import { useForm } from '@inertiajs/react';

interface ComplicacaoInternamentoForm {
  complicacao_id: number;
  id: string;
  internamento_id: number;
  resolucaos: string;
}

export default function Edit({ item }: { item: ComplicacaoInternamentoForm }) {
  const { data, setData, put } = useForm<ComplicacaoInternamentoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/ComplicacaoInternamento/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="number"
        value={data.complicacao_id}
        onChange={e => setData('complicacao_id', e.target.value)}
      />      <input
        type="text"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="number"
        value={data.internamento_id}
        onChange={e => setData('internamento_id', e.target.value)}
      />      <input
        type="text"
        value={data.resolucaos}
        onChange={e => setData('resolucaos', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}