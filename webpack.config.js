module.exports = [

    {
        entry: {
			"settings": "./app/views/settings.js",
        },
        output: {
            filename: "./app/bundle/[name].js"
        },
		externals: {
			"jquery": "jQuery",
            'uikit': 'UIkit',
            'vue': 'Vue'
        },     
        module: {
            loaders: [
				{ test: /\.html$/, loader: "vue-html" },
                { test: /\.vue$/, loader: "vue" }
            ]
        }
    }

];
