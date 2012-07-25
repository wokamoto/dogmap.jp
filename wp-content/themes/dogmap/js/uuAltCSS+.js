
// === uuAltCSSPlus ===
// depend: uuMeta, uuQuery, uuStyle, uuStyleSheet, uuAltCSS, uuColor,
//         uuCanvas, uuLayer, uuResize
/*
window.UUALTCSS_IMAGE_DIR = "."
window.UUALTCSS_ENABLE_MAXMIN = 0
uuAltCSSPlus.redraw()
 */
(function() {
var _altcssplus, // inner namespace
    _mm = uuMeta,
    _mix = _mm.mix,
    _ss = uuStyleSheet,
    _query = uuQuery,
    _style = uuStyle,
    _color = uuColor,
    _win = window,
    _doc = document,
    _float = parseFloat,
    _uaver = _mm.uaver,
    _ie = _mm.ie,
    _ie6 = _ie && _uaver === 6,
    _ie7 = _ie && _uaver === 7,
    _ie67 = _ie6 || _ie7,
    _runstyle = _mm.runstyle,
    _ssid = "uuAltCSSPlus",
    _imgdir = _win.UUALTCSS_IMAGE_DIR || ".",
    _spacer = _imgdir.replace(/\/+$/, "") + "/1dot.gif",
    _canvas = _mm.canvas && !(_mm.opera && _uaver < 9.5),
    // enable functions
    _enable = {
      hover:      _ie6  ? 0x8   : 0, // IE6 :hover
      stdcare:    _ie6  ? 0x10  : 0, // IE6 position: absolute bug
      posfxd:     _ie6  ? 0x20  : 0, // IE6 position: fixed
      alphapng:   _ie6  ? 0x40  : 0, // IE6 <img src="some.alpha.png">
      maxmin:     _ie67 ? 0x80  : 0, // IE6-IE7 max-width (IE7 td,th support)
      opacity:    _ie   ? 0x100 : 0, // IE6-IE8 opacity:
      disptbl:    _ie67 ? 0x200 : 0, // IE6-IE7 display: table
      boxshadow:  (_canvas && !_mm.webkit522)
                        ? 0x400 : 0,    // -webkit-box-shadow:
      boxreflect: (_canvas && !_mm.webkit530)
                        ? 0x800 : 0,    // -webkit-box-reflect:
      bradius:    (_canvas && !_mm.webkit522)
                        ? 0x1000 : 0,   // -webkit-border-radius:
      bimage:     (_canvas && !_mm.webkit522)
                        ? 0x2000 : 0,   // -webkit-border-image:
      gradient:   (_canvas && !_mm.webkit530)
                        ? 0x4000 : 0    // -webkit-gradient:
    },
    _deny = 0, // 1: deny removeChild
    _plan = { delay: [], alphapng: [], disptbl: [], posfxd: [],
              maxmin: [], boxeffect: [] },
    _find = {
      prop: {
        display: 1,
        position: 2,
        background: 3,
        "background-image": 3,
        opacity:                  _enable.opacity,
        "-webkit-box-shadow":     _enable.boxshadow,
        "-webkit-box-reflect":    _enable.boxreflect,
        "-webkit-border-radius":  _enable.bradius,
        "-webkit-border-image":   _enable.bimage,
        "-webkit-border-top-left-radius":     _enable.bradius,
        "-webkit-border-top-right-radius":    _enable.bradius,
        "-webkit-border-bottom-right-radius": _enable.bradius,
        "-webkit-border-bottom-left-radius":  _enable.bradius
      },
      display:    { table: _enable.disptbl },
      position:   { fixed: _enable.posfxd }
    },
    TRANSPARENT = "transparent",
    TRIM = /^\s+|\s+$/g;

if (!_win.UUALTCSS_ENABLE_MAXMIN) {
  _enable.maxmin = 0;
}
if (_enable.boxshadow  |
    _enable.boxreflect |
    _enable.bradius    |
    _enable.bimage     |
    _enable.gradient) {
  _enable.boxeffect = 0x10000;
}

// find keyword
function find(pair, expr) {
  var bits = 0, prop = {}, order = [], v, w, i = 0, j, jz, ary;

  while ( (v = pair[i++]) ) {
    switch (w = _find.prop[v.prop] || 0) {
    case 1: bits |= _find.display[v.val]; break;  // display:
    case 2: bits |= _find.position[v.val]; break; // position:
    case 3:
      if (v.val.indexOf("-webkit-gradient") >= 0) { // background(-image)
        bits |= _enable.gradient;
      }
      break;
    default:
      bits |= w;
    }
    prop[v.prop] = v.val;
    order.push(v.prop);
  }
  if (_ie6) {
    if (/:hover/.test(expr)) {
      bits |= _enable.hover;
    }
  }
  return { bits: bits, prop: prop, order: order.join(",") + "," };
}

function redraw() {
  _enable.posfxd && posfxdRecalc();
  _enable.maxmin && maxminRecalc();
  _enable.boxeffect && boxeffectRecalc();
}

_altcssplus = {
  init: function(context) {
    _enable.stdcare && stdcare(context);

    if (_enable.posfxd) {
      _plan.delay.push(function() {
        _ss.create(_ssid);
        _ss.insertRule(_ssid, ".uuposfix", // not "uucssposfix"
          ("z-index:5000;" +
           "behavior:expression(" +
           "this.style.pixelTop=document.#.scrollTop+this.uuCSSPosFxd.vpx," +
           "this.style.pixelLeft=document.#.scrollLeft+this.uuCSSPosFxd.hpx)").
          replace(/#/g, _mm.quirks ? "body" : "documentElement")
        );
      });
    }
  },

  // make plan
  plan: function(nodeList, revalidate, context) {
    if (!revalidate) {
      _enable.alphapng && alphapng(context);
    }
  },

  // pre-validate plan
  prevalidate: function(nodeList, revalidate, context) {
    var i, iz;

    if (_plan.delay.length) {
      for (i = 0, iz = _plan.delay.length; i < iz; ++i) {
        _plan.delay[i]();
      }
      _plan.delay = []; // clear
      _enable.stdcare = 0;
    }
    if (!revalidate) {
      _enable.alphapng && alphapngRecalc(context);
    }
  },

  // post-validate plan
  postvalidate: function(nodeList, revalidate, context) {
    var node, bits, info, i = 0, iz = nodeList.length;

    for (; i < iz; i += 2) {
      node = nodeList[i];
      info = nodeList[i + 1];
      bits = info.bits;

      (bits & _enable.opacity) && opacity(node);
      (bits & _enable.posfxd)  && posfxd(node);

      if (!revalidate) {
        if (bits & _enable.disptbl) {
          _plan.disptbl.push(node); // stock
        }
        if (bits & _enable.hover) {
          (function(node) {
            _mm.event.bind(node, "mouseenter", function(evt) {
              node.className += node.uuCSSHover;
            });
            _mm.event.bind(node, "mouseleave", function(evt) {
              node.className =
                  node.className.replace(/\s*uucsshover[\d]+/g, "");
            });
          })(node);
        }
        if (bits & (_enable.boxshadow  |
                    _enable.boxreflect |
                    _enable.bradius    |
                    _enable.bimage     |
                    _enable.gradient)) {
          _plan.boxeffect.push(node, info.prop, info.order); // stock
        }
      }
    }
    if (!revalidate) {
      _plan.posfxd.length && posfxdRecalc();
      _plan.disptbl.length && disptbl();
      _plan.boxeffect.length && boxeffect(revalidate);
    }
    _enable.maxmin && (maxmin(context),
                       maxminRecalc());

    if (!revalidate) {
      if (_enable.posfxd | _enable.maxmin | _enable.boxeffect) {
        _mm.event.resize(redraw, 1); // use resize-agent
      }
    }
  }
};

// ---------------------------------------------------------
// care: position: absolute bug(cannot select text)
//       smooth scroll
//       background image cache
// for IE6
function stdcare(context) {
  function markup(elm) {
    _plan.delay.push(function() {
      var st = elm.style;
      // text selection bug
      if (!_mm.quirks) {
        st.height = "100%";
//      st.margin  = "0"; // ToDo
//      st.padding = "0"; // ToDo
      }
      st.backgroundAttachment = "fixed"; // smooth scroll
      !_style.getBGImg(elm) && (st.backgroundImage = "url(none)");
    });
  }
  markup(_query.tag("html")[0]); // <html>
  markup(_doc.body);             // <body>
  _plan.delay.push(function() {
    _doc.execCommand("BackgroundImageCache", false, true);
  });
}

// ---------------------------------------------------------
// care: opacity
// for IE6, IE7, IE8
function opacity(elm) {
  var v = elm.style.opacity || elm.currentStyle.opacity;

  _style.setOpacity(elm, _float(v) || 1.0);
}

// ---------------------------------------------------------
// care: "position: fixed"
// for IE6
function posfxd(elm) {
  if ("uuCSSPosFxd" in elm) { return; } // already fixed

  var vp = _style.getViewport(), rect = _style.getRect(elm),
      cs = elm.currentStyle,
      v = cs.top  !== "auto", // vertical
      h = cs.left !== "auto", // horizontal
      pxfn = _style.getPixel;

  _plan.posfxd.push(elm); // mark

  elm.uuCSSPosFxd = { // bond
    mode: (v ? 1 : 2) | (h ? 4 : 8), // 1:top, 2:bottom, 4:left, 8:right
    vcss: v ? cs.top : cs.bottom,
    hcss: h ? cs.left : cs.right,
    vpx: v ? (pxfn(elm, "paddingTop") + pxfn(elm, "top"))
           : (vp.h - rect.oh - pxfn(elm, "bottom")),
    hpx: h ? (pxfn(elm, "paddingLeft") + pxfn(elm, "left"))
           : (vp.w - rect.ow - pxfn(elm, "right"))
  };
  elm.className += " uuposfix";
  elm.style.position = "absolute"; // position:fixed -> position:absolute
}

function posfxdRecalc() {
  var ary = [], v, w, i = 0, iz = _plan.posfxd.length,
      vp = _style.getViewport(), cs, p;

  for (; i < iz; ++i) {
    v = _plan.posfxd[i];
    if (v && (p = v.uuCSSPosFxd)) {
      cs = v.currentStyle;
      w = _style.toPixel(v, p.vcss, 1);
      p.vpx = (p.mode & 0x1) ? (_style.getPixel(v, "paddingTop") + w)
                             : (vp.h - v.offsetHeight - w);
      w = _style.toPixel(v, p.hcss, 1);
      p.hpx = (p.mode & 0x4) ? (_style.getPixel(v, "paddingLeft") + w)
                             : (vp.w - v.offsetWidth - w);
      ary.push(v);
    }
  }
  // http://www.microsoft.com/japan/msdn/columns/dude/dude061198.aspx
  ary.length && _doc.recalc(); // update
  _plan.posfxd = ary;
}

// ---------------------------------------------------------
// care: <img src="some.alpha.png" />
// care: <img class="alpha" src="some.png" />
// for IE6
function alphapng(context) {
  function filter(elm, src, method) {
    return [" progid:DXImageTransform.Microsoft.AlphaImageLoader",
            "(src='", src, "',sizingMethod='", method, "')"].join("");
  }

  var rv = [], v, i = 0,
      node = _query('img[src$=".png"],input[type=image][src$=".png"]',
                    context);

  while ( (v = node[i++]) ) {
    if (/\.alpha\.png$/.test(v.src) ||
        / alpha /.test(" " + v.className + " ")) {
      rv.push({ elm: v, filter: filter(v, v.src, "image"),
                orgw: v.width, orgh: v.height });
    }
  }

  _plan.alphapng = rv;
}

function alphapngRecalc(context) {
  var hash, ary = _plan.alphapng, v, i = 0;

  while ( (hash = ary[i++]) ) {
    v = hash.elm;
    v.style.filter += hash.filter;
    v.src = _spacer;
    (v.tagName === "IMG") ? (v.width = hash.orgw, v.height = hash.orgh)
                          : (v.style.zoom = 1);
  }
  _plan.alphapng = []; // clear
}

// ---------------------------------------------------------
// care: max-width, min-width, max-height, min-height
// for IE6, IE7(td, th only)
function maxmin(context) {
  var rv = [], xw, nw, xh, nh,
      node = _ie6 ? _query.tag("*", context)
                  : _query("td,th", context), // IE7 td, th
      v, i = 0, cs, rex1 = /^(inherit|none|auto)$/,
      blockLevel = { block: 1, "inline-block": 1, "table-cell": 1 };

  while ( (v = node[i++]) ) {
    cs = v.currentStyle;
    if (_ie7 || blockLevel[cs.display]) {
      xw = cs["max-width"]  || cs.maxWidth || ""; // length | % | none
      nw = cs["min-width"]  || cs.minWidth || ""; // length | %
      xh = cs["max-height"] || cs.maxHeight|| ""; // length | % | none
      nh = cs["min-height"] || cs.minHeight|| ""; // length | %
                                                  // (ie6 default "auto")
      rex1.test(xw) && (xw = "");
      rex1.test(nw) && (nw = "");
      rex1.test(xh) && (xh = "");
      rex1.test(nh) && (nh = "");

      if (xw === "" && nw === "" &&
          xh === "" && nh === "") {
        if ("uuCSSBoostMaxMin" in v) {
          delete v["uuCSSBoostMaxMin"];
        }
        continue; // exclude
      }

      _mix(v, {
        uuCSSBoostMaxMin: {}
      }, 0, 0);
      _mix(v.uuCSSBoostMaxMin, {
        maxWidth: xw,
        minWidth: nw,
        maxHeight: xh,
        minHeight: nh
      });
      _mix(v.uuCSSBoostMaxMin, {
        orgWidth:  v.currentStyle.width,
        orgHeight: v.currentStyle.height
      }, 0, 0);
      rv.push(v);
    }
  }
  _plan.maxmin = rv;
}

function maxminRecalc() {
  var elm, i = 0, hash,
      calcMaxWidth, calcMinWidth, calcMaxHeight, calcMinHeight,
      run, width, height, rect;

  while ( (elm = _plan.maxmin[i++]) ) {
    hash = elm.uuCSSBoostMaxMin;

    calcMaxWidth  = maxminRecalcSize(elm, hash, "maxWidth", 1);
    calcMinWidth  = maxminRecalcSize(elm, hash, "minWidth", 1);
    calcMaxHeight = maxminRecalcSize(elm, hash, "maxHeight");
    calcMinHeight = maxminRecalcSize(elm, hash, "minHeight");

    // recalc
    if (calcMaxWidth || calcMinWidth) {

      // recalc max-width
      if (calcMinWidth > calcMaxWidth) {
        calcMaxWidth = calcMinWidth;
      }

      // recalc width
      // width: auto !important
      run = elm.runtimeStyle.width;  // keep runtimeStyle.width
      elm.runtimeStyle.width = hash.orgWidth;
      elm.style.width = "auto";
      rect = elm.getBoundingClientRect(); // re-validate
      width = rect.right - rect.left;

      elm.style.width = hash.orgWidth; // o
      elm.runtimeStyle.width = run; // restore style

      // recalc limits
      if (width > calcMaxWidth) {
        width = calcMaxWidth;
        elm.style.pixelWidth = width;
      } else if (width < calcMinWidth) {
        width = calcMinWidth;
        elm.style.pixelWidth = width;
      }
    }

    if (calcMaxHeight || calcMinHeight) {

      // recalc max-height
      if (calcMinHeight > calcMaxHeight) {
        calcMaxHeight = calcMinHeight;
      }

      // recalc height
      // height: auto !important
      run = elm.runtimeStyle.height;  // keep runtimeStyle.height
      elm.runtimeStyle.height = hash.orgHeight;
      elm.style.height = "auto";
      rect = elm.getBoundingClientRect(); // re-validate
      height = rect.bottom - rect.top;

      elm.style.height = hash.orgHeight; // o
      elm.runtimeStyle.height = run; // restore style

      // recalc limits
      if (height > calcMaxHeight) {
        height = calcMaxHeight;
        elm.style.pixelHeight = height;
      } else if (height < calcMinHeight) {
        height = calcMinHeight;
        elm.style.pixelHeight = height;
      }
    }
  }
}

function maxminRecalcSize(elm, hash, prop, horizontal) {
  var rv = 0, rect;

  if (hash[prop] !== "") {
    if (/[\d\.]+%$/.test(hash[prop])) {
      rect = elm.parentNode.getBoundingClientRect();
      rv = _float(hash[prop]);
      rv = (horizontal ? (rect.right - rect.left)
                       : (rect.bottom - rect.top)) * rv / 100;
    } else {
      rv = _style.toPixel(elm, hash[prop], 1);
    }
  }
  return rv;
}

// ---------------------------------------------------------
// care: display:table, display: table-cell
// for IE6, IE7
function disptbl() {
  var node = _plan.disptbl, v, i, j, iz,
      tbl, row, cell;

  for (i = 0, iz = node.length; i < iz; ++i) {
    v = node[i];
    tbl = _doc.createElement("table");
    // copy attrs
    tbl.id = v.id;
    tbl.title = v.title;
    tbl.className = v.className;
    // copy style
    tbl.style.cssText = v.style.cssText;
    tbl.style.borderCollapse = "collapse";

    row = tbl.insertRow(); // add last row

    for (j = v.firstChild; j; j = j.nextSibling) {
      cell = row.insertCell(); // add last cell
      // copy attrs
      cell.id = j.id;
      cell.title = j.title;
      cell.className = j.className;
      // copy style
      cell.style.cssText = j.style.cssText;
      cell.style.margin = "0"; // force margin: 0
      // copy event handler(click, dblclick only)
      cell.onclick = j.onclick;
      cell.ondblclick = j.ondblclick;

      while (j.firstChild) {
        cell.appendChild(j.removeChild(j.firstChild));
      }
    }
    v.parentNode.replaceChild(tbl, v);
  }
  _plan.disptbl = []; // clear
}

// ---------------------------------------------------------
function boxeffect(revalidate) {
  var BTW = "borderTopWidth",
      BLW = "borderLeftWidth",
      BRW = "borderRightWidth",
      BBW = "borderBottomWidth",
      BTC = "borderTopColor",
      nodeProp = _plan.boxeffect, node, i = 0, iz = nodeProp.length,
      ns, vs, css, prop, order, view, hash, dim, canvas,
      topx = _style.toPixel;

  for (; i < iz; i += 3) { // [(node, prop@css, order), (,,), ... ]
    node  = nodeProp[i];     // node
    css   = nodeProp[i + 1]; // prop
    order = nodeProp[i + 2]; // order
    view  = node.parentNode;
    ns = _ie ? node[_runstyle] : _runstyle(node, ""); // node currentStyle
    vs = _ie ? view[_runstyle] : _runstyle(view, ""); // view currentStyle

    if (ns.display === "none" || vs.display === "none") {
      continue;
    }

    // IE6,IE7 CSS layout bugfix
    if (_ie67) {
      if (!vs.hasLayout) {
        view.style.zoom = 1;
      }
      node.style.zoom = 1; // apply z-index(sink canvas)
      if (ns.position === "static") {
        node.style.position = "relative"; // set "position: relative"
      }
    }

    prop = {
      css:        css,
      order:      order,
      view:       view,
      node:       node,
      layer:      0,
      slmode:     0, // 1 = Silverlight mode
      nodeRect:   _style.getRect(node),
      viewRect:   _style.getRect(view),
      nodeOffset: 0, // lazy
      border:     { render: 0,
                    t: topx(node, ns[BTW]),
                    l: topx(node, ns[BLW]),
                    r: topx(node, ns[BRW]),
                    b: topx(node, ns[BBW]),
                    tc: _color.parse(ns[BTC]) }, // border-top-color
      margin:     { t: topx(node, ns.marginTop),
                    l: topx(node, ns.marginLeft),
                    r: topx(node, ns.marginRight),
                    b: topx(node, ns.marginBottom) },
      mbg:        { render: 0, type: [],
                               m: [""], r: ["repeat"], p: ["0% 0%"],
                               rgba: { r: 0, g: 0, b: 0, a: 0 }, 
                               altcolor: _style.getBGColor(node, 1, 1),
                               grad: [], img: [], tid: -1 },
      bradius:    { render: 0, r: [0,0,0,0] },
      boxshadow:  { render: 0, rgba: 0, ox: 0, oy: 0, blur: 0 },
      boxreflect: { render: 0, dir: 0, offset: 0, url: 0, img: 0,
                    grad: { render: 0 } }
    };
    if (prop.border.tc[1]) { // has border
      if (prop.border.t || prop.border.l ||
          prop.border.r || prop.border.b) {
        prop.border.render = 1;
      }
    }

    parseBoxReflectParam(ns, prop);

    if (prop.boxreflect.render) {
      hash = prop.boxreflect;
      if (hash.dir === "below") {
        if (node.tagName === "IMG") {
          dim = _style.getActualDimension(node);

          prop.layer = new uuLayer(view, dim.w, dim.h * 2 + hash.offset);
          prop.layer.createReflectionLayer(
              "reflect", node, 0, 0, 0, 0, 0,
              void 0, 0, hash.offset);
//        node.style.display = "none";
          node.style.visibility = "hidden";
        }
      }
    } else {
      canvas = "vmlcanvas";
      if (_ie && _mm.slver > 0 &&
          prop.css["-uu-canvas-type"] === "sl") {
        prop.slmode = 1; // Silverlight mode
        canvas = "canvas";
      }
      prop.layer = new uuLayer(view);
      prop.nodebgLayer =
          prop.layer.createLayer("nodebg", canvas, 0, 1,
                                 prop.nodeRect.ow, prop.nodeRect.oh);
      prop.viewbgLayer =
          prop.layer.createLayer("viewbg", canvas, 0, 1);
      prop.layer.appendLayer("node", node);
    }

    // http://d.hatena.ne.jp/uupaa/20090719
    if (_ie67) {
      prop.ie6borderorg = {
        marginTop: ns.marginTop,
        marginLeft: ns.marginLeft,
        marginRight: ns.marginRight,
        marginBottom: ns.marginBottom,
        borderTopWidth: ns[BTW],
        borderLeftWidth: ns[BLW],
        borderRightWidth: ns[BRW],
        borderBottomWidth: ns[BBW]
      };
      prop.ie6borderfix = {
        marginTop: (prop.margin.t + prop.border.t) + "px",
        marginLeft: (prop.margin.l + prop.border.l) + "px",
        marginRight: (prop.margin.r + prop.border.r) + "px",
        marginBottom: (prop.margin.b + prop.border.b) + "px",
        border: "0px none"
      };
    }

    detectMultipleBackgroundImage(ns, prop);

    node.uuAltCSSBoxEffect = prop; // bond
    prop.slmode ? boxeffectDelay(prop, 0)
                : boxeffectDraw(prop, 0);
  }
}

function boxeffectDelay(prop) {
  uuCanvas.ready(function() {
    boxeffectDraw(prop, 0);
  });
}

function boxeffectRecalc() {
  var nodeProp = _plan.boxeffect, node, view, prop, ns, vs,
      i = 0, iz = nodeProp.length;

  for (; i < iz; i += 3) {
    node = nodeProp[i]; // node
    view  = node.parentNode;

    ns = _ie ? node[_runstyle] : _runstyle(node, ""); // node currentStyle
    vs = _ie ? view[_runstyle] : _runstyle(view, ""); // view currentStyle

    if (ns.display === "none" || vs.display === "none") {
      continue;
    }

    prop = node.uuAltCSSBoxEffect;
    boxeffectRecalcRect(node, prop);
    // improvement of response time
    boxeffectDelayRecalc(prop);
  }
}

function boxeffectRecalcRect(node, prop) {
  // http://d.hatena.ne.jp/uupaa/20090719
  if (_ie67) { // restore border and margin state
    _mm.mix(node.style, prop.ie6borderorg);
  }
  // update rect
  prop.nodeRect = _style.getRect(node);
  prop.viewRect = _style.getRect(prop.view);
}

function boxeffectDelayRecalc(prop) {
  setTimeout(function() {
    boxeffectDraw(prop, 1);
  }, 0);
}

function boxeffectDraw(prop, redraw) {
  var node = prop.node,
      view = prop.view,
      layer = prop.layer,
      nodebg = prop.nodebgLayer,
      viewbg = prop.viewbgLayer,
      nctx = layer.getContext("nodebg"),
      vctx = layer.getContext("viewbg"),
      hash, ary, ns;

  if (0) { // debug
    layer.view.style.border = "2px solid pink";
    nodebg.style.border = "5px solid red";
    viewbg.style.border = "5px solid green";
  }

  if (redraw) {
    viewbg && layer.resizeLayer("viewbg", prop.viewRect.w, prop.viewRect.h);
    nodebg && layer.resizeLayer("nodebg", prop.nodeRect.w, prop.nodeRect.h);
  }

  // most important
  prop.nodeOffset = getOffsetFromAncestor(prop.node, view);

  ns = _ie ? node[_runstyle]
           : _runstyle(node, "");
  parseBorderRadiusParam(ns, prop);
  parseBoxShadowParam(ns, prop);

  // CSS3 background-origin:
  if (nodebg) {
    nodebg.style.left =
        (prop.nodeOffset.x + (_ie67 ? 0 : prop.border.l)) + "px";
    nodebg.style.top =
        (prop.nodeOffset.y + (_ie67 ? 0 : prop.border.t)) + "px";
  }

  if (viewbg) {
    // ToDo: clipping path for background-color: rgba(,,,0.5) support
/* keep
    if (0) {
      vctx.save();
      vctx.rect(0, 0, prop.viewRect.w, prop.viewRect.h);
      boxpath(vctx,
        prop.nodeOffset.x + prop.border.l,
        prop.nodeOffset.y + prop.border.t,
                prop.nodeRect.ow - prop.border.l - prop.border.r,
                prop.nodeRect.oh - prop.border.t - prop.border.b,
                prop.bradius.r,
                1); // open path
      vctx.clip();
    }
 */
    // draw shadow
    if (prop.boxshadow.render) {
      hash = prop.boxshadow;
      vctx.save();
      drawFakeShadow(vctx,
                     prop.nodeOffset.x - hash.blur / 2 + hash.ox,
                     prop.nodeOffset.y - hash.blur / 2 + hash.oy,
                     prop.nodeRect.ow + hash.blur,
                     prop.nodeRect.oh + hash.blur,
                     hash.rgba,
                     Math.max(hash.blur, Math.abs(hash.ox * 2),
                                         Math.abs(hash.oy * 2)),
                     prop.bradius.r);
      vctx.restore();
    }

    // draw border
    if (prop.border.render) {
      ary = [];
      if (prop.boxshadow.render) {
        hash = prop.bradius.r;
        ary[0] = !hash[0] ? 1 : (hash[0] < 40) ? hash[0] + 4 : hash[0];
        ary[1] = !hash[1] ? 1 : (hash[1] < 40) ? hash[1] + 4 : hash[1];
        ary[2] = !hash[2] ? 1 : (hash[2] < 40) ? hash[2] + 4 : hash[2];
        ary[3] = !hash[3] ? 1 : (hash[3] < 40) ? hash[3] + 4 : hash[3];
      } else {
        ary = prop.bradius.r;
      }
      vctx.save();
      vctx.fillStyle = prop.border.tc[0];
      boxpath(vctx,
              prop.nodeOffset.x,
              prop.nodeOffset.y,
              prop.nodeRect.ow,
              prop.nodeRect.oh,
              ary);
      vctx.fill();
      vctx.restore();
    }
/* keep
    if (0) { // end clip
      vctx.restore();
    }
 */
  }

  if (nodebg) {
    layer.push("nodebg");

    // draw background-color
    if (prop.border.render ||
        prop.boxshadow.render ||
        prop.mbg.rgba.a) {
      nctx.save();
      if (!prop.mbg.rgba.r &&
          !prop.mbg.rgba.g &&
          !prop.mbg.rgba.b &&
          !prop.mbg.rgba.a) { // background-color: transparent
        nctx.globalAlpha = prop.mbg.altcolor.a;
        nctx.fillStyle = _color.hex(prop.mbg.altcolor);
      } else {
        nctx.globalAlpha = prop.mbg.rgba.a;
        nctx.fillStyle = _color.hex(prop.mbg.rgba);
      }
      boxpath(nctx,
              _ie67 ? prop.border.l : 0,
              _ie67 ? prop.border.t : 0,
              prop.nodeRect.ow - prop.border.l - prop.border.r,
              prop.nodeRect.oh - prop.border.t - prop.border.b,
              prop.bradius.r);
      nctx.fill();
      nctx.restore();
    }

    // draw multiple background image
    if (prop.mbg.m.length) {
      nctx.save();
      drawMultipleBackgroundImage(prop, nctx);
      nctx.restore();
    }
    layer.pop();
  }

  if (!redraw) {
    // bg setting
    node.style.backgroundColor = TRANSPARENT;
    node.style.backgroundImage = "none";
    node.style.borderColor = TRANSPARENT;
  }
  // http://d.hatena.ne.jp/uupaa/20090719
  // IE6 'borderColor = "transparent";' unsupported
  if (_ie67) {
    _mm.mix(node.style, prop.ie6borderfix);
  }

  if (!redraw && prop.slmode) {
    _deny = 1;
  }
}

function parseBorderRadiusParam(ns, prop) {
  // -webkit-border-radius: <radius>{1,4} [/ <radius>{1,4}]
  // -webkit-border-radius: top-left, top-right, bottom-right, bottom-left
  // -webkit-border-radius: top-left, top-right, [top-left], [top-right]
  // -webkit-border-top-left-radius: <h_radius> [<v_radius>]
  // -webkit-border-top-right-radius: <h_radius> [<v_radius>]
  // -webkit-border-bottom-left-radius: <h_radius> [<v_radius>]
  // -webkit-border-bottom-right-radius: <h_radius> [<v_radius>]
  var REX = /-webkit-border((?:-top|-bottom)(?:-left|-right))?-radius/g,
      POS = { "-top-left": 0, "-top-right": 1,
              "-bottom-right": 2, "-bottom-left": 3 },
      r = [0, 0, 0, 0], v, m;

  while ( (m = REX.exec(prop.order)) ) {
    if (m[1] in POS) {
      r[POS[m[1]]] = prop.css[m[0]].replace(/\s.*$/, ""); // ignore v_radius
    } else {
      v = prop.css[m[0]].replace(/\s*\/.*$/, "").split(/\s/);
      switch (v.length) {
      case 1: r[0] = r[1] = r[2] = r[3] = v[0]; break;
      case 2: r = v; r[3] = r[1]; r[2] = r[0]; break;
      case 3: r = v; r[3] = r[1]; break; // bottom-left = top-right
      case 4: r = v;
      }
    }
  }
  if (r[0] || r[1] || r[2] || r[3]) {
    prop.bradius.render = 1;
    prop.bradius.r = [ _style.toPixel(prop.node, r[0]),
                       _style.toPixel(prop.node, r[1]),
                       _style.toPixel(prop.node, r[2]),
                       _style.toPixel(prop.node, r[3]) ];
  }
}

function parseBoxShadowParam(ns, prop) {
  // parse: box-shadow: <color> || <offsetX> <offsetY> <blur>
  var ary, color, ox, oy, blur, hash = prop.boxshadow,
      elm = prop.node,
      key = prop.css["-webkit-box-shadow"],
      topx = _style.toPixel;

  // parse -webkit-box-shadow:
  //    "rgba( 0, 0, 0, 0 )  0px  0px  0px"
  //        v
  //    ["rgba(0,0,0,0)", "0px", "0px", "0px"]
  if (key) {
    ary = key.replace(/\(\s*/, "(").replace(/\s*\)/, ")").
              replace(/\s*,\s*/g, ",").split(/\s+/);

    color = /^\d/.test(ary[0]) ? ary.pop() : ary.shift();
    ox    = ary.shift() || 0;
    oy    = ary.shift() || 0;
    blur  = ary.shift() || 0;

    hash.render = 1;
    hash.rgba = _color.parse(color, 1); // { r, g, b, a }
    hash.ox   = topx(elm, ox);    // shadow offset x
    hash.oy   = topx(elm, oy);    // shadow offset y
    hash.blur = topx(elm, blur);  // shadow blur
  }
}

function parseBoxReflectParam(ns, prop) {
  // -webkit-box-reflect: <direction> [<offset>] [<mask-box-image>]
  // <direction> ::= "above" / "below" / "left" / "right"
  // <offset> ::= length
  // <mask-box-image> ::= -webkit-gradient() or url()
  var ary, dir, off, mask, match, url, img, grad,
      hash = prop.boxreflect,
      key = prop.css["-webkit-box-reflect"];

  if (key) {
    ary = key.split(/\s+/);

    dir  = ary.shift();
    off  = ary.shift() || 0;
    mask = ary.length ? ary.join(" ").replace(TRIM, "") : void 0;

    if (mask) {
      if ( (match = /^\s*url\((.*)\)$/.exec(mask)) ) {
        url = match[1].replace(/^\s*[\"\']?|[\"\']?\s*$/g, "");

        img = new Image();
        img.state = 0; // bond
        img.onload = function() {
          if (img.complete ||
              img.readyState === "complete") { // IE8
            img.state = 2;
            setTimeout(function() {
              boxeffectRecalcRect(prop.node, prop);
              boxeffectDraw(prop, 1)
            }, 0);
          }
        };
        img.setAttribute("src", url);
      } else {
        grad = parseGradientParam(prop, mask);
      }
    }

    hash.render = 1;
    hash.dir    = dir;
    hash.offset = _style.toPixel(prop.node, off);
    hash.url    = url;
    hash.img    = img;
    hash.grad   = grad;
  }
}

function parseGradientParam(prop, propValue) {
  // -webkit-gradient(<type>, <point> [, <radius>]?,
  //                  <point> [, <radius>]? [, <stop>]*)

  function getPos(m, lt, rb, num, per) {
    var size = (pos.length & 1) ? prop.nodeRect.h
                                : prop.nodeRect.w;
    pos.push(lt ? 0 :
             rb ? size :
             per ? (size * _float(num)) : _float(num));
    return "";
  }
  function getRadius(m, a1) {
    radius.push(_float(a1));
    return "";
  }

  var type = 0, pos = [], radius = [],
      match = /^\s*-webkit-gradient\((.*)\)$/.exec(propValue),
      offset = [], color = [], expr,
      GRAD_TYPE = /^(?:(linear)|radial)\s*,?/,
      GRAD_POS1 = /(?:(left)|(right)|([\d\.]+)(%)?)\s+/,
      GRAD_POS2 = /(?:(top)|(bottom)|([\d\.]+)(%)?)\s*,?/,
      GRAD_RADIUS = /([\d\.]+)\s*,/,
      GRAD_FROM = /from\(\s*(rgba?\([^\)]+\)|#?[\w]+)\s*\)/,
      GRAD_TO   = /to\(\s*(rgba?\([^\)]+\)|#?[\w]+)\s*\)/,
      GRAD_STOP =
          /color-stop\(\s*([\d\.]+)\s*,\s*(rgba?\([^\)]+\)|#?[\w]+)\s*\)/g;

  if (match) {
    expr = match[1].replace(GRAD_TYPE, function(m, l) {
      type = l ? 1 : 2;
      return "";
    }).replace(GRAD_POS1, getPos).
       replace(GRAD_POS2, getPos);

    if (type === 2) { // radial
      expr = expr.replace(GRAD_RADIUS, getRadius);
    }

    expr = expr.replace(GRAD_POS1, getPos).
                replace(GRAD_POS2, getPos);

    if (type === 2) {
      expr = expr.replace(GRAD_RADIUS, getRadius);
    }

    expr.replace(TRIM, "").
      replace(GRAD_FROM, function(m, c) {
        offset.push(0);
        color.push(c); return "";
      }).
      replace(GRAD_TO,   function(m, c) {
        offset.push(1);
        color.push(c); return "";
      }).
      replace(GRAD_STOP, function(m, p, c) {
        offset.push(_float(p));
        color.push(c); return "";
      });
  }
  return {
    render: !type ? 0 : 1,
    type:   type,   // 0: none, 1: linear, 2: radial
    pos:    pos,    // [pos1x, pos1y, pos2x, pos2y]
    r:      radius, // [radius1, radius2]
    offset: offset, // [0, ...]
    color:  color   // [{ r, g, b, a}, ... ]
  };
}

function drawFakeShadow(ctx, x, y, width, height,
                        rgba, blur, radius) {
  var i = 0, j = 0, k, step = 1, line = 5, r = radius,
      fg = "rgba(" + [rgba.r, rgba.g, rgba.b, ""].join(","); // fragment

  if (blur > 30 || (_ie6 && !_mm.slver)) { // IE6 + VML - cut short
    step *= 2, line *= 2;
  }
  ctx.globalAlpha = 1;
  ctx.lineWidth = line;
  for (; i < blur; i += step) {
    k = i / blur;
    j += 0.5;
    ctx.strokeStyle = fg + (k * k * k) + ")";
    boxpath(ctx, x + i, y + i, width - (i * 2), height - (i * 2),
            [r[0] - j, r[1] - j, r[2] - j, r[3] - j]);
    ctx.stroke();
  }
}

function drawMultipleBackgroundImage(prop, ctx) {
  var img, i = 0, iz = prop.mbg.m.length, draw = 0, pos = [];

  for (; i < iz; ++i) {
    switch (prop.mbg.type[i]) {
    case 1: // image
      img = prop.mbg.img[i];
      if (img.state === 2) {
        pos[i] = parseBackgroundPosition(prop, prop.mbg.p[i], img);
        ++draw;
      }
      break;
    case 2: // gradient
      if (prop.mbg.grad[i].render) {
        ++draw;
      }
    }
  }

  if (draw) {
    if (!_ie || prop.slmode) {
      boxpath(ctx,
              _ie67 ? prop.border.l : 0,
              _ie67 ? prop.border.t : 0,
              prop.nodeRect.ow - prop.border.l - prop.border.r,
              prop.nodeRect.oh - prop.border.t - prop.border.b,
              prop.bradius.r);
      ctx.clip();
    }
    while (i--) {
      switch (prop.mbg.type[i]) {
      case 1:
        img = prop.mbg.img[i];
        if (img.state === 2) {
          switch (prop.mbg.r[i]) {
          case "no-repeat":
            // http://twitter.com/uupaa/status/2763996863
            // Firefox2 bugfix
            ctx.drawImage(img, pos[i].x | 0, pos[i].y | 0);
            break;
          case "repeat-x":
          case "repeat-y":
            drawImageTile(prop, ctx, img,
                          (prop.mbg.r[i] === "repeat-x" ? 1 : 0),
                          pos[i].x | 0, pos[i].y | 0,
                          _ie67 ? prop.border.l : 0,
                          _ie67 ? prop.border.t : 0,
                          prop.nodeRect.ow - prop.border.l - prop.border.r,
                          prop.nodeRect.oh - prop.border.t - prop.border.b);
            break;
          case "repeat":
          default:
            ctx.save();
            ctx.fillStyle = ctx.createPattern(img, "repeat");
            boxpath(ctx,
                    _ie67 ? prop.border.l : 0,
                    _ie67 ? prop.border.t : 0,
                    prop.nodeRect.ow - prop.border.l - prop.border.r,
                    prop.nodeRect.oh - prop.border.t - prop.border.b,
                    prop.bradius.r);
            ctx.fill();
            ctx.restore();
            break;
          }
        }
        break;
      case 2:
        if (prop.mbg.grad[i].render) {
          drawGradient(prop, ctx, prop.mbg.grad[i]);
        }
      }
    }
  }
}

function drawImageTile(prop, ctx, img, horizontal,
                       ix, iy, left, top, right, bottom) {
  var x = ix, y = iy, w = img.width, h = img.height,
      xmin = left - w, ymin = top - h;

  if (horizontal) {
    for (x = ix; x < right; x += w) {
      ctx.drawImage(img, x, y);
    }
    for (x = ix - w; x > xmin; x -= w) {
      ctx.drawImage(img, x, y);
    }
  } else {
    for (y = iy; y < bottom; y += h) {
      ctx.drawImage(img, x, y);
    }
    for (y = iy - h; y > ymin; y -= h) {
      ctx.drawImage(img, x, y);
    }
  }
}

function drawGradient(prop, ctx, hash) {
  ctx.save();
  ctx.fillStyle = (hash.type === 1)
      ? prop.layer.linearGrad(hash.pos[0], hash.pos[1],
                              hash.pos[2], hash.pos[3],
                              hash.offset, hash.color)
      : prop.layer.radialGrad(hash.pos[0], hash.pos[1],
                              hash.r[0],
                              hash.pos[2], hash.pos[3],
                              hash.r[1],
                              hash.offset, hash.color);
  boxpath(ctx,
          _ie67 ? prop.border.l : 0,
          _ie67 ? prop.border.t : 0,
          prop.nodeRect.ow - prop.border.l - prop.border.r,
          prop.nodeRect.oh - prop.border.t - prop.border.b,
          prop.bradius.r);
  ctx.fill();
  ctx.restore();
}

function detectMultipleBackgroundImage(ns, prop) {
  function split(key) {
    return (key.indexOf(",") < 0) ? [key] : splitToken(key);
  }
  var REX = /background(-image|-repeat|-position|-color|-attachment|-clip|-origin|-size|-break)?/g,
      css = prop.css,
      hash = prop.mbg,
      i = 0, iz, m, url, N;

  while ( (m = REX.exec(prop.order)) ) {
    switch (m[1] || "shorthand") {
    case "shorthand":
      parseMultipleBackground(css["background"], hash);
      break;
    case "-image":
      hash.m = split(css["background-image"]);
      break;
    case "-repeat":
      hash.r = css["background-repeat"].split(",");
      break;
    case "-position":
      hash.p = css["background-position"].split(",");
      break;
    case "-color":
      hash.rgba = _color.parse(css["background-color"], 1);
    }
  }

  // spec http://www.w3.org/TR/css3-background/#layering
  N = Math.max(hash.m.length, hash.r.length, hash.p.length);

  if (N > hash.m.length) {
    hash.m = multipleArray(hash.m, Math.ceil(N / hash.m.length), N);
  }
  if (N > hash.r.length) {
    hash.r = multipleArray(hash.r, Math.ceil(N / hash.r.length), N);
  }
  if (N > hash.p.length) {
    hash.p = multipleArray(hash.p, Math.ceil(N / hash.p.length), N);
  }

  for (iz = hash.m.length; i < iz; ++i) {
    hash.m[i] = hash.m[i].replace(TRIM, ""); // trim both
    hash.r[i] = hash.r[i].replace(TRIM, "");
    hash.p[i] = hash.p[i].replace(TRIM, "");
    hash.type[i] = 0; // unknown

    if ( (m = /^\s*url\((.*)\)$/.exec(hash.m[i])) ) {
      hash.type[i] = 1; // image
      url = m[1].replace(/^\s*[\"\']?|[\"\']?\s*$/g, "");
    } else if (/^\s*-webkit-gradient/.test(hash.m[i])) {
      hash.type[i] = 2; // gradient
      hash.grad[i] = parseGradientParam(prop, hash.m[i]);
      continue;
    } else {
      continue;
    }
    hash.img[i] = new Image();
    hash.img[i].state = 0; // bond
    // lazy load
    (function(img, url) {
      img.onerror = function() {
        img.state = 1;
      };
      img.onload = function() {
        if (img.complete ||
            img.readyState === "complete") { // IE8
          img.state = 2;
          if (prop.mbg.tid >= 0) {
            clearTimeout(prop.mbg.tid);
          }
          prop.mbg.tid = setTimeout(function() {
            boxeffectRecalcRect(prop.node, prop);
            boxeffectDraw(prop, 1)
            prop.mbg.tid = -1;
          }, 100);
        }
      };
      img.setAttribute("src", url);
    })(hash.img[i], url);
  }
}

function parseMultipleBackground(value, hash) {
  var URA = /^(?:none|(url\(.*?\))|(repeat|no-repeat|repeat-x|repeat-y)|(scroll|fixed))$/,
      LEN = /^([\d\.]+(%|px|em|pt|cm|mm|in|pc|px)|left|center|right|top|bottom|0)$/,
      multi = splitToken(value), m, v, w, i = 0, j, jz, ary,
      u, r, x, y, c = hash.rgba;

  while ( (v = multi[i++]) ) {
    u = r = x = y = "";
    ary = v.replace(TRIM, "").split(/\s+/);

    for (j = 0, jz = ary.length; j < jz; ++j) {
      w = ary[j];
      if ( (m = URA.exec(w)) ) {
        if (m[1]) { u = m[1]; } // url
        if (m[2]) { r = m[2]; } // repeat
      } else if ( (m = LEN.exec(w)) ) {
        x ? (y = m[1])  // posy
          : (x = m[1]); // posx
      } else if (_color.isColor(w)) {
        c = _color.parse(w, 1); // rgba
      }
    }
    hash.m.push(u || "");
    hash.r.push(r || "repeat");
    hash.p.push((x || "0%") + " " + (y || "0%"));
  }
  hash.rgba = c; // color
}

function parseBackgroundPosition(prop, pos, img) {
  var key1 = { left: "0%", center: "50%", right: "100%" },
      key2 = { top: "0%", center: "50%", bottom: "100%" },
      ary, px, py,
      nw = prop.nodeRect.w,
      nh = prop.nodeRect.h,
      iw = img.width,
      ih = img.height;

  ary = (pos.indexOf(" ") > 0) ? pos.split(" ")
                               : [pos, pos];

  if (ary[0] === "top" || ary[0] === "bottom" ||
      ary[1] === "left" || ary[1] === "right") {
    ary.reverse(); // "top left" -> "left top"
  }

  ary[0] = key1[ary[0]] || ary[0];
  ary[1] = key2[ary[1]] || ary[1];

  if (ary[0].lastIndexOf("%") > 0) {
    px = nw * _float(ary[0]) / 100
       - iw * _float(ary[0]) / 100;
  } else {
    px = _style.toPixel(prop.node, ary[0]);
  }

  if (ary[1].lastIndexOf("%") > 0) {
    py = nh * _float(ary[1]) / 100
       - ih * _float(ary[1]) / 100;
  } else {
    py = _style.toPixel(prop.node, ary[1]);
  }
  return { x: px, y: py };
}

function boxpath(ctx, x, y, w, h, rary, openPath) {
  var r0 = rary[0], r1 = rary[1], r2 = rary[2], r3 = rary[3],
      w2 = (w / 2) | 0, h2 = (h / 2) | 0;

  if (r0 < 0) { r0 = 0; }
  if (r1 < 0) { r1 = 0; }
  if (r2 < 0) { r2 = 0; }
  if (r3 < 0) { r3 = 0; }
  if (r0 >= w2 || r0 >= h2) { r0 = Math.min(w2, h2) - 2; }
  if (r1 >= w2 || r1 >= h2) { r1 = Math.min(w2, h2) - 2; }
  if (r2 >= w2 || r2 >= h2) { r2 = Math.min(w2, h2) - 2; }
  if (r3 >= w2 || r3 >= h2) { r3 = Math.min(w2, h2) - 2; }

  if (!openPath) {
    ctx.beginPath();
  }
  ctx.moveTo(x, y + h2);
  ctx.lineTo(x, y + h - r3);
  ctx.quadraticCurveTo(x, y + h, x + r3, y + h); // bottom-left
  ctx.lineTo(x + w - r2, y + h);
  ctx.quadraticCurveTo(x + w, y + h, x + w, y + h - r2); // bottom-right
  ctx.lineTo(x + w, y + r1);
  ctx.quadraticCurveTo(x + w, y, x + w - r1, y); // top-left
  ctx.lineTo(x + r0, y);
  ctx.quadraticCurveTo(x, y, x, y + r0); // top-right
  ctx.closePath();
}

function getOffsetFromAncestor(elm, ancestor) {
  var rv, e, x = 0, y = 0,
      mem = ancestor.style.position;

  ancestor.style.position = "relative"; // overwrite

  e = elm;
  while (e && e !== ancestor) {
    x += e.offsetLeft || 0;
    y += e.offsetTop  || 0;
    e  = e.offsetParent;
  }
  rv = { x: x, y: y };

  ancestor.style.position = mem; // restore
  return rv;
}

// "url(...), -webkit-gradient(...), ..."
function splitToken(expr) {
  var rv = [], ary = expr.split(""),
      v, i = 0, iz = ary.length,
      nest = 0, tmp = [], ti = -1,
      token = { "(": 1, ")": 2, ",": 3 };

  for (; i < iz; ++i) {
    v = ary[i];

    switch (token[v] || 0) {
    case 1: tmp[++ti] = v; ++nest; break;
    case 2: tmp[++ti] = v; --nest; break;
    case 3: nest ? (tmp[++ti] = v)
                 : (rv.push(tmp.join("")), tmp = [], ti = -1); break;
    default:tmp[++ti] = v; break;
    }
  }
  // remain
  tmp.length && rv.push(tmp.join(""));
  return rv;
}

function multipleArray(ary, times, maxLength) {
  var rv = [], i = 0, iz;

  for (; i < times; ++i) {
    rv = rv.concat(ary);
  }
  if (rv.length > maxLength) {
    for (i = 0, iz = rv.length - maxLength; i < iz; ++i) {
      rv.pop();
    }
  }
  return rv;
}

// --- initialize ---

// --- export ---
_win.uuAltCSSPlus = _altcssplus;
_altcssplus.find = find;
_altcssplus.deny = function() { return _deny; };
_altcssplus.redraw = redraw;

})(); // uuAltCSSPlus scope
