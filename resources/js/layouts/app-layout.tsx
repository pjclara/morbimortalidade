import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { Toaster } from 'react-hot-toast'


interface AppLayoutProps {
    children: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
        <Toaster
            position="top-right"
            toastOptions={{
                style: {
                    background: '#1f1f1f',
                    color: '#fff',
                    borderRadius: '8px',
                    padding: '12px 16px',
                },
            }}
        />
        {children}
    </AppLayoutTemplate>
);
