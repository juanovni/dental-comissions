import './bootstrap';

import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

let closingSidebarGroups = false;

const collapseSidebarGroup = (group) => {
    if (group.classList.contains('fi-collapsed')) {
        return;
    }

    const collapseButton = group.querySelector('.fi-sidebar-group-collapse-btn');

    if (! collapseButton) {
        return;
    }

    closingSidebarGroups = true;
    collapseButton.click();
    closingSidebarGroups = false;
};

const closeOtherSidebarGroups = (activeGroup) => {
    const sidebar = activeGroup.closest('.fi-main-sidebar.fi-sidebar-open');

    if (! sidebar) {
        return;
    }

    sidebar
        .querySelectorAll('.fi-sidebar-group.fi-collapsible:not(.fi-collapsed)')
        .forEach((group) => {
            if (group === activeGroup) {
                return;
            }

            collapseSidebarGroup(group);
        });
};

const normalizeSidebarGroups = () => {
    const sidebar = document.querySelector('.fi-main-sidebar.fi-sidebar-open');

    if (! sidebar) {
        return;
    }

    const openGroups = [...sidebar.querySelectorAll('.fi-sidebar-group.fi-collapsible:not(.fi-collapsed)')];

    if (openGroups.length <= 1) {
        return;
    }

    const groupToKeepOpen = openGroups.find((group) => group.classList.contains('fi-active')) ?? openGroups.at(-1);

    openGroups.forEach((group) => {
        if (group === groupToKeepOpen) {
            return;
        }

        collapseSidebarGroup(group);
    });
};

const setupExclusiveSidebarGroups = () => {
    document
        .querySelectorAll('.fi-main-sidebar.fi-sidebar-open .fi-sidebar-group.fi-collapsible .fi-sidebar-group-btn')
        .forEach((button) => {
            if (button.dataset.exclusiveSidebarGroup === 'true') {
                return;
            }

            button.dataset.exclusiveSidebarGroup = 'true';
            button.addEventListener('click', () => {
                if (closingSidebarGroups) {
                    return;
                }

                const activeGroup = button.closest('.fi-sidebar-group');

                if (! activeGroup) {
                    return;
                }

                window.setTimeout(() => {
                    if (activeGroup.classList.contains('fi-collapsed')) {
                        return;
                    }

                    closeOtherSidebarGroups(activeGroup);
                }, 50);
            });
        });

    window.setTimeout(normalizeSidebarGroups, 50);
};

document.addEventListener('DOMContentLoaded', setupExclusiveSidebarGroups);
document.addEventListener('livewire:navigated', setupExclusiveSidebarGroups);
document.addEventListener('livewire:updated', setupExclusiveSidebarGroups);

setTimeout(setupExclusiveSidebarGroups, 250);
