const path = require('path');
const webpack = require('webpack');

const webpackConfig = {
	devtool: 'source-map',

	entry: {
		'shortcodes/repo-activity': path.resolve(__dirname, 'shortcodes/js/repo-activity.js'),
		'shortcodes/average-label-time': path.resolve(__dirname, 'shortcodes/js/average-label-time.js'),
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
		},
		{
			test: /\.css$/,
			loader: "style-loader!css-loader?importLoaders=1"
		},
		{
			test: /\.(png|woff|woff2|eot|ttf|svg)$/,
      loader: 'url-loader?limit=1024&name=/fonts/[name].[ext]'
    }
	],
	},
};

module.exports = webpackConfig;
