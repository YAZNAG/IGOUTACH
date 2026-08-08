/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      colors: {
        // Nom de reference pour le nouveau code.
        brand: {
          DEFAULT: 'var(--brand)',
          strong: 'var(--brand-strong)',
          light: 'var(--brand-light)',
          soft: 'var(--brand-soft)',
        },
        // Alias herites : ces palettes pointent desormais sur la marque.
        navy: { DEFAULT: 'var(--navy)', 2: 'var(--navy-2)', 3: 'var(--navy-3)' },
        sky: { DEFAULT: 'var(--sky)', 2: 'var(--sky-2)', soft: 'var(--sky-soft)' },
        ink: 'var(--ink)',
        muted: 'var(--muted)',
        faint: 'var(--faint)',
        bg: 'var(--bg)',
        card: 'var(--card)',
        line: { DEFAULT: 'var(--line)', 2: 'var(--line-2)' },
        ok: { DEFAULT: 'var(--ok)', bg: 'var(--ok-bg)' },
        warn: { DEFAULT: 'var(--warn)', bg: 'var(--warn-bg)' },
        bad: { DEFAULT: 'var(--bad)', bg: 'var(--bad-bg)' },
      },
      borderRadius: {
        DEFAULT: 'var(--radius)',
        lg: 'var(--radius-lg)',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
  plugins: [],
}
