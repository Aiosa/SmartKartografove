var MoveError = require("./moveError");
var Tile = require('./tile');
var NullTile = require('./nullTile');
var gridSize = 11;

function Board () {
  this.grid = Board.makeGrid();
  this.size = gridSize;
}

Board.makeGrid = function () {
  var grid = [];
  for (var i = 0; i < gridSize; i++) {
    grid.push([]);
    for (var j = 0; j < gridSize; j++) {
      grid[i].push(new NullTile([i,j], null));
    }
  }
  return grid;
};

Board.validPos = function (pos) {
  return (
    (0 <= pos[0]) && (pos[0] < gridSize) && (0 <= pos[1]) && (pos[1] < gridSize)
  );
};

Board.prototype.placeShape = function (shape) {
  var coords = shape.coords;
  if (this.emptyCoords(coords)) {
    coords.forEach(function (row, rowIdx) {
      row.forEach(function (tile, tileIdx) {
        tile = new Tile([rowIdx, tileIdx], shape.color);
      });
    });
  }
};

Board.prototype.emptyCoords = function (coords) {
  var self = this;
  coords.forEach(function (row, rowIdx) {
    row.forEach(function (tile, tileIdx) {
      if (Board.validPos([rowIdx, tileIdx])) {
        var tile = self.grid[rowIdx][tileIdx];
        if (tile.empty) {
          return false;
        }
      }
    });
  });
  return true;
};

Board.prototype.isOver = function () {
  for (var rowIdx = 0; rowIdx < 3; rowIdx++) {
    for (var colIdx = 0; colIdx < 3; colIdx++) {
      if (this.isEmptyPos([rowIdx, colIdx])) {
        return false;
      }
    }
  }
  return true;
};

module.exports = Board;
