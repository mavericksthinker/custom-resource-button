let mix = require('laravel-mix');

mix
  .setPublicPath('dist')
  .js('resources/js/card.js', 'js')
  .sass('resources/sass/card.scss', 'css')
    .webpackConfig({
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js/'),
            },
        },
    });
