const path = require('path');
const webpack = require('webpack');

const webpackConfig = {
	devtool: 'source-map',

	entry: {
		'shortcodes/repo-activity': path.resolve(__dirname, 'shortcodes/js/repo-activity.js'),
		// 'shortcodes/main-report': path.resolve(__dirname, 'shortcodes/js/main-report.js'),
		// 'shortcodes/team-activity': path.resolve(__dirname, 'shortcodes/js/team-activity.js'),
	},

	output: {
		filename: '[name].js',
		path: path.resolve(__dirname, '_build'),
	},

	resolve: {
		extensions: [".js", ".jsx", ".json"],
	},

	module: {
		rules: [{
			test: /\.jsx?$/,
			exclude: /node_modules/,
			loader: 'babel-loader',
		}, ],
	},
};

if (process.env.NODE_ENV === 'production') {
	// When running in production, we want to use the minified script so that the file is smaller
	webpackConfig.plugins.push(new webpack.optimize.UglifyJsPlugin({
		compress: {
			warnings: false
		}
	}));

}

module.exports = webpackConfig;
