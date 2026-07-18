import { useForm } from '@inertiajs/react';

interface ProcedimentoForm {
  codigo: string;
  created_at: string;
  deleted_at: string;
  descricao: string;
  grupo_procedimento_id: number;
  nome: string;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<ProcedimentoForm>({
  codigo: string;
  created_at: string;
  deleted_at: string;
  descricao: string;
  grupo_procedimento_id: number;
  nome: string;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/Procedimento');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.codigo}
        onChange={e => setData('codigo', e.target.value)}
      />      <input
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
        type="number"
        value={data.grupo_procedimento_id}
        onChange={e => setData('grupo_procedimento_id', e.target.value)}
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