//
// Non-standard config for Karmabunny gulp stuff.
//

exports.styles = {
    "web": {
        "src": [
            "src/skin/default/_patterns/base.scss",
            "src/skin/default/_patterns/components.scss"
        ],
        "watch": [
            "src/skin/default/_patterns/**/*.scss",
        ],
        "target": "src/skin/default/css"
    }
}

exports.assets = {};
