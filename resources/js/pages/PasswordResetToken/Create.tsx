import { useForm } from '@inertiajs/react';

interface PasswordResetTokenForm {
  created_at: string;
  email: string;
  token: string;
}

export default function Create() {
  const { data, setData, post } = useForm<PasswordResetTokenForm>({
  created_at: string;
  email: string;
  token: string;
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/PasswordResetToken');
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
      <button type="submit">Criar</button>
    </form>
  );
}