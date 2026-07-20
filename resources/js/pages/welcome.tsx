import { Head, Link, usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Morbimortalidade Cirúrgica">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC] lg:justify-center lg:p-8">

                {/* NAV */}
                <header className="mb-6 w-full max-w-4xl text-sm">
                    <nav className="flex items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={route('login')}
                                    className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm hover:border-[#1915014a] dark:border-[#3E3E3A]"
                                >
                                    Entrar
                                </Link>
                               
                            </>
                        )}
                    </nav>
                </header>

                {/* MAIN */}
                <main className="flex w-full max-w-4xl flex-col lg:flex-row rounded-lg overflow-hidden shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]">

                    {/* LEFT PANEL */}
                    <div className="flex-1 bg-white dark:bg-[#161615] p-8 lg:p-16 text-[14px] leading-[22px]">
                        <h1 className="mb-2 text-xl font-semibold">Sistema de Morbimortalidade Cirúrgica</h1>

                        <p className="mb-4 text-[#706f6c] dark:text-[#A1A09A]">
                            Plataforma integrada para registo, análise e monitorização de internamentos,
                            complicações cirúrgicas, mortalidade e indicadores de qualidade assistencial.
                        </p>

                        <ul className="space-y-3 mb-6">
                            <li className="flex items-center gap-3">
                                <span className="h-3 w-3 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                <span>Acompanhe indicadores de morbimortalidade em tempo real</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <span className="h-3 w-3 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                <span>Registe internamentos, complicações e episódios cirúrgicos</span>
                            </li>
                            <li className="flex items-center gap-3">
                                <span className="h-3 w-3 rounded-full bg-[#dbdbd7] dark:bg-[#3E3E3A]" />
                                <span>Gere relatórios estatísticos e dashboards clínicos</span>
                            </li>
                        </ul>

                        {auth.user ? (
                            <Link
                                href={route('dashboard')}
                                className="inline-block rounded-sm bg-[#1b1b18] text-white px-6 py-2 text-sm hover:bg-black dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:bg-white"
                            >
                                Aceder ao Dashboard
                            </Link>
                        ) : (
                            <span></span>
                        )}
                    </div>

                    {/* RIGHT PANEL — imagem temática */}
                    <div className="relative w-full lg:w-[420px] bg-[#E5FFCC] dark:bg-[#1D0002] flex items-center justify-center p-6">
                        <div className="text-center">
                            <h2 className="text-lg font-medium mb-2">Serviço Cirúrgico</h2>
                            <p className="text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                Qualidade assistencial, segurança do doente e análise contínua de resultados.
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
