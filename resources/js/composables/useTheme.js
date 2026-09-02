import { ref } from 'vue';

const isDark = ref(false);

if (typeof document !== 'undefined') {
    isDark.value = document.documentElement.classList.contains('dark');
}

function apply(dark) {
    isDark.value = dark;
    document.documentElement.classList.toggle('dark', dark);
    localStorage.setItem('pos-theme', dark ? 'dark' : 'light');
}

export function useTheme() {
    function toggle() {
        apply(! isDark.value);
    }

    return { isDark, toggle };
}
