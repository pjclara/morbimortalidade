import { useForm } from '@inertiajs/react';

interface RoleForm {
  created_at: string;
  guard_name: string;
  name: string;
  updated_at: string;
}

export default function Create() {
  const { data, setData, post } = useForm<RoleForm>({
  created_at: string;
  guard_name: string;
  name: string;
  updated_at: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/Role');
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.guard_name}
        onChange={e => setData('guard_name', e.target.value)}
      />      <input
        type="text"
        value={data.name}
        onChange={e => setData('name', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Criar</button>
    </form>
  );
}