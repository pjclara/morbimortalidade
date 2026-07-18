import { useForm } from '@inertiajs/react';

interface BlocoOperatorioProcedimentoForm {
  bloco_operatorio_id: number;
  descricao: string;
  procedimento_id: number;
}

export default function Create() {
  const { data, setData, post } = useForm<BlocoOperatorioProcedimentoForm>({
  bloco_operatorio_id: number;
  descricao: string;
  procedimento_id: number;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/BlocoOperatorioProcedimento');
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
        type="number"
        value={data.procedimento_id}
        onChange={e => setData('procedimento_id', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}