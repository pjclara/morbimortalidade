import { useForm } from '@inertiajs/react';

interface DiagnosticoInternamentoForm {
  descricao: string;
  diagnostico_id: number;
  id: string;
  internamento_id: number;
  principal: string;
}

export default function Edit({ item }: { item: DiagnosticoInternamentoForm }) {
  const { data, setData, put } = useForm<DiagnosticoInternamentoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/DiagnosticoInternamento/" + item.id);
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
        type="text"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="number"
        value={data.internamento_id}
        onChange={e => setData('internamento_id', e.target.value)}
      />      <input
        type="text"
        value={data.principal}
        onChange={e => setData('principal', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}