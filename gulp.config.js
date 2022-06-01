//
// Non-standard config for Karmabunny gulp stuff.
//

exports.styles = {
    "web": {
        "src": [
            "fractal/_patterns/base.scss",
            "fractal/_patterns/components.scss",
            "fractal/_patterns/03-modules/posts/posts.scss",
            "fractal/_patterns/03-modules/magnific-popup/magnific-popup.scss"
        ],
        "watch": [
            "fractal/_patterns/**/*.scss",
        ],
        "target": "web/css"
    },
    "redactor": {
        "src": [
            "fractal/_patterns/03-modules/redactor/karmabunny.scss",
        ],
        "watch": [
            "fractal/_patterns/**/*.scss",
        ],
        "target": "config/redactor/plugins/karmabunny"
    }
}

exports.assets = {};
