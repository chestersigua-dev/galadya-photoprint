import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

// Make Alpine accessible globally
window.Alpine = Alpine;

// Initialize Lucide icons helper
window.lucideIcons = icons;
window.initLucideIcons = () => {
    createIcons({ icons });
};

// Start Alpine
Alpine.start();

// Initial Lucide icons mount
document.addEventListener('DOMContentLoaded', () => {
    window.initLucideIcons();
});

// Re-init icons when Alpine updates DOM
document.addEventListener('alpine:initialized', () => {
    window.initLucideIcons();
});
