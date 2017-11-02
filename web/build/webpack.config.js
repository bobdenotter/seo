var Encore = require('@symfony/webpack-encore');

Encore
    // .setOutputPath('../')
    .setOutputPath('../../../../../../public/extensions/vendor/bobdenotter/seo')
    .setPublicPath('/extensions/vendor/bobdenotter/seo')
    .addEntry('seo_extension', './seo_extension.js')
    // .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
;

// export the final configuration
module.exports = Encore.getWebpackConfig();
