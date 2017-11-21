const path = require('path');
const webpack = require('webpack');

const webpackConfig = {
	devtool: 'source-map',

	entry: {
		'js/admin': path.resolve(__dirname, 'app/admin.js'),
		'js/shortcode': path.resolve(__dirname, 'app/shortcode.js'),
		'js/widget': path.resolve(__dirname, 'app/widget.js'),
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
