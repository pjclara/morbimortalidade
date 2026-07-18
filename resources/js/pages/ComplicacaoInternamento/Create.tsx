import { useForm } from '@inertiajs/react';

interface ComplicacaoInternamentoForm {
  complicacao_id: number;
  internamento_id: number;
  resolucaos: string;
}

export default function Create() {
  const { data, setData, post } = useForm<ComplicacaoInternamentoForm>({
  complicacao_id: number;
  internamento_id: number;
  resolucaos: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/ComplicacaoInternamento');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="number"
        value={data.complicacao_id}
        onChange={e => setData('complicacao_id', e.target.value)}
      />      <input
        type="number"
        value={data.internamento_id}
        onChange={e => setData('internamento_id', e.target.value)}
      />      <input
        type="text"
        value={data.resolucaos}
        onChange={e => setData('resolucaos', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}