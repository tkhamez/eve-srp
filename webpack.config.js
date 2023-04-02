const path = require('path');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const CompressionPlugin = require('compression-webpack-plugin');
const LicenseWebpackPlugin = require('license-webpack-plugin').LicenseWebpackPlugin;
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");

module.exports = (env, argv) => {
    const devMode = argv.mode !== 'production';
    const config = {
        entry: './resources',
        output: {
            path: path.resolve(__dirname, './web/dist'),
            filename: devMode ? '[name].js' : '[name].[chunkhash].js'
        },
        module: {
            rules: [{
                test: /\.(css|scss)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader',
                ]
            }]
        },
        plugins: [
            new HtmlWebpackPlugin({
                template: './resources/css.html',
                filename: 'css.html',
                inject: false,
            }),
            new HtmlWebpackPlugin({
                template: './resources/js.html',
                filename: 'js.html',
                inject: false,
            }),
            new MiniCssExtractPlugin({
                filename: devMode ? '[name].css' : '[name].[fullhash].css',
            }),
            new CleanWebpackPlugin(),
        ],
        devtool: devMode ? 'eval-cheap-source-map' : 'source-map',
        optimization: {
            runtimeChunk: 'single',
            minimizer: [
                new CssMinimizerPlugin()
            ]
        },
    };
    if (!devMode) {
        // noinspection JSCheckFunctionSignatures
        config.plugins.push(new CompressionPlugin({
            test: /\.(js|css)$/,
            threshold: 1,
            compressionOptions: { level: 6 },
        }));
        // noinspection JSCheckFunctionSignatures
        config.plugins.push(new LicenseWebpackPlugin({
            perChunkOutput: false,
        }));
    }
    return config;
};
