import { useForm } from '@inertiajs/react';

interface UserForm {
  name: string;
  username: string;
  email: string;
  password: string;
  ativo: boolean;
}

export default function Create() {
  const { data, setData, post, errors } = useForm<UserForm>({
    name: '',
    username: '',
    email: '',
    password: '',
    ativo: true,
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post(route('users.store'));
  }

  return (
    <form onSubmit={submit} className="space-y-4 p-6 max-w-lg">

      <div>
        <label>Nome</label>
        <input
          type="text"
          value={data.name}
          onChange={e => setData('name', e.target.value)}
          className="input"
        />
        <div className="text-red-600">{errors.name}</div>
      </div>

      <div>
        <label>Username</label>
        <input
          type="text"
          value={data.username}
          onChange={e => setData('username', e.target.value)}
          className="input"
        />
        <div className="text-red-600">{errors.username}</div>
      </div>

      <div>
        <label>Email</label>
        <input
          type="email"
          value={data.email}
          onChange={e => setData('email', e.target.value)}
          className="input"
        />
        <div className="text-red-600">{errors.email}</div>
      </div>

      <div>
        <label>Password</label>
        <input
          type="password"
          value={data.password}
          onChange={e => setData('password', e.target.value)}
          className="input"
        />
        <div className="text-red-600">{errors.password}</div>
      </div>

      <div className="flex items-center gap-2">
        <input
          type="checkbox"
          checked={data.ativo}
          onChange={e => setData('ativo', e.target.checked)}
        />
        <label>Ativo</label>
      </div>

      <button type="submit" className="btn-primary">
        Criar Utilizador
      </button>
    </form>
  );
}
