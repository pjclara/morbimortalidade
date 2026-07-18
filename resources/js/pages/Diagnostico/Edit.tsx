import { useForm } from '@inertiajs/react';

interface DiagnosticoForm {
  codigo: string;
  codigo_pai: string;
  created_at: string;
  deleted_at: string;
  descricao: string;
  grupo_diagnostico_id: number;
  id: number;
  nome: string;
  updated_at: string;
}

export default function Edit({ item }: { item: DiagnosticoForm }) {
  const { data, setData, put } = useForm<DiagnosticoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Diagnostico/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.codigo}
        onChange={e => setData('codigo', e.target.value)}
      />      <input
        type="text"
        value={data.codigo_pai}
        onChange={e => setData('codigo_pai', e.target.value)}
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
        value={data.grupo_diagnostico_id}
        onChange={e => setData('grupo_diagnostico_id', e.target.value)}
      />      <input
        type="number"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="text"
        value={data.nome}
        onChange={e => setData('nome', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}