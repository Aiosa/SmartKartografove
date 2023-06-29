Array.prototype.random = function () {
  return this[Math.floor(Math.random() * this.length)];
}

function Shape (data) {
	$.extend(this, data);
};

Shape.prototype.rotateRight = function(right) {
  const coords = [...this.coords];

  function rotate(arr) {
    for (let c of arr) {
      const t = c[0];
      c[0] = -c[1];
      c[1] = t; 
    }
  }

  rotate(this.coords);
  if (this.scoords) rotate(this.scoords);

  //move to corner up
  let mx = Math.min(...this.coords.map(c => c[0]));
  let my = Math.min(...this.coords.map(c => c[1]));
  if (this.scoords) {
    mx = Math.min(mx, ...this.scoords.map(c => c[0]));
    my = Math.min(my, ...this.scoords.map(c => c[1]));
  }

  for (let c of this.coords) {
    c[0] = c[0]-mx;
    c[1] = c[1]-my; 
  }

  if (this.scoords) {
    for (let c of this.scoords) {
      c[0] = c[0]-mx;
      c[1] = c[1]-my; 
    }
  }
};

Shape.prototype.export = function(props=['color', 'coords', 'image', 'corner', 'placed', 'card_uid', 'scoords', 'monster']) {

  const result = {};
  Object.entries(this).forEach(([k,v]) => {
    if (props.some(x => x === k)) result[k]=v;
  });
  return result;
};

Shape.prototype.flip = function(horizontal) {
  let mx = Math.max(...this.coords.map(c => c[0]));
  let my = Math.max(...this.coords.map(c => c[1]));
  if (this.scoords) {
    mx = Math.max(mx, ...this.scoords.map(c => c[0]));
    my = Math.max(my, ...this.scoords.map(c => c[1]));
    this.scoords = this.scoords.map(pos => horizontal ? [mx - pos[0], pos[1]] : [pos[0], my - pos[1]]);
  }
  this.coords = this.coords.map(pos => horizontal ? [mx - pos[0], pos[1]] : [pos[0], my - pos[1]]);
};

module.exports = Shape;
