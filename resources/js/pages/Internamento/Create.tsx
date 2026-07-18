import { useForm } from '@inertiajs/react';

interface InternamentoForm {
  bloquear: string;
  clavien_dindo_id: number;
  created_at: string;
  data_alta: string;
  data_entrada: string;
  data_saida: string;
  deleted_at: string;
  destino_id: number;
  dias_internamento: string;
  episodio: string;
  equipa_id: number;
  falecido: string;
  mortalidade_esperada: string;
  observacoes: string;
  origem_id: number;
  patient_id: number;
  responsavel_id: string;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<InternamentoForm>({
  bloquear: string;
  clavien_dindo_id: number;
  created_at: string;
  data_alta: string;
  data_entrada: string;
  data_saida: string;
  deleted_at: string;
  destino_id: number;
  dias_internamento: string;
  episodio: string;
  equipa_id: number;
  falecido: string;
  mortalidade_esperada: string;
  observacoes: string;
  origem_id: number;
  patient_id: number;
  responsavel_id: string;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/Internamento');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.bloquear}
        onChange={e => setData('bloquear', e.target.value)}
      />      <input
        type="number"
        value={data.clavien_dindo_id}
        onChange={e => setData('clavien_dindo_id', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="date"
        value={data.data_alta}
        onChange={e => setData('data_alta', e.target.value)}
      />      <input
        type="date"
        value={data.data_entrada}
        onChange={e => setData('data_entrada', e.target.value)}
      />      <input
        type="date"
        value={data.data_saida}
        onChange={e => setData('data_saida', e.target.value)}
      />      <input
        type="text"
        value={data.deleted_at}
        onChange={e => setData('deleted_at', e.target.value)}
      />      <input
        type="number"
        value={data.destino_id}
        onChange={e => setData('destino_id', e.target.value)}
      />      <input
        type="text"
        value={data.dias_internamento}
        onChange={e => setData('dias_internamento', e.target.value)}
      />      <input
        type="text"
        value={data.episodio}
        onChange={e => setData('episodio', e.target.value)}
      />      <input
        type="number"
        value={data.equipa_id}
        onChange={e => setData('equipa_id', e.target.value)}
      />      <input
        type="text"
        value={data.falecido}
        onChange={e => setData('falecido', e.target.value)}
      />      <input
        type="text"
        value={data.mortalidade_esperada}
        onChange={e => setData('mortalidade_esperada', e.target.value)}
      />      <textarea
        value={data.observacoes}
        onChange={e => setData('observacoes', e.target.value)}
      />      <input
        type="number"
        value={data.origem_id}
        onChange={e => setData('origem_id', e.target.value)}
      />      <input
        type="number"
        value={data.patient_id}
        onChange={e => setData('patient_id', e.target.value)}
      />      <input
        type="text"
        value={data.responsavel_id}
        onChange={e => setData('responsavel_id', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}