var Board = require("./board");
var Tray = require("./tray");
var MoveError = require("./moveError");

function Game (map, items, mountainData, mountains) {
  this.board = new Board();

  let i = 0;
  this.trays = [];
  for (const item of (items || [])) {
    //index 0, tray class 1
    this.trays[i] = new Tray(items[i++], `tray${i}`);
  }

  this.score = 0;
  this.running = false;

  this.map = map;
  this.mountains = mountains;
  this.mountainData = mountainData;
}

Game.prototype.isOver = function () {
  const over = this.board.isOver();
  if (over) {
    if (this.clbck) this.clbck();
    delete this.clbck;
    this.running = false;
  }
  return over;
};

Game.prototype.playMove = function () {
  // this.board.placeShape(this.tray.shape);
};

Game.prototype.run = function (gameCompletionCallback) {
  this.running = true;
  this.clbck = gameCompletionCallback;
};


module.exports = Game;
