import { useForm } from '@inertiajs/react';

interface JobBatchForm {
  cancelled_at: string;
  created_at: string;
  failed_job_ids: string;
  failed_jobs: string;
  finished_at: string;
  id: string;
  name: string;
  options: string;
  pending_jobs: string;
  total_jobs: string;
}

export default function Edit({ item }: { item: JobBatchForm }) {
  const { data, setData, put } = useForm<JobBatchForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/JobBatch/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.cancelled_at}
        onChange={e => setData('cancelled_at', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.failed_job_ids}
        onChange={e => setData('failed_job_ids', e.target.value)}
      />      <input
        type="text"
        value={data.failed_jobs}
        onChange={e => setData('failed_jobs', e.target.value)}
      />      <input
        type="text"
        value={data.finished_at}
        onChange={e => setData('finished_at', e.target.value)}
      />      <input
        type="text"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="text"
        value={data.name}
        onChange={e => setData('name', e.target.value)}
      />      <input
        type="text"
        value={data.options}
        onChange={e => setData('options', e.target.value)}
      />      <input
        type="text"
        value={data.pending_jobs}
        onChange={e => setData('pending_jobs', e.target.value)}
      />      <input
        type="text"
        value={data.total_jobs}
        onChange={e => setData('total_jobs', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}