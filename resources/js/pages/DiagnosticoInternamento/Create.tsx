import { useForm } from '@inertiajs/react';

interface DiagnosticoInternamentoForm {
  descricao: string;
  diagnostico_id: number;
  internamento_id: number;
  principal: string;
}

export default function Create() {
  const { data, setData, post } = useForm<DiagnosticoInternamentoForm>({
  descricao: string;
  diagnostico_id: number;
  internamento_id: number;
  principal: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/DiagnosticoInternamento');
  }

  return (
    <form onSubmit={submit}>
      <textarea
        value={data.descricao}
        onChange={e => setData('descricao', e.target.value)}
      />      <input
        type="number"
        value={data.diagnostico_id}
        onChange={e => setData('diagnostico_id', e.target.value)}
      />      <input
        type="number"
        value={data.internamento_id}
        onChange={e => setData('internamento_id', e.target.value)}
      />      <input
        type="text"
        value={data.principal}
        onChange={e => setData('principal', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}