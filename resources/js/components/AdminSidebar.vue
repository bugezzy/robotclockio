<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    Clock,
    CreditCard,
    LayoutGrid,
    User,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import admin from '@/routes/admin';
import { home } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage<{ auth: Auth }>();
// Owner needs this to reach their own organization's settings; admin
// (team-scoped) has no organization-level page to see.
const canSeeOrganizations = computed(() =>
    ['owner', 'system'].includes(page.props.auth.user.role),
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: admin.dashboard(),
        icon: LayoutGrid,
    },
    ...(canSeeOrganizations.value
        ? [
              {
                  title: 'Organizations',
                  href: admin.organizations.index(),
                  icon: Building2,
              },
          ]
        : []),
    {
        title: 'Teams',
        href: admin.teams.index(),
        icon: UsersRound,
    },
    {
        title: 'Users',
        href: admin.users.index(),
        icon: User,
    },
    {
        title: 'Cards',
        href: admin.cards.index(),
        icon: CreditCard,
    },
    {
        title: 'Punches',
        href: admin.punches.index(),
        icon: Clock,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'View site',
        href: home(),
        icon: LayoutGrid,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="admin.dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
