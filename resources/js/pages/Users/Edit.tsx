import { useForm } from '@inertiajs/react';

interface UserForm {
  ativo: string;
  created_at: string;
  email: string;
  email_verified_at: string;
  id: number;
  name: string;
  password: string;
  remember_token: string;
  updated_at: string;
  username: string;
}

export default function Edit({ item }: { item: UserForm }) {
  const { data, setData, put } = useForm<UserForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/User/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.ativo}
        onChange={e => setData('ativo', e.target.value)}
      />      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.email}
        onChange={e => setData('email', e.target.value)}
      />      <input
        type="text"
        value={data.email_verified_at}
        onChange={e => setData('email_verified_at', e.target.value)}
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
        value={data.password}
        onChange={e => setData('password', e.target.value)}
      />      <input
        type="text"
        value={data.remember_token}
        onChange={e => setData('remember_token', e.target.value)}
      />      <input
        type="text"
        value={data.updated_at}
        onChange={e => setData('updated_at', e.target.value)}
      />      <input
        type="text"
        value={data.username}
        onChange={e => setData('username', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}