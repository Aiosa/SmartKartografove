var View = require('./lib/view');
var Game = require('./lib/game');

window.kartografove = function(rootEl, map, items, interaction, mountainData={color:"brown", image:"mountain.png"}, mountains=[[1,9],[3,2],[5,8],[7,4],[9,1]]) {
  //map: 2D array of nodes: {image, color }
  //items: 1D array of active pieces: {image, color, pos:[[point]], corner:[point], placed:bool}
  //can optionally receive mountain spec and mountain array of positions
  //colors: they need to be a) valid value for nonempty tile (nonempty string), b) do not have to be consistent, colors are used for borders only 
  var game = new Game(map, items, mountainData, mountains);
  window.view = new View(game, rootEl, interaction, "img/");
  game.run();
};


