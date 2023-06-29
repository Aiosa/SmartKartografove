var Shape = require('./shape');
var Tile = require('./tile');
var NullTile = require('./nullTile');

var count = 0;
var COLORS = ["red", "blue", "yellow", "green", "purple"];

function Tray (shapeData, cls) {
  this.size = Tray.size;
  this.class= cls;

  //override color to distinguish pieces
  shapeData.color = COLORS[count++ % COLORS.length];

  this.grid = Tray.makeGrid(this.size);
  this.shape = new Shape(shapeData);
  this.shape.tray = this;
  this.placeShape(this.shape);
}

Tray.size = 4;

Tray.makeGrid = function (size) {
  var grid = [];
  for (var i = 0; i < size; i++) {
    grid.push([]);
    for (var j = 0; j < size; j++) {
      grid[i].push(new NullTile([i,j], null));
    }
  }
  return grid;
};

Tray.prototype.placeShape = function (shape) {
  this.shape = shape;
  shape.coords.forEach(function (pos) {
    this.grid[pos[0]][pos[1]] = new Tile(pos, shape.color, shape.image);
  }.bind(this));

  shape.scoords?.forEach(function (pos) {
    this.grid[pos[0]][pos[1]].scord = true;
  }.bind(this));
};

Tray.prototype.rotateRight = function() {
  this.shape.rotateRight();
  this.grid = Tray.makeGrid(this.size);
  this.placeShape(this.shape);
};

Tray.prototype.flip = function() {
  this.shape.flip();
  this.grid = Tray.makeGrid(this.size);
  this.placeShape(this.shape);
};

module.exports = Tray;
