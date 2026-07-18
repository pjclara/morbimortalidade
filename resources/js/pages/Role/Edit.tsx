import { useForm } from '@inertiajs/react';

interface RoleForm {
  created_at: string;
  guard_name: string;
  id: number;
  name: string;
  updated_at: string;
}

export default function Edit({ item }: { item: RoleForm }) {
  const { data, setData, put } = useForm<RoleForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/Role/" + item.id);
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
        type="number"
        value={data.id}
        onChange={e => setData('id', e.target.value)}
      />      <input
        type="text"
        value={data.name}
        onChange={e => setData('name', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}