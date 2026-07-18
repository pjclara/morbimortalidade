import { useForm } from '@inertiajs/react';

interface PatientForm {
  created_at: string;
  data_nascimento: string;
  deleted_at: string;
  localidade_id: string;
  processo: string;
  sexo_id: number;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<PatientForm>({
  created_at: string;
  data_nascimento: string;
  deleted_at: string;
  localidade_id: string;
  processo: string;
  sexo_id: number;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/Patient');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="date"
        value={data.data_nascimento}
        onChange={e => setData('data_nascimento', e.target.value)}
      />      <input
        type="text"
        value={data.deleted_at}
        onChange={e => setData('deleted_at', e.target.value)}
      />      <input
        type="text"
        value={data.localidade_id}
        onChange={e => setData('localidade_id', e.target.value)}
      />      <input
        type="text"
        value={data.processo}
        onChange={e => setData('processo', e.target.value)}
      />      <input
        type="number"
        value={data.sexo_id}
        onChange={e => setData('sexo_id', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}