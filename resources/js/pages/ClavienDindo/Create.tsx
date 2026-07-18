import { useForm } from '@inertiajs/react';

interface ClavienDindoForm {
  created_at: string;
  deleted_at: string;
  descricao: string;
  nome: string;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<ClavienDindoForm>({
  created_at: string;
  deleted_at: string;
  descricao: string;
  nome: string;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/ClavienDindo');
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
        type="text"
        value={data.descricao}
        onChange={e => setData('descricao', e.target.value)}
      />      <input
        type="text"
        value={data.nome}
        onChange={e => setData('nome', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}