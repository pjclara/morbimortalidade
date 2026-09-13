// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout title="Verificar email" description="Verifique o seu endereço de email clicando no link que acabámos de lhe enviar.">
            <Head title="Verificação de email" />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    Foi enviado um novo link de verificação para o endereço de email fornecido durante o registo.
                </div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                <Button disabled={processing} variant="secondary">
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Reenviar email de verificação
                </Button>

                <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                    Terminar sessão
                </TextLink>
            </form>
        </AuthLayout>
    );
}
