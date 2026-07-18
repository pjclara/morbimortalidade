import { useForm } from '@inertiajs/react';

interface PasswordResetTokenForm {
  created_at: string;
  email: string;
  token: string;
}

export default function Edit({ item }: { item: PasswordResetTokenForm }) {
  const { data, setData, put } = useForm<PasswordResetTokenForm>(item);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    put("/PasswordResetToken/" + item.id);
  }

  return (
    <form onSubmit={submit}>
      <input
        type="text"
        value={data.created_at}
        onChange={e => setData('created_at', e.target.value)}
      />      <input
        type="text"
        value={data.email}
        onChange={e => setData('email', e.target.value)}
      />      <input
        type="text"
        value={data.token}
        onChange={e => setData('token', e.target.value)}
      />
      <button type="submit">Atualizar</button>
    </form>
  );
}