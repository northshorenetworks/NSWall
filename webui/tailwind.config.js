/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // Cyber color palette
        cyber: {
          black: '#0a0a0f',
          darker: '#0d0d14',
          dark: '#12121a',
          DEFAULT: '#1a1a24',
          light: '#252530',
          lighter: '#32323f',
        },
        neon: {
          cyan: '#00f0ff',
          blue: '#0080ff',
          purple: '#8b5cf6',
          pink: '#ec4899',
          green: '#00ff88',
          yellow: '#fbbf24',
          orange: '#ff6b35',
          red: '#ff3366',
        },
        accent: {
          primary: '#00f0ff',
          secondary: '#8b5cf6',
          success: '#00ff88',
          warning: '#fbbf24',
          danger: '#ff3366',
        },
        glass: {
          light: 'rgba(255, 255, 255, 0.05)',
          DEFAULT: 'rgba(255, 255, 255, 0.08)',
          medium: 'rgba(255, 255, 255, 0.12)',
          heavy: 'rgba(255, 255, 255, 0.18)',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
        display: ['Orbitron', 'sans-serif'],
      },
      boxShadow: {
        'neon-cyan': '0 0 5px #00f0ff, 0 0 20px rgba(0, 240, 255, 0.3)',
        'neon-purple': '0 0 5px #8b5cf6, 0 0 20px rgba(139, 92, 246, 0.3)',
        'neon-green': '0 0 5px #00ff88, 0 0 20px rgba(0, 255, 136, 0.3)',
        'neon-red': '0 0 5px #ff3366, 0 0 20px rgba(255, 51, 102, 0.3)',
        'glow': '0 0 40px rgba(0, 240, 255, 0.15)',
        'inner-glow': 'inset 0 0 20px rgba(0, 240, 255, 0.1)',
        'card': '0 4px 30px rgba(0, 0, 0, 0.4)',
      },
      backgroundImage: {
        'gradient-radial': 'radial-gradient(var(--tw-gradient-stops))',
        'cyber-grid': `
          linear-gradient(rgba(0, 240, 255, 0.03) 1px, transparent 1px),
          linear-gradient(90deg, rgba(0, 240, 255, 0.03) 1px, transparent 1px)
        `,
        'scan-line': 'linear-gradient(transparent 50%, rgba(0, 0, 0, 0.1) 50%)',
        'gradient-border': 'linear-gradient(135deg, #00f0ff, #8b5cf6, #ff3366)',
      },
      backgroundSize: {
        'grid': '50px 50px',
        'scan': '100% 4px',
      },
      animation: {
        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
        'scan': 'scan 8s linear infinite',
        'float': 'float 6s ease-in-out infinite',
        'slide-up': 'slideUp 0.3s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'fade-in': 'fadeIn 0.2s ease-out',
        'border-flow': 'borderFlow 3s linear infinite',
        'shimmer': 'shimmer 2s linear infinite',
        'pulse-ring': 'pulseRing 1.5s ease-out infinite',
        'typing': 'typing 0.8s steps(12) forwards',
      },
      keyframes: {
        glow: {
          '0%': { boxShadow: '0 0 5px rgba(0, 240, 255, 0.5), 0 0 20px rgba(0, 240, 255, 0.2)' },
          '100%': { boxShadow: '0 0 10px rgba(0, 240, 255, 0.8), 0 0 40px rgba(0, 240, 255, 0.4)' },
        },
        scan: {
          '0%': { backgroundPosition: '0 -100vh' },
          '100%': { backgroundPosition: '0 100vh' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        slideUp: {
          '0%': { transform: 'translateY(10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        slideDown: {
          '0%': { transform: 'translateY(-10px)', opacity: '0' },
          '100%': { transform: 'translateY(0)', opacity: '1' },
        },
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        borderFlow: {
          '0%': { backgroundPosition: '0% 50%' },
          '50%': { backgroundPosition: '100% 50%' },
          '100%': { backgroundPosition: '0% 50%' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
        pulseRing: {
          '0%': { transform: 'scale(0.8)', opacity: '1' },
          '100%': { transform: 'scale(2)', opacity: '0' },
        },
        typing: {
          '0%': { width: '0' },
          '100%': { width: '100%' },
        },
      },
      backdropBlur: {
        xs: '2px',
      },
      borderWidth: {
        '0.5': '0.5px',
      },
    },
  },
  plugins: [],
}
