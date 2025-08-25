// webpack/react.config.js

const path = require('path');
const { merge } = require('webpack-merge');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const customConfig = {
	entry: {
		hosting: path.resolve(process.cwd(), 'src/hosting.js'), // Main React entry point
	},
	output: {
		path: path.resolve(process.cwd(), 'build/hosting'),
		filename: 'hosting.min.js', // Output JS
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: 'hosting.min.css', // Output CSS
		}),
	],

};

module.exports = merge(defaultConfig, customConfig);
