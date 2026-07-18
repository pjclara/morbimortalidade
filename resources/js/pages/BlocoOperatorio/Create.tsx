import { useForm } from '@inertiajs/react';

interface BlocoOperatorioForm {
  ambulatorio: string;
  bloco_num: number;
  created_at: string;
  data_intervencao: string;
  deleted_at: string;
  internamento_id: number;
  tipo_de_cirurgia_id: number;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<BlocoOperatorioForm>({
  ambulatorio: string;
  bloco_num: number;
  created_at: string;
  data_intervencao: string;
  deleted_at: string;
  internamento_id: number;
  tipo_de_cirurgia_id: number;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/BlocoOperatorio');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.ambulatorio}
        onChange={e => setData('ambulatorio', e.target.value)}
      />      <input
        type="number"
        value={data.bloco_num}
        onChange={e => setData('bloco_num', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="date"
        value={data.data_intervencao}
        onChange={e => setData('data_intervencao', e.target.value)}
      />      <input
        type="text"
        value={data.deleted_at}
        onChange={e => setData('deleted_at', e.target.value)}
      />      <input
        type="number"
        value={data.internamento_id}
        onChange={e => setData('internamento_id', e.target.value)}
      />      <input
        type="number"
        value={data.tipo_de_cirurgia_id}
        onChange={e => setData('tipo_de_cirurgia_id', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}