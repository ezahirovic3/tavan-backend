import preset from './vendor/filament/filament/tailwind.config.preset';

export default {
  presets: [preset],
  content: [
    './app/Filament/**/*.php',
    './resources/views/filament/**/*.blade.php',
    './vendor/filament/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Archivo Narrow"', '"Archivo"', '"Inter Tight"', 'system-ui', 'sans-serif'],
      },
      colors: {
        tavan: {
          pink:   '#FB5C90',
          ink:    '#C4275C',
          green:  '#1D781C',
          paper:  '#FAFAFA',
          line:   '#E5E5E5',
        },
      },
      borderRadius: {
        sm: '2px',
        DEFAULT: '4px',
      },
    },
  },
};
