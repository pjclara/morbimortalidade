import { useForm } from '@inertiajs/react';

interface FailedImportRowForm {
  created_at: string;
  data: string;
  import_id: number;
  updated_at: string;
  validation_error: string;
}

export default function Create() {
  const { data, setData, post } = useForm<FailedImportRowForm>({
  created_at: string;
  data: string;
  import_id: number;
  updated_at: string;
  validation_error: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/FailedImportRow');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.data}
        onChange={e => setData('data', e.target.value)}
      />      <input
        type="number"
        value={data.import_id}
        onChange={e => setData('import_id', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />      <textarea
        value={data.validation_error}
        onChange={e => setData('validation_error', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}