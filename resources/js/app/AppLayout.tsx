import { ActionIcon, AppShell, Burger, Group, NavLink, Stack, Tooltip } from '@mantine/core';
import { useDisclosure, useLocalStorage, useMediaQuery } from '@mantine/hooks';
import { IconLayoutSidebarLeftCollapse, IconLayoutSidebarLeftExpand, IconUsers, type Icon } from '@tabler/icons-react';
import { useEffect } from 'react';
import { Link, Outlet, useLocation } from 'react-router';
import { ColorSchemeToggle } from './ColorSchemeToggle';
import { Logo } from './Logo';

interface NavItem {
    label: string;
    to: string;
    icon: Icon;
    isActive: (pathname: string) => boolean;
}

const NAV_ITEMS: NavItem[] = [
    {
        label: 'Contacts',
        to: '/contacts',
        icon: IconUsers,
        isActive: (pathname) => pathname.startsWith('/contacts'),
    },
];

/** Collapsed navbar: just wide enough for a 36px icon item plus the navbar padding. */
const COLLAPSED_NAV_WIDTH = 56;
const EXPANDED_NAV_WIDTH = 240;

export function AppLayout() {
    const [mobileOpened, { toggle: toggleMobile, close: closeMobile }] = useDisclosure(false);
    const [collapsed, setCollapsed] = useLocalStorage({ key: 'rex-crm-nav-collapsed', defaultValue: false, getInitialValueInEffect: false });
    const isMobile = useMediaQuery('(max-width: 48em)') ?? false;
    const { pathname } = useLocation();

    // The mobile drawer always shows labels; the collapsed state applies from the sm breakpoint up.
    const iconOnly = collapsed && !isMobile;

    useEffect(() => {
        closeMobile();
    }, [pathname, closeMobile]);

    return (
        <AppShell
            header={{ height: 60 }}
            navbar={{
                width: iconOnly ? COLLAPSED_NAV_WIDTH : EXPANDED_NAV_WIDTH,
                breakpoint: 'sm',
                collapsed: { mobile: !mobileOpened },
            }}
            padding="md"
        >
            <AppShell.Header>
                <Group h="100%" px="md" justify="space-between" wrap="nowrap">
                    <Group gap="sm" wrap="nowrap">
                        <Burger
                            opened={mobileOpened}
                            onClick={toggleMobile}
                            hiddenFrom="sm"
                            size="sm"
                            aria-label={mobileOpened ? 'Close navigation' : 'Open navigation'}
                            aria-expanded={mobileOpened}
                            aria-controls="app-navigation"
                        />
                        <Logo />
                    </Group>
                    <ColorSchemeToggle />
                </Group>
            </AppShell.Header>

            <AppShell.Navbar id="app-navigation" p="xs" aria-label="Main navigation">
                <Stack gap={4} style={{ flex: 1 }}>
                    {NAV_ITEMS.map((item) => {
                        const active = item.isActive(pathname);
                        const ItemIcon = item.icon;

                        return (
                            <Tooltip
                                key={item.to}
                                label={item.label}
                                position="right"
                                disabled={!iconOnly}
                                withArrow
                                events={{ hover: true, focus: true, touch: false }}
                            >
                                <NavLink
                                    component={Link}
                                    to={item.to}
                                    label={iconOnly ? undefined : item.label}
                                    aria-label={item.label}
                                    aria-current={active ? 'page' : undefined}
                                    active={active}
                                    leftSection={<ItemIcon size={20} stroke={1.6} aria-hidden />}
                                    styles={iconOnly ? { root: { justifyContent: 'center' }, section: { marginInlineEnd: 0 } } : undefined}
                                />
                            </Tooltip>
                        );
                    })}
                </Stack>

                <Group justify={iconOnly ? 'center' : 'flex-end'} visibleFrom="sm">
                    <Tooltip label={iconOnly ? 'Expand navigation' : 'Collapse navigation'} position="right">
                        <ActionIcon
                            variant="subtle"
                            size="lg"
                            onClick={() => setCollapsed(!collapsed)}
                            aria-label={iconOnly ? 'Expand navigation' : 'Collapse navigation'}
                            aria-expanded={!iconOnly}
                            aria-controls="app-navigation"
                        >
                            {iconOnly ? <IconLayoutSidebarLeftExpand size={20} aria-hidden /> : <IconLayoutSidebarLeftCollapse size={20} aria-hidden />}
                        </ActionIcon>
                    </Tooltip>
                </Group>
            </AppShell.Navbar>

            <AppShell.Main>
                <Outlet />
            </AppShell.Main>
        </AppShell>
    );
}
