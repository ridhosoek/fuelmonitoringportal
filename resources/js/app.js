import 'bootstrap';
import 'admin-lte';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

window.formatLiter = function formatLiter(value) {
    return Number(value || 0).toLocaleString('id-ID', {
        minimumFractionDigits: 3,
        maximumFractionDigits: 3,
    });
};
