/** uupaa-cssselector.js
 *
 * uupaa-cssselector.js is Fastest CSS3 Selector
 *  - uupaa.js spin-off project
 *
 * Functional Limit:
 * - Do not support :link :visited :not :before :after :first-letter :first-line
 *
 * @author Takao Obara <com.gmail@js.uupaa>
 * @license uupaa-datascheme.js is licensed under the terms and conditions of the MIT licence.
 * @version 1.0
 * @date 2008-10-03
 * @see <a href="http://code.google.com/p/uupaa-js/">uupaa.js Home(Google Code)</a>
 * @see <a href="http://code.google.com/p/uupaa-js-spinoff/">uupaa.js SpinOff Project Home(Google Code)</a>
 */

(function() {
// === Core ==========================
var UU = { VERSION: 1 }, // [release]
    uu = {};

uu.mix = function(base, flavor, aroma) {
  for(var i in flavor) {
    base[i] = flavor[i];
  }
  return aroma ? uu.mix(base, aroma) : base;
};
uu.mix(UU, {
  CSS_QUICK:    /^(\.|#)([0-9a-z\-_]+)$/i,      // .class or #id
  CSS_ID:       /^#([a-z0-9\_\-]+)/i,           // #id
  CSS_CLASS:    /^\.([a-z0-9\_\-]+)/i,          // .class
  CSS_ATTR1:    /^\[\s*([^\~\^\$\*\|\=\!\s]+)\s*([\~\^\$\*\|\!]?=)\s*([^\]]*)\s*\]/, // [A=V]
  CSS_ATTR2:    /^\[\s*([^\]]*)\s*\]/,          // [A]
  CSS_QUOTE:    /^[\"\']?|[\"\']?$/g,           // "..." or '...'
  CSS_PSEUDO:   /^\:([\w\-]+)(?:\((.*)\))?/,    // :nth-child(an+b)
  CSS_ANB:      /^(-?\d*)n((?:\+|-)?\d*)/,      // an+b
  CSS_COMBO1:   /^\s*([\>\+\~])\s*(\*|\w*)/,    // E>F   E+F   E~F
  CSS_COMBO2:   /^\s*(\*|\w*)/,                 // E F
  CSS_GROUP:    /^\s*,\s*/,                     // E,F
  CSS_GUARD:    { title: 0, id: 0, name: 0, "for": 0 },
  CSS_NG_ATTR:  { "class": "className", htmlFor: "for" },
  CSS_OP:       { "=":  function(v, a) { return v === a; },
                  "!=": function(v, a) { return v !== a; },
                  "*=": function(v, a) { return a.indexOf(v) !== -1; },
                  "^=": function(v, a) { return a.indexOf(v) === 0; },
                  "$=": function(v, a) { return a.lastIndexOf(v) + v.length === a.length; },
                  "~=": function(v, a) { return (" " + a + " ").indexOf(v) !== -1; },
                  "|=": function(v, a) { return v === a || a.substring(0, v.length + 1) === v + "-"; }
                }
});

uu.ua = {};
uu.ua._        = navigator.userAgent;            // UserAgent cache
uu.ua.opera    = !!window.opera;                 // is Opera
uu.ua.ie       = !!document.uniqueID;            // is Internet Explorer
uu.ua.webkit   = uu.ua._.indexOf("WebKit") >= 0; // is WebKit(Safari, Konqueror, Chrome)
uu.ua.version  = uu.ua.ie     ? parseFloat(uu.ua._.match(/MSIE ([\d]\.[\d][\w]?)/)[1])  // 5.5, 6, 7, 8(IE Major version)
               : uu.ua.gecko  ? parseFloat(uu.ua._.match(/Gecko\/(\d{8})/)[1])          // 20080404(Gecko Build Number)
               : uu.ua.webkit ? parseFloat(uu.ua._.match(/WebKit\/(\d+(?:\.\d+)*)/)[1]) // 525.13(Webkit Build Number)
               : uu.ua.opera  ? opera.version() : 0;                              // 10048(Opera Build Number)
uu.ua.ie8      = uu.ua.ie && uu.ua.version === 8; // is Internet Explorer Version 8
uu.ua.opera95  = uu.ua.opera && parseInt(uu.ua.version) >= 10048; // is Opera Version 9.5+

uu.mix(uu, {
  css:      function(expr, context /* = document */) {
              var x = expr.replace(/^[\s]*|[\s]*$/g, ""), cx = context || document;
              try {
                return (!cx.querySelector || x.indexOf(":contains") > -1)
                       ? uu._css(x, cx) : Array.prototype.slice.call(cx.querySelectorAll(x));
              } catch (e) { (uu.config.debug & 0x1) && uu.die("uu.css(expr=%s)", expr); }
              return [];
            },
  _css:     function(expr, context) {
              var doc = document;
              var rv = [], cx = [context || doc],
                  lastX = "", lastXX = "", x = expr, m, r, w, v;
              var E, F, uid;
              var ii, jj, iz, jz, node, m1, m2, m3, f1, f2, f3, cn, pn, ri, gd = {}, ggd = {};
              var mixed = 0;
              var uidCounter = uu.uid; // alias uu.uid._count
              var ieLike = uu.ua.ie || (uu.ua.opera && !uu.ua.opera95);
              function NTH(anb) {
                var m, a, b;
                if (!isNaN(anb)) { b = parseInt(anb); return  function(i) { return i === b; } }
                if (anb === "even") { return  function(i) { return ((i - 0) % 2 === 0) && ((i - 0) / 2 >= 0); }; }
                if (anb === "odd" ) { return  function(i) { return ((i - 1) % 2 === 0) && ((i - 1) / 2 >= 0); }; }
                m = anb.match(UU.CSS_ANB);
                !m && uu.die("%s unsupported", anb);
                a = parseInt(m[1] === "-" ? -1 : m[1] || 1);
                b = parseInt(m[2] || 0);
                switch (a) {
                case  0: return function(i) { return i === b; };
                case  1: return function(i) { return i >= b;  };
                case -1: return function(i) { return i <= b;  };
                }
                return function(i) { return ((i - b) % a === 0) && ((i - b) / a >= 0); }; // an+b
              }

              if ( (m = x.match(UU.CSS_QUICK)) ) {
                if (m[1] === "#") {
                  return [doc.getElementById(m[2])];
                }
                f1 = " " + m[2] + " ";
                node = cx[0].getElementsByTagName("*");
                for (gd = {}, ri = -1, ii = 0, iz = node.length; ii < iz; ++ii) {
                  cn = node[ii];
                  if (cn.nodeType === 1 && (" " + cn.className + " ").indexOf(f1) > -1) {
                    uid = "uid" in cn ? cn.uid : (cn.uid = ++uidCounter._count);
                    if (!(uid in gd)) { rv[++ri] = cn, gd[uid] = true; }
                  }
                }
                return rv;
              }

              while (x.length && x !== lastX) {
                lastX = x, m = null;

                // "E > F"  "E + F"  "E ~ F" phase
                if ( (m = x.match(UU.CSS_COMBO1)) ) {
                  F = m[2] ? m[2].toUpperCase() : "*";
                  f1 = F === "*";
                  r = [], ri = -1, gd = {}, ii = 0, iz = cx.length;
                  switch (m[1]) {
                  case ">": // "E > F"
                            for (; ii < iz; ++ii) {
                              for (cn = cx[ii].firstChild; cn; cn = cn.nextSibling) {
                                if (cn.nodeType === 1) {
                                  if (f1 || cn.tagName.toUpperCase() === F) {
                                    uid = "uid" in cn ? cn.uid : (cn.uid = ++uidCounter._count);
                                    if (!(uid in gd)) { r[++ri] = cn, gd[uid] = true; }
                                  }
                                }
                              }
                            }
                            cx = r;
                            break;
                  case "+": // "E + F"
                            for (; ii < iz; ++ii) {
                              for (cn = cx[ii].nextSibling; cn; cn = cn.nextSibling) {
                                if (cn.nodeType === 1) {
                                  if (f1 || cn.tagName.toUpperCase() === F) { r[++ri] = cn; }
                                  break;
                                }
                              }
                            }
                            cx = r;
                            break;
                  case "~": // "E ~ F"
                            for (; ii < iz; ++ii) {
                              for (cn = cx[ii].nextSibling; cn; cn = cn.nextSibling) {
                                if (cn.nodeType === 1) {
                                  if (f1 || cn.tagName.toUpperCase() === F) {
                                    uid = "uid" in cn ? cn.uid : (cn.uid = ++uidCounter._count);
                                    if (uid in gd) { break; }
                                    r[++ri] = cn;
                                    gd[uid] = true;
                                  }
                                }
                              }
                            }
                            cx = r;
                            break;
                  }
                } else if ( (m = x.match(UU.CSS_COMBO2)) ) {
                  // "E" or "*" phase
                  E = m[1].toUpperCase() || "*";
                  f1 = E === "*";
                  for (r = [], ri = -1, gd = {}, ii = 0, iz = cx.length; ii < iz; ++ii) {
                    for (node = cx[ii].getElementsByTagName(E), jj = 0, jz = node.length; jj < jz; ++jj) {
                      cn = node[jj];
                      if (cn.nodeType === 1) {
                        if (f1 || cn.tagName.toUpperCase() === E) {
                          uid = "uid" in cn ? cn.uid : (cn.uid = ++uidCounter._count);
                          if (!(uid in gd)) { r[++ri] = cn, gd[uid] = true; }
                        }
                      }
                    }
                  }
                  cx = r;
                }
                m && (x = x.slice(m[0].length));


                while (x.length && x !== lastXX) {
                  lastXX = x, m = null;

                  switch (x.charAt(0)) {
                  case "#": if ( (m = x.match(UU.CSS_ID)) ) { // m[1] = id
                              m1 = m[1];
                              v = doc.getElementById(m1);
                              v && (cx = [v]);
                            }
                            break;
                  case ".": if ( (m = x.match(UU.CSS_CLASS)) ) {
                              for (m1 = " " + m[1] + " ", r = [], ri = -1, ii = 0, iz = cx.length; ii < iz; ++ii) {
                                v = cx[ii];
                                if ((" " + v.className + " ").indexOf(m1) > -1) {
                                  r[++ri] = v;
                                }
                              }
                              cx = r;
                            }
                            break;
                  case "[": if ( (m = x.match(UU.CSS_ATTR1)) ) {
                              m1 = m[1], m2 = m[2], m3 = m[3];
                              if (uu.ua.ie && m1 in UU.CSS_NG_ATTR) {
                                m1 = UU.CSS_NG_ATTR[m1];
                              }
                              f1 = m1 in UU.CSS_GUARD;
                              v = m3.replace(UU.CSS_QUOTE, "");
                              if (!f1) {
                                v = v.toLowerCase(); 
                              }
                              if (m2 === "~=") {
                                v = " " + v + " ";
                              }
                              for (r = [], ri = -1, ii = 0, iz = cx.length; ii < iz; ++ii) {
                                w = cx[ii].getAttribute(m1);
                                if (w && UU.CSS_OP[m2](v, f1 ? w : w.toLowerCase())) {
                                  r[++ri] = cx[ii];
                                }
                              }
                              cx = r;
                            } else if ( (m = x.match(UU.CSS_ATTR2)) ) { // m[1] = "A"
                              f1 = uu.ua.ie && !uu.ua.ie8;
                              for (r = [], ri = -1, ii = 0, iz = cx.length; ii < iz; ++ii) {
                                v = cx[ii];
                                if (f1) {
                                  node = v.getAttributeNode(m[1]);
                                  if (node && node.specified) {
                                    r[++ri] = v;
                                  }
                                } else if (v.hasAttribute(m[1])) {
                                  r[++ri] = v;
                                }
                              }
                              cx = r;
                            }
                            break;
                  case ":": if ( (m = x.match(UU.CSS_PSEUDO)) ) {
                              m1 = m[1], m2 = m[2];
                              r = [], ri = -1, ii = 0, iz = cx.length;
                              switch (m1) {
                              case "root":
                                r = [doc.getElementsByTagName("html")[0]];
                                break;
                              case "enabled":
                                f1 = function(v) { return !v.disabled; };
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  f1(v) && (r[++ri] = v);
                                }
                                break;
                              case "disabled":
                                f1 = function(v) { return v.disabled; };
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  f1(v) && (r[++ri] = v);
                                }
                                break;
                              case "checked":
                                f1 = function(v) { return v.checked; };
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  f1(v) && (r[++ri] = v);
                                }
                                break;
                              case "contains":
                                f1 = m2.replace(UU.CSS_QUOTE, "");
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  ((ieLike ? v.innerText : v.textContent).indexOf(f1) > -1) && (r[++ri] = v);
                                }
                                break;
                              case "empty":
                                f1 = 0;
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  for (cn = v.firstChild; cn; cn = cn.nextSibling) {
                                    if (cn.nodeType === 1) { ++f1; break; }
                                  }
                                  (!f1 && !(ieLike ? v.innerText : v.textContent)) && (r[++ri] = v);
                                }
                                break;
                              case "target":
                                if ( !(f1 = location.hash.slice(1)) ) { break; }
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  (v.id === f1 || ("name" in v && v.name === f1)) && (r[++ri] = v);
                                }
                                break;
                              case "lang":
                                f1 = RegExp("^(" + m2 + "$|" + m2 + "-)", "i");
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  while (v && v !== doc && !v.getAttribute("lang")) { v = v.parentNode; }
                                  ((v && v !== doc) && f1.test(v.getAttribute("lang"))) && (r[++ri] = cx[ii]);
                                }
                                break;
                              case "first-child":
                                for (; ii < iz; ++ii) {
                                  for (v = cx[ii], f1 = 0, cn = v.previousSibling; cn; cn = cn.previousSibling) {
                                    if (cn.nodeType === 1) { ++f1; break; }
                                  }
                                  !f1 && (r[++ri] = v);
                                }
                                break;
                              case "last-child":
                                for (; ii < iz; ++ii) {
                                  for (v = cx[ii], f1 = 0, cn = v.nextSibling; cn; cn = cn.nextSibling) {
                                    if (cn.nodeType === 1) { ++f1; break; }
                                  }
                                  !f1 && (r[++ri] = v);
                                }
                                break;
                              case "only-child":
                                for (; ii < iz; ++ii) {
                                  v = cx[ii], f1 = 0;
                                  for (cn = v.nextSibling; cn; cn = cn.nextSibling) {
                                    if (cn.nodeType === 1) { ++f1; break; }
                                  }
                                  if (!f1) {
                                    for (cn = v.previousSibling; cn; cn = cn.previousSibling) {
                                      if (cn.nodeType === 1) { ++f1; break; }
                                    }
                                  }
                                  !f1 && (r[++ri] = v);
                                }
                                break;
                              case "nth-child":
                                if (!cx.length) { break; }
                                f1 = NTH(m2), f2 = cx[0].tagName.toUpperCase(), gd = {}; // gd = { uid: pn }
                                for (; ii < iz; ++ii) {
                                  pn = cx[ii].parentNode;
                                  uid = "uid" in pn ? pn.uid : (pn.uid = ++uu.uid._count);
                                  if (!(uid in gd)) {
                                    gd[uid] = true;
                                    for (jj = 0, cn = pn.firstChild; cn; cn = cn.nextSibling) {
                                      if (cn.nodeType === 1 && f1(++jj) && cn.tagName.toUpperCase() === f2) {
                                        r[++ri] = cn;
                                      }
                                    }
                                  }
                                }
                                break;
                              case "nth-last-child":
                                if (!cx.length) { break; }
                                f1 = NTH(m2), f2 = cx[0].tagName.toUpperCase(), gd = {}; // gd = { uid: pn }
                                for (; ii < iz; ++ii) {
                                  pn = cx[ii].parentNode;
                                  uid = "uid" in pn ? pn.uid : (pn.uid = ++uu.uid._count);
                                  if (!(uid in gd)) {
                                    gd[uid] = true;
                                    for (jj = 0, cn = pn.lastChild; cn; cn = cn.previousSibling) {
                                      if (cn.nodeType === 1 && f1(++jj) && cn.tagName.toUpperCase() === f2) {
                                        r[++ri] = cn;
                                      }
                                    }
                                  }
                                }
                                break;
                              case "nth-last-of-type":
                                cx.reverse(); // break through
                              case "nth-of-type":
                                f1 = NTH(m2), f2 = null, f3 = {};
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  pn = v.parentNode;
                                  (f2 !== pn) && (f2 = pn, f3 = {});
                                  (v.tagName in f3) ? ++f3[v.tagName] : (f3[v.tagName] = 1);
                                  f1(f3[v.tagName]) && (r[++ri] = v);
                                }
                                break;
                              case "last-of-type":
                                cx.reverse(); // break through
                              case "first-of-type":
                                f1 = function(i) { return i === 1; }, f2 = null, f3 = {};
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  pn = v.parentNode;
                                  (f2 !== pn) && (f2 = pn, f3 = {});
                                  (v.tagName in f3) ? ++f3[v.tagName] : (f3[v.tagName] = 1);
                                  f1(f3[v.tagName]) && (r[++ri] = v);
                                }
                                break;
                              case "only-of-type":
                                f1 = 0;
                                for (; ii < iz; ++ii) {
                                  v = cx[ii];
                                  f2 = v.tagName.toUpperCase();

                                  for (cn = v.nextSibling; cn; cn = cn.nextSibling) {
                                    if (cn.nodeType === 1 && cn.tagName.toUpperCase() === f2) { ++f1; break; }
                                  }
                                  if (!f1) {
                                    for (cn = v.previousSibling; cn; cn = cn.previousSibling) {
                                      if (cn.nodeType === 1 && cn.tagName.toUpperCase() === f2) { ++f1; break; }
                                    }
                                  }
                                  if (!f1) {
                                    r[++ri] = v;
                                  }
                                }
                                break;

                              default: uu.die(":%s unsupported", m1);
                              }
                              cx = r;
                            }
                  }
                  m && (x = x.slice(m[0].length));
                }
                if ( (m = x.match(UU.CSS_GROUP)) ) {
                  // mix
                  for (++mixed, ri = rv.length - 1, ii = 0, iz = cx.length; ii < iz; ++ii) {
                    v = cx[ii];
                    uid = "uid" in v ? v.uid : (v.uid = ++uu.uid._count);
                    !(uid in ggd) && (rv[++ri] = v, ggd[uid] = true);
                  }
                  cx = [context || doc], lastX = "", lastXX = "";
                  x = x.slice(m[0].length);
                }
              }
              x.length && uu.die("%s unsupported", x);
              if (!mixed) { return cx; }

              // mix
              for (ri = rv.length - 1, ii = 0, iz = cx.length; ii < iz; ++ii) {
                v = cx[ii];
                uid = "uid" in v ? v.uid : (v.uid = ++uu.uid._count);
                !(uid in ggd) && (rv[++ri] = v, ggd[uid] = true);
              }
              return rv;
            }
});
uu.die = function(fmt, p1, p2) { var err = fmt.replace("%s", p1); throw TypeError(err); }
uu.uid = { _count: 0 };
uu.config = { debug: 0 };

// export
if (!document.querySelectorAll) {
  document.getElementsBySelector = uu.css;
}

})(); // end (function())()

