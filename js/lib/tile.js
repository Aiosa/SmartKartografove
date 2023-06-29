function Tile (pos, color, image=undefined) {
  this.pos = pos;
  this.color = color;
  this.empty = false;
  this.image = image;
};

Tile.size = 25;

module.exports = Tile;
