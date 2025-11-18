import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import terser from '@rollup/plugin-terser';
import postcss from 'rollup-plugin-postcss';
import cssnano from 'cssnano';

export default {
  input: 'frontend/index.js',
  output: {
    file: 'assets/dist/pp.js',
    format: 'iife',
    name: 'PathPilotBundle',
    sourcemap: false,
  },
  plugins: [
    resolve(),
    commonjs(),
    postcss({
      // Extract CSS next to the JS bundle as assets/dist/pp.css
      extract: true,
      minimize: true,
      plugins: [cssnano()],
    }),
    terser(),
  ],
};


