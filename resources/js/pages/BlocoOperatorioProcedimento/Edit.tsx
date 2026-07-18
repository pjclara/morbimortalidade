import { useForm } from '@inertiajs/react';

interface BlocoOperatorioProcedimentoForm {
  bloco_operatorio_id: number;
  descricao: string;
  id: string;
  procedimento_id: number;
}

export default function Edit({ item }: { item: BlocoOperatorioProcedimentoForm }) {
  const { data, setData, put } = useForm<BlocoOperatorioProcedimentoForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/BlocoOperatorioProcedimento/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="number"
        value={data.bloco_operatorio_id}
        onChange={e => setData('bloco_operatorio_id', e.target.value)}
      />      <textarea
        value={data.descricao}
        onChange={e => setData('descricao', e.target.value)}
      />      <input
        type="text"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="number"
        value={data.procedimento_id}
        onChange={e => setData('procedimento_id', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}