import './bootstrap';

import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

const moveSocialRoiFilters = () => {
    if (! window.location.pathname.endsWith('/admin/roi-social')) {
        return;
    }

    const filters = document.querySelector('.social-roi-filters-form');
    const statsHeader = document.querySelector('.fi-wi-stats-overview .fi-section-header');

    if (! filters || ! statsHeader || statsHeader.contains(filters)) {
        return;
    }

    statsHeader.appendChild(filters);
};

document.addEventListener('DOMContentLoaded', moveSocialRoiFilters);
document.addEventListener('livewire:navigated', moveSocialRoiFilters);
document.addEventListener('livewire:updated', moveSocialRoiFilters);

setTimeout(moveSocialRoiFilters, 250);
