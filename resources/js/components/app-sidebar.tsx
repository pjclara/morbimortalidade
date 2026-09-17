import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BarChart3, Folder, LayoutGrid, UserCheck, Users } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [

];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const page = usePage();
    const { menu } = page.props as unknown as {
        menu: Array<{ title: string; href: string; icon: string }>;
    };
    // Converter menu do backend → NavItem[]
    const iconMap: Record<string, any> = {
        LayoutGrid,
        Folder,
        Users,
        BarChart3,
        UserCheck,
    };

    const mainNavItems = menu.map((item) => ({
        title: item.title,
        url: item.href,
        icon: iconMap[item.icon] ?? LayoutGrid,
    }));
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
