const path = require('path');

module.exports = {
  entry: './ui/js/pods-tooltip/pods-tooltip.js',
  output: {
    filename: './pods-tooltip.min.js',
    path: path.resolve(__dirname),
  }
};