const Tile = require("./tile");
const Tray = require("./tray");
const Shape = require("./shape");

//interaction: 'all', 'monster', 'none'
var View = function (game, $el, interaction, imgRootPath="") {
  this.game = game;
  this.board = game.board;
  this.$el = $el;
  this.imgRootPath = imgRootPath;
  this.interaction = interaction;

  //one tray above
  var $container = $("<div>");
  $container.addClass('trays d-flex flex-row flex-wrap');
  this.$el.append($container);
  this.$trayEl = $container;

  this.activeShape = undefined;
  this.grid = [];
  this.setupBoard();

   //one tray below
  $container = $("<div>");
  $container.addClass('trays d-flex flex-row flex-wrap');
  this.$el.append($container);
  this.$trayEl2 = $container;

  this.setupTrays();
};

View.prototype.setupTrays = function (pieces) {
  function placedShape(tray, self) {
    if (!tray || !tray.shape.placed|| !tray.shape.corner) return;
    const $tray = $("." + tray.class);
    const shape = tray.shape;

    self.activeShape = shape;
    self.activeTray = tray;
    if (self.placeShapeIfPossible(shape.corner)) {
      $tray[0].style.visibility = 'hidden';
    } 
  }

  for (let i = 0; i < this.game.trays.length; i++) {
    const tray = this.game.trays[i];
    tray._order = i;

    this.setupPieceTray(tray);
    placedShape(tray, this);
  }
}

View.prototype.setupBoard = function () {
  var self = this;
  var $ul = $("<ul>").addClass("group position-relative");

  function isActiveTrayForCoords(tray, i, j) {
    if (!tray || self.interaction == 'none') return false;

    if ((self.interaction == 'monster' && !tray.shape.monster) 
    || (self.interaction != 'monster' && tray.shape.monster)) {
      return false;
    }

    const coords = tray.shape.transformCoords;
    return coords && coords.some(c => [i, j].equals(c));
  }

  function createCounterCell(letter) {
    var $li = $("<li>");  
    $li.css({width: Tile.size, height: Tile.size, background: 'transparent'})
    $li.html(letter);
    //need to have due to testing, but far away to say 'not able to place'
    $li.data("pos", [-99999,-99999]);
    $li.addClass('border-counting');
    $ul.append($li);
  }

  //empty corner:
  createCounterCell();

  this.board.grid.forEach(function (row, rowIdx) {
    createCounterCell(String.fromCharCode(rowIdx+65));
  });

  this.board.grid.forEach(function (row, rowIdx) {
    row.forEach(function (tile, tileIdx) {
      
      if (tileIdx == 0) {
        createCounterCell(rowIdx+1);
      }

      var $li = $("<li>");
      const pos = [rowIdx, tileIdx];

      $li.css({width: Tile.size, height: Tile.size})
      $li.data("pos", pos);

      const mapPiece = self.game.map[rowIdx][tileIdx] || {};

      if (mapPiece.scord) { //special non-collision coord
        $li.data("scord", mapPiece.scord);
        $li.addClass('special-coord');
      }

      if (self.game.mountains.some(m => m.equals(pos))) { //mountain
        $li.data("color", self.game.mountainData.color);
        $li.data("image", self.game.mountainData.image);
        $li.css('background', "url('"+ self.imgRootPath+self.game.mountainData.image+"')");
        $li.addClass("mountain");

      } else if (mapPiece.color) { //old piece
        $li.data("color", mapPiece.color);
        $li.data("image", mapPiece.image);
        $li.css('background', "url('"+self.imgRootPath+mapPiece.image+"')");

        if (mapPiece.monster) {
          $li.addClass('monster');
        }

      } else { //empty
        
        $li.addClass('dropzone');

          self.addDroppableListener($li);  
          $li.on('click', (e) => {
            //existing shapes can be removed
            const tray = self.game.trays.find(tray => isActiveTrayForCoords(tray, rowIdx, tileIdx));
    
            if (tray) {
              self.revertRender(tray.shape);
              self.setupPieceTray(tray);
              tray.shape.placed = false;
              tray.shape.corner = undefined; 
              tray.shape.transformCoords = undefined;
            }
          })
      }

      $ul.append($li);
    });
  });

  $ul.css('width', (this.board.size+1) * (Tile.size+1) + 5);

  this.$el.append($ul);
};

View.prototype.addDroppableListener = function ($li) {

  const self = this;
  interact('.dropzone').dropzone({
    // Require a 75% element overlap for a drop to be possible
    overlap: 0.4,
  
 
    // ondropactivate: function (event) {
    //   self.activeTray = tray;
    // },
    ondragenter: function (event) {
      var draggableElement = event.relatedTarget
      var dropzoneElement = event.target
      // feedback the possibility of a drop
      dropzoneElement.classList.add('drop-target')
      draggableElement.classList.add('can-drop')

    },
    ondragleave: function (event) {
      // remove the drop feedback style
      event.target.classList.remove('drop-target')
      event.relatedTarget.classList.remove('can-drop')


    },
    ondrop: function (event) {
      const dragTarget = $(event.relatedTarget).data('pos');
      const dropTarget = $(event.target).data('pos');

      //we might be dragging by element [2,3] -> always get left top
      const corner = [dropTarget[0]-dragTarget[0], dropTarget[1]-dragTarget[1]];
  
      if (self.placeShapeIfPossible(corner)) {
       
        self.activeShape.placed = true;
        self.activeShape.corner = corner;
        event.relatedTarget.parentElement.style.visibility = 'hidden';
      }
    },
    ondropdeactivate: function (event) {
      // remove active dropzone feedback
      event.target.classList.remove('drop-target')
    }
  })

};

View.prototype.getCurrentItems = function () {
  return this.game.trays.map(t => t.shape.export());
}


View.prototype.transformCoords = function (startPos, coords) {
  return coords.map(function (pos) {
    return [startPos[0]+pos[0], startPos[1]+pos[1]];
  });
};

View.prototype.revertRender = function (shape) {
  if (!shape.corner || !shape.transformCoords) {
    console.error("Attempt to revert unplaced tile!", shape);
    return;
  }

  const self = this;
  $('.group > li').each(function (idx, li) {
    var $li = $(li);
    shape.transformCoords.forEach(function (pos, i) {
      if (pos.equals($li.data('pos'))) {
        $li.css("background", "");
        $li.css('outline', 'unset');
        $li.removeData('color');
        $li.removeClass('active monster'); //monster - just to be sure
      }  
    });

    if (shape?.scoords) {
      for (let pos of self.transformCoords(shape.corner, shape.scoords)) {
        if (pos.equals($li.data('pos'))) {
          $li.removeClass('special-coord');
        }
       
      }
    }
  
  }); 
  $('.'+shape.tray.class).css('visibility', 'hidden');
};

View.prototype.placeShapeIfPossible = function (startPos) {
  var self = this;

  this.activeShape = this.activeTray.shape;

  var coords = this.activeShape.coords;
  var maxLen = this.game.board.size;

  this.activeShape.transformCoords = this.transformCoords(startPos, coords);
  for (let pos of this.activeShape.transformCoords) {
    if (!this.activeShape || pos[0] < 0 || pos[1] < 0 || pos[0] >= maxLen || pos[1] >= maxLen) {
      this.activeShape = undefined;
      return;
    }
    
    $('.group > li').each(function (idx, li) {
      var $li = $(li);
      if (!pos.equals($li.data('pos'))) return;
      if (($li.data('color') !== undefined)) {
        self.activeShape = undefined;
      }
    });
  }

  if (!this.activeShape) return false;
  //else render

  var color = this.activeShape.color;
  var image = this.activeShape.image;
  var monster = this.activeShape.monster;

  $('.group > li').each(function (idx, li) {
    const $li = $(li),
      tilePos = $li.data('pos');

    for (let pos of self.activeShape.transformCoords) {
      if (pos.equals(tilePos)) {
        $li.css('background', "url('"+self.imgRootPath+image+"')");
        $li.data('color', color);
        $li.css('outline', '1px solid ' + color);
        $li.addClass('active');

        if (monster) {
          $li.addClass('monster');
        }
      }
    }

    if (self.activeShape?.scoords) {
      for (let pos of self.transformCoords(startPos, self.activeShape.scoords)) {
        if (pos.equals(tilePos)) {
          $li.addClass('special-coord');
        }
      }
    }
  });

  this.game.playMove();
  return true;
};

View.prototype.full = function (arr) {
  var full = true;
  arr.forEach(function (li) {
    var $li = $(li);
    if ($li.data('color') === undefined) {
      full = false;
    }
  });
  return full;
};

// View.prototype.clear = function (arr) {
//   this.game.score += 93
//   var self = this;
//   arr.forEach(function (li) {
//     var $li = $(li);
//     $li.animate({ 'background': '#ccc' }, 500, function() {
//       $li.removeAttr('style');
//       $li.removeData('color');
//       $li.removeClass('piece');
// 		});
//   });

//   return true;
// };

View.prototype.setupPieceTray = function (tray) {

  const $parent = tray._order > 2 ? this.$trayEl2 : this.$trayEl;

  if (!tray) {
    var $c = $("<ul class='d-flex border-1 rounded-2 p-1 m-1 position-relative'>");
    $c.css('display', 'inline-block');
    $c.css('background', '#eeea');

    for (let i = 0; i < Tray.size; i++) {
      for (let j = 0; j < Tray.size; j++) {
        var $li = $("<li>");
        $li.css({width: Tile.size, height: Tile.size})
  
        if ((i+j)%3==0) {
          $li.css('background', "color:#ccc");
        } else {
          $li.css('background', 'transparent');
        }
  
        $li.data("pos", [-1,-1]);
        $c.append($li);
      }
    }
    $c.css('width', Tray.size * (Tile.size+1) + 10);
    $parent.append($c);
    return;
  }


  var self = this;
  var $ul = $("<ul class='border-1 rounded-1 p-1 m-1 position-relative'>");
  $ul.addClass(tray.class);
  $ul.css('background', '#eeea');
  $ul.css('z-index', '999');

  tray.grid.forEach(function (row, rowIdx) {
    row.forEach(function (tile, tileIdx) {
      var $li = $("<li>");
      $li.css({width: Tile.size, height: Tile.size});

      if (tile.scord) { //special non-collision coord
        $li.data("scord", tile.scord);
        $li.addClass('special-coord');
      }

      if (!tile.empty) {
        $li.data("color", tile.color);
        $li.data("image", tile.image);
        $li.css('background', "url('"+self.imgRootPath+tile.image+"')");
        $li.addClass("piece");
      } else {
        $li.css('background', 'transparent');
      }

      $li.data("pos", [rowIdx, tileIdx]);
      $ul.append($li);
    });
  });
  $ul.css('width', tray.size * (Tile.size+1) + 10);


  var timer = 0;
  function pointDown() {
    timer = Date.now();
  }

  function pointUp() {
    const d = Date.now() - timer;
    timer = 0;

    const moved = $ul[0]._moving;
    $ul[0]._moving=false;
    if ($ul.css('visibility')!='visible' || moved) return;
    
    if (d < 120) {
      tray.rotateRight();
    } else {
      tray.flip();
    }
    self.setupPieceTray(tray);
  }

  $ul[0].addEventListener("touchstart", pointDown, false);
  $ul[0].addEventListener('touchend', pointUp,false);

  $ul.on('click', e => {
    
  })

  $elem = $(`.${tray.class}`);
  if ($elem.length) {
    $elem.replaceWith($ul);
  } else {
    var $c = $("<div>");
    $c.css('display', 'inline-block');
    $c.append($ul);
    $parent.append($c);
  }

  this.addDraggableListener(tray);
};


View.prototype.addDraggableListener = function (tray) {

  const cls = tray.class;
  const self = this;

  if (this.interaction == 'none') return;
  const selector = this.interaction == 'monster' ? `.${cls} > li.monster` : `.${cls} > li.piece`;
  
  interact(`.${cls} > li.piece`).draggable({
    inertia: true,
    modifiers: [
      interact.modifiers.restrictRect({
        restriction: 'parent',
        endOnly: true
      })
    ],
    autoScroll: true,
    // dragMoveListener from the dragging demo above
    listeners: { 
      move: (event) => {
        const node = event.target.parentElement;
        // keep the dragged position in the data-x/data-y attributes
        var x = (parseFloat(node.getAttribute('data-x')) || 0) + event.dx
        var y = (parseFloat(node.getAttribute('data-y')) || 0) + event.dy
      
        // translate the element
        node.style.transform = 'translate(' + x + 'px, ' + y + 'px)'
      
        // update the posiion attributes
        node.setAttribute('data-x', x)
        node.setAttribute('data-y', y)
      },
      start: (event) => {
        self.activeTray = tray;
        timer = Date.now();

        const node = event.target.parentElement;
        node._moving=true;
        node.style.background = 'transparent';
        self.$el.css({
          "touch-action": "none", "user-select": "none"
        });
        
      }
    }
  }).on('dragend', event=> {
    self.activeTray = null;

    const node = event.target.parentElement;
    node.style.transform = 'initial';
    node.setAttribute('data-x', 0);
    node.setAttribute('data-y', 0);
    node.style.background = '#eeea';

    self.$el.css({
      "touch-action": "", "user-select": ""
    });
  })
  


  // $(`.${cls} > li.piece`).draggable({
  //   cursor: 'pointer',
  //   revert: false,
  //   helper: function(e) {
  //     var helperList = $('<ul class="draggable-helper">');
  //     helperList.append($(`.${cls} > li`).clone());
  //     return helperList;
  //   },
  //   start: function (e, ui) {
  //     self.activeTray = tray;
  //   },
  //   drag: function (e, ui) {
  //     $(`.${cls} > li.piece`).css('visibility', 'hidden');
  //   },
  //   stop: function(e, ui) {
  //     if (!self.activeShape) {
  //       $(`.${cls} > li.piece`).css('visibility', 'visible');
  //     }
	// 	},
  //   cursorAt: { top: 40 }
  // });
};

Array.prototype.equals = function (array) {
  if (!array)
      return false;
  if (this.length != array.length)
      return false;
  for (var i = 0, l=this.length; i < l; i++) {
      if (this[i] instanceof Array && array[i] instanceof Array) {
          if (!this[i].equals(array[i]))
              return false;
      }
      else if (this[i] != array[i]) {
          return false;
      }
  }
  return true;
}

module.exports = View;
