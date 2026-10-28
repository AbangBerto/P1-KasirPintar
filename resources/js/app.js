import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import * as lucide from 'lucide';

window.Alpine = Alpine;
window.Chart = Chart;
window.lucide = lucide;

// Helper to refresh lucide icons after dynamic DOM changes
window.refreshLucideIcons = () => {
    if (window.lucide && typeof window.lucide.createIcons === 'function') {
        window.lucide.createIcons();
    }
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshLucideIcons();
});

Alpine.start();
