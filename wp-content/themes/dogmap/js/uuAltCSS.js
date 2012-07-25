
// === uuAltCSS ===
// depend: uuMeta, uuColor, [uuCodec], uuStyleSheet, uuQuery,
//         uuAltCSSPlus
/*
window.UUALTCSS_FORCE_MARKUP = undefined; // 1: force markup, 0: markup off
window.UUALTCSS_IMAGE_DIR = ".";
window.UUALTCSS_DISABLE_CONDITIONAL_SELECTOR = 0;
window.UUALTCSS_VALUE_VALIDATION = 0;
window.UUALTCSS_STRIP_EMBED_IMAGE = 0;
window.UUALTCSS_STRIP_INLINE_WIDTH = 0;

uuAltCSS(rebuild = 0, css = "")
uuAltCSS.getRuleset() - return ruleset array
 */
(function() {
var _altcss, // inner namespace
    _validator, // inner namespace
    _mm = uuMeta,
    _mix = _mm.mix,
    _ss = uuStyleSheet,
    _query = uuQuery,
    _color = uuColor,
    _win = window,
    _doc = document,
    _xhr, // lazy
    _uaver = _mm.uaver,
    _egver = _mm.enginever,
    _ie = _mm.ie,
    _ie6 = _ie && _uaver === 6,
    _ie7 = _ie && _uaver === 7,
    _ie67 = _ie6 || _ie7,
    _opera = _mm.opera,
    _gecko = _mm.gecko,
    _webkit = _mm.webkit,
    _safari = _mm.safari,
    _chrome = _mm.chrome,
    _plus, // lazy detection
    _codec, // lazy detection
    _imagedir = _win.UUALTCSS_IMAGE_DIR || ".",
    _gif = _imagedir.replace(/\/+$/, "") + "/1dot.gif",
    // Value Validation
    _enableValidation = _win.UUALTCSS_VALUE_VALIDATION || 0,
    // IE6, IE7: Size of an inline element is invalidated.
    //    <span style="width:100px; height:100px">inline with size</span>
    //          v
    //    <span style="width:auto; height:auto">inline without size</span>
    _stripWidth = _ie67 && (_win.UUALTCSS_STRIP_INLINE_WIDTH || 0),
    // IE6, IE7: Strip embed image
    //    <img src="data:image/*,...">
    //    <div style="background: url(data:image/*,...)">
    //          v
    //    <img src="1dot.gif">
    //    <div style="background: url(1dot.gif)">
    _stripEmbedImage = _ie67 && (_win.UUALTCSS_STRIP_EMBED_IMAGE || 0),
    _enableDocumentFragment = !(_gecko && _egver <= 1.9), // exclude Fx2, Fx3
    _ruleset = [],
    _ruleuid = 0,   // unique rule id
    _rule = {}, // unique rule
    _ssid = "uuAltCSS", // StyleSheet ID
    _root, // root node( <html> )
    _init = 0, // init flag
    _markup = 0,
    _boostup = 0, // opacity, position:fixed
    _cache = {}, // import cache { url: cssText }
    _specs = [], // raw data
    _data = {}, // raw data
    _lazyClearClass = [], // lazy clear className nodeList
    SPEC_E = /\w+/g,
    SPEC_ID = /#[\w\u00C0-\uFFEE\-]+/g, // (
    SPEC_NOT = /:not\(([^\)]+)\)/,
    SPEC_ATTR =
        /\[\s*(?:([^~\^$*|=\s]+)\s*([~\^$*|]?\=)\s*(["'])?(.*?)\3|([^\]\s]+))\s*\]/g,
    SPEC_CLASS = /\.[\w\u00C0-\uFFEE\-]+/g,
    SPEC_PCLASS = /:[\w\-]+(?:\(.*\))?/g,
    SPEC_PELEMENT = /::[\w\-]+/g,
    SPEC_CONTAINS = /:contains\((["'])?.*?\1\)/g,
    TRIM = /^\s+|\s+$/g,
    MEMENTO = "uuAltCSSMemento",
    COMMENT = /\/\*[^*]*\*+([^\/][^*]*\*+)*\//g,
    IMPORTS =
        /@import\s*(?:url)?[\("']+\s*([\w\.\/\+\-]+)\s*["'\)]+\s*([\w]+)?\s*;/g,
    DATA_SCHEME = /^data\:/,
    RESET_DIV = "div{width:auto;height:auto;border:medium none black;" +
                "background:transparent none repeat scroll 0% 0%}",
    DETOXIFY_WIDTH = "width:auto;height:auto",
    // --- CSS Validator ---
    _validatorProps = {
      width: 1,
      background: 1
    },
    BACKGROUND_URL = /^none$|^url\(/i, // )
    BACKGROUND_REPEAT = /repeat/i,
    BACKGROUND_POSITION =
      /^(?:[\d\.]+(%|px|em|pt|cm|mm|in|pc|px)|left|center|right|top|bottom|0)$/i,
    BACKGROUND_ATTACHMENT = /^(?:scroll|fixed)/i;

_altcss =
    function(context, // @param Node/IDString(= void 0): revalidation context
             rebuild, // @param Number(= 0): 0 = OFF(quick), 1 = ON(full)
             css) {   // @param CSSString(= ""): CSS text
  // lazy revalidate for :target
  (_markup || _boostup) && setTimeout(function() {
    var tick = +new Date,
        ctx = (typeof context === "string") ? _query.id(context) : context;

    ctx = (!ctx || !ctx.parentNode || ctx === _doc) ? _doc
                                                    : ctx.parentNode;
    // --- begin perf block ---
        _altcss._unbond(ctx);
        if (rebuild) {
          _specs = [], _data = {}; // clear raw data
          _altcss._init(css);
        }
        _altcss._validate(ctx);
        _init = 1;
    // --- end perf block ---
    status = new Date - tick;
  }, 0);
};

_mm.mix(_altcss, {
  // uuAltCSS.getRuleset
  getRuleset: function() { // @return Array: rule-set
    return _ruleset;
  },

  // unbond attrs
  _unbond: function(context) { // @param Node:
    var node, v, i, j;

    // remove class="uucss{n} ..."
    //   1. replace element.className
    //   2. remove element.uuCSSC attr
    _lazyClearClass = _query("[uuCSSC]", context);

    // remove "!important" style
    //   1. collect old style from element.uuCSSIHash
    //   2. remove element.uuCSSI attr
    if (!_ie) {
      node = _query("[uuCSSI]", context), i = 0;
      while ( (v = node[i++]) ) {
        for (j in v.uuCSSIHash) {
          v.style.removeProperty(j);
          v.style.setProperty(j, v.uuCSSIHash[j], "");
        }
        v.removeAttribute("uuCSSI"); // unmarkup
      }
    }

    // remove uuCSSHover="uucsshover{n} ..."
    //   1. remove element.uuCSSHover attr
    node = _query("[uuCSSHover]", context), i = 0;
    while ( (v = node[i++]) ) {
      v.removeAttribute("uuCSSHover");
    }
  },

  _init: function(css) {
    var node, v, i;

    // memnto
    if (_ie) {
      node = _query.tag("style"), i = 0;
      while ( (v = node[i++]) ) {
        !(MEMENTO in v) && (v[MEMENTO] = v.innerHTML);
      }
    }

    // collect style sheets
    css = _altcss._cleanup(css || _altcss._imports());

    // http://d.hatena.ne.jp/uupaa/20090619/1245348504
    if (!_init && _ie6) {
      v = _win.name;
      _win.name = ""; // clear
      if (/UNKNOWN[^\{]+?\{|:unknown[^\{]+?\{/.test(css) && // }}}}
          "UNKNOWN" !== v) {
        _win.name = "UNKNOWN";
        location.reload(false);
        return false;
      }
    }
    if (_ie) {
      css = RESET_DIV + css;
    } else {
      ++_ruleuid;
    }

    // strip embed background-image
    if (!_init && _stripEmbedImage) {
      // <div style="background: url(data...)"> // (
      css = css.replace(/url\(\"data\:[^\)]+\"\)/gi,
                        'url("' + _gif + '")');
    }

    _altcss._parse(css);
    _specs.sort(function(a, b) {
      return a - b;
    });
    _ss.create(_ssid);
    return true;
  },

  _validate: function(context) {
    // uuNode.cutdown - cut all nodes less than context
    function cutdown(context) { // @Node(= document.body): parent node
                                // @return DocumentFragment:
      var rv, ctx = context || _doc.body;
      if (_doc.createRange) {
        (rv = _doc.createRange()).selectNodeContents(ctx);
        return rv.extractContents(); // return DocumentFragment
      }
      rv = _doc.createDocumentFragment();
      while (ctx.firstChild) {
        rv.appendChild(ctx.removeChild(ctx.firstChild));
      }
      return rv;
    }

    var v, w, i = 0, j, k, l, iz = _specs.length, jz, kz, lz,
        spec, data, expr, ruleid, nodeuid, found, node, unique = {},
        IMP = " !important;",
        skip = _ie6
             ? /^\*$|:before$|:after$|:first-letter$|:first-line$|:active|:focus|:unknown/
             : /^\*$|:before$|:after$|:first-letter$|:first-line$|:active|:focus|:unknown|:hover/,
        hover = /:hover/,
        pseudo,
        nodeList = [], fragment, ruleset = [],
        stripEmbedImageNode, // strip embed image
        // document fragment context
        dfctx = (!context || context === _doc ||
                             context === _root) ? _doc.body : context;

    for (; i < iz; ++i) {
      spec = _specs[i];
      data = _data[spec];

      for (j = 0, jz = data.length; j < jz; ++j) {
        expr = data[j].expr;
        if (skip.test(expr)) { // skip universal, pseudo-class/elements
          continue;
        }

        try {
          if (_boostup) {
            found = _plus.find(data[j].pair, expr);
          }
          pseudo = 0;

          if (!_ie6 || !hover.test(expr)) {
            node = _query(expr, context);
          } else {
            node = _query(expr.replace(hover, function(m) {
              pseudo |= 0x2;
              return "";
            }), context);
          }

          if (_ie || spec < 10000) {
            // make unique rule id from expr
            ruleid = _rule[expr] || (_rule[expr] = ++_ruleuid);

            // add new rule
            if (_markup) {
              // ".uucss[num] { color: red; font-size: 24pt; ... }"
              w = (spec < 10000) ? data[j].decl.join(";")
                                 : data[j].decl.join(IMP) + IMP;
              w += ";";
              if (!pseudo) {
                ruleset.push(".uucss" + ruleid, w);
              } else if (pseudo & 0x2) { // 0x2: hover
                ruleset.push(".uucsshover" + ruleid, w);
              }
            }
            for (k = 0, kz = node.length; k < kz; ++k) {
              v = node[k];
              nodeuid = _mm.uid(v); // node unique id

              // prepare container
              if (!(nodeuid in unique)) { // first time
                unique[nodeuid] = {
                  node: v, rules: {}, klass: [],
                  found: { bits: 0, prop: {}, order: "" }
                };
              }
              // regist rule if not exists
              if (!(ruleid in unique[nodeuid].rules)) {
                unique[nodeuid].rules[ruleid] = ruleid;
                if (_markup) {
                  if (!pseudo) {
                    unique[nodeuid].klass.push("uucss" + ruleid);
                  } else if (pseudo & 0x2) { // 0x2: hover
                    if (!("uuCSSHover" in v)) {
                      v.uuCSSHover = ""; // bond
                    }
                    v.uuCSSHover += " uucsshover" + ruleid;
                  }
                }
                if (_stripWidth) {
                  if (ruleid >= 2) {
                    if (/display:inline;/i.test(w) ||
                        /^inline$/.test(v.currentStyle.display)) {
                      !pseudo &&
                          _markup && unique[nodeuid].klass.push("uucssinline");
                    }
                  }
                }
              }
              if (_boostup) {
                unique[nodeuid].found.bits |= found.bits;
                _mix(unique[nodeuid].found.prop, found.prop);
                unique[nodeuid].found.order += found.order; // "has,last-comma,"
              }
            }
          } else { // !important
            for (k = 0, kz = node.length; k < kz; ++k) {
              v = node[k];
              nodeuid = _mm.uid(v); // node unique id

              if (_markup) {
                v.setAttribute("uuCSSI", 1); // bond + markup for revalidate

                // prepare container
                !("uuCSSIHash" in v) && (v.uuCSSIHash = {}); // bond

                for (l = 0, lz = data[j].pair.length; l < lz; ++l) {
                  w = data[j].pair[l];
                  // save
                  v.uuCSSIHash[w.prop] = v.style.getPropertyValue(w.prop);
                  // overwrite
                  v.style.setProperty(w.prop, w.val, "important");
                }
              }

              // prepare container
              if (!(nodeuid in unique)) { // first time
                unique[nodeuid] = {
                  node: v, rules: {}, klass: [],
                  found: { bits: 0, prop: {}, order: "" }
                };
              }
              // regist rule if not exists
              if (_boostup) {
                unique[nodeuid].found.bits |= found.bits;
                _mix(unique[nodeuid].found.prop, found.prop);
                unique[nodeuid].found.order += found.order; // "has,last-comma,"
              }
            }
          }
        } catch(err) {}
      }
    }

    // --- make boostup plan ---
    if (_boostup) {
      // create array from unique data
      for (nodeuid in unique) {
        nodeList.push(unique[nodeuid].node, unique[nodeuid].found);
      }
      _plus.plan(nodeList, _init, context);
    }

    // --- collect effective nodeList ---

    // query('<img src="data:...">') nodeList
    if (!_init && _stripEmbedImage) {
      stripEmbedImageNode = [], node = _query.tag("img"), i = 0;
      while ( (v = node[i++]) ) {
        DATA_SCHEME.test(v.src) && stripEmbedImageNode.push(v);
      }
    }

    _enableDocumentFragment && !_plus.deny() && (fragment = cutdown(dfctx));
    // --- begin code block ---
        // lazy - clear all rules
        _ss.removeAllRules(_ssid);
        _ruleset = [];

        // lazy - clear all className
        for (i = 0, iz = _lazyClearClass.length; i < iz; ++i) {
          v = _lazyClearClass[i];
          if (v) {
            v.className = v.className.replace(/uucss[\w]+\s*/g, "");
            v.removeAttribute("uuCSSC");
          }
        }

        // apply to className
        if (_markup) {
          for (nodeuid in unique) {
            v = unique[nodeuid].node;
            w = v.className + " " + unique[nodeuid].klass.join(" ");
            v.className = w.replace(TRIM, "").replace(/\s+/g, " ");
            v.setAttribute("uuCSSC", "1"); // bond + markup for revalidate
          }
        }
        // insert rule
        for (i = 0, iz = ruleset.length; i < iz; i += 2) {
          _ss.insertRule(_ssid, ruleset[i], ruleset[i + 1]);
          _ruleset.push(ruleset[i] + " { " + ruleset[i + 1] + " }");
        }
        // strip width
        if (_stripWidth) {
          _ss.insertRule(_ssid, ".uucssinline", DETOXIFY_WIDTH);
          _ruleset.push(".uucssinline" + " { " + DETOXIFY_WIDTH + " }");
        }
        // <img src="data:..."> -> <img src="1dot.gif">
        if (stripEmbedImageNode) {
          i = 0;
          while ( (v = stripEmbedImageNode[i++]) ) {
            v.src = _gif;
          }
        }
        // boost prevalidate
        if (_boostup) {
          _plus.prevalidate(nodeList, _init, context);
        }
    // --- end code block ---
    _enableDocumentFragment && !_plus.deny() && dfctx.appendChild(fragment);

    // boost postvalidate
    if (_boostup) {
      _plus.postvalidate(nodeList, _init, context);
    }

    // Opera9.5+ problem fix and Opera9.2 flicker fix
    if (_opera) {
      _enableDocumentFragment = 0;
    }
  },

  _parse: function(css) {
    // escape "{};,"
    function esc(m, q, str) {
      ++escape;
      return q + str.replace(/\{/g, "\\u007B").replace(/;/g, "\\u003B").
                     replace(/\}/g, "\\u007D").replace(/,/g, "\\u002C") + q;
    }

    // unescape "{};,"
    function unesc(str) {
      return str.replace(/\\u007B/g, "{").replace(/\\u003B/g, ";").
                 replace(/\\u007D/g, "}").replace(/\\u002C/g, ",");
    }
    // http://www.w3.org/TR/2005/WD-css3-selectors-20051215/#specificity
    function calcSpec(expr) {
      var a = 0, b = 0, c = 0;
      function B() { ++b; return ""; }

      expr.replace(SPEC_NOT, function(m, E) { return " " + E; }).
            replace(SPEC_ID, function() { ++a; return ""; }). // #id
            replace(SPEC_CLASS, B).     // .class
            replace(SPEC_CONTAINS, B).  // :contains("...")
            replace(SPEC_PELEMENT, ""). // ::pseudo-element
            replace(SPEC_PCLASS, B).    // :pseudo-class
            replace(SPEC_ATTR, B).      // [attr=value]
            replace(SPEC_E,  function() { ++c; return ""; }); // E
      return a * 100 + b * 10 + c;
    }

    var escape = 0, v, i, j, k, iz, jz, kz,
        gd1, gd2, gp1, gp2, ary, expr, decl, decls, exprs, spec,
        rex1 = /\s*\!important\s*/,
        rex2 = /\s*\!important\s*/g,
        ignore, prop, val, valid;

    v = css.replace(/(["'])(.*?)\1/g, esc);
    if (_ie) {
      v = v.replace(/^\s*\{/,   "*{").
            replace(/\}\s*\{/g, "}*{").
            replace(/\{\}/g,    "{ }"); // for IE Array.split bug
    }
    ary = v.split(/\s*\{|\}\s*/);
    !_ie && ary.pop(); // for IE Array.split bug

    if (ary.length % 2) {
      return; // parse error
    }

    for (i = 0, iz = ary.length; i < iz; i += 2) {
      expr = ary[i];
      decl = ary[i + 1].replace(TRIM, "");
      exprs = (expr + ",").split(/,+/); !_ie && exprs.pop(); // IE split bug
      decls = (decl + ";").split(/;+/); !_ie && decls.pop(); // IE split bug

      gd1 = [], gd2 = [], gp1 = [], gp2 = [];
      for (k = 0, kz = decls.length; k < kz; ++k) {
        ignore = 0;

        if (decls[k]) {
          v = decls[k].replace(/;+$/, "").replace(TRIM, "").split(/\s*:\s*/);
          prop = v.shift();
          val = v.join(":");
          val = escape ? unesc(val): val;

          if (/\\/.test(prop)) { // .parser { m\argin: 2em; };
            ++ignore;
          } else if (rex1.test(val)) { // !important
            val = val.replace(rex2, "");
            valid = (_enableValidation && _validatorProps[prop]) ?
                        _validator[prop](val) : 1;
            if (valid) {
              gd2.push(prop + ":" + val);
              gp2.push({ prop: prop, val: val });
            } else {
              ++ignore;
            }
          } else if (/!/.test(val)) {
            // fix #Acid2  .parser { border: 5em solid red ! error; }
            ++ignore;
          } else {
            valid = (_enableValidation && _validatorProps[prop]) ?
                        _validator[prop](val) : 1;
            if (valid) {
              gd1.push(prop + ":" + val);
              gp1.push({ prop: prop, val: val });
            } else {
              ++ignore;
            }
          }
          ignore && _mm.debug &&
              alert('"' + prop + ":" + val + '" ignore decl');
        }
      }
      for (j = 0, jz = exprs.length; j < jz; ++j) {
        v = (escape ? unesc(exprs[j]) : exprs[j]).replace(TRIM, "");

        // * html .parser {  background: gray; }  -> "gray"
        if (/^\s*\*\s+html/i.test(v)) {
          _mm.debug && alert(v + " ignore CSS Star hack");
          continue; // ignore rule set
        }
        spec = calcSpec(v);
        if (gd1.length) {
          !(spec in _data) && (_specs.push(spec), _data[spec] = []);
          _data[spec].push({ expr: v, decl: gd1, pair: gp1 });
        }
        if (gd2.length) { // !important
          spec += 10000;
          !(spec in _data) && (_specs.push(spec), _data[spec] = []);
          _data[spec].push({ expr: v, decl: gd2, pair: gp2 });
        }
      }
    }
  },

  _imports: function() { // @return String: minified CSS
    function imports(css, absdir) { // @import
      return css.replace(COMMENT, "").
                 replace(IMPORTS, function(m, url, media) {
        var v = toAbsURL(url, absdir);
        return imports(sync(v), toDir(v));
      });
    }

    var rv = [], absdir = toAbsURL("."), href, hash, dstr,
        node = _mm.toArray(_doc.styleSheets), v, w, i = 0,
        prop1 = _ie ? "owningElement" : "ownerNode",
        prop2 = _ie ? MEMENTO : "textContent";

    while ( (v = node[i++]) ) {
      if (!v.disabled) {
        href = v.href || "";
        if (!DATA_SCHEME.test(href)) { // execlude data:text/css,...
          if (/\.css$/.test(href)) {
            // <link>
            w = toAbsURL(v.href, absdir);
            !(w in _cache) && (_cache[w] = imports(sync(w), toDir(w)));
            rv.push(_cache[w]);
          } else {
            // <style>
            rv.push(imports(v[prop1][prop2], absdir));
          }
        }
      }
    }
    // decode datauri
    //    <link href="data:text/css,...">
    if (_codec) {
      node = _query.tag("link"), i = 0;
      while ( (v = node[i++]) ) {
        if (DATA_SCHEME.test(v.href)) {
          hash = _codec.decode(v.href);
          if (hash.mime === "text/css") {
            dstr = String.fromCharCode.apply(null, hash.data);
            w = "link" + i; // "link1"
            !(w in _cache) && (_cache[w] = imports(dstr, absdir));
            rv.push(_cache[w]);
          }
        }
      }
    }
    // decode script
    //    <script src="data:text/javascript,..">
    if (_codec && _ie67) {
      node = _query.tag("script"), i = 0;
      while ( (v = node[i++]) ) {
        if (DATA_SCHEME.test(v.src)) {
          hash = _codec.decode(v.src);
          if (hash.mime === "text/javascript") {
            dstr = String.fromCharCode.apply(null, hash.data);
            (new Function(dstr))();
          }
        }
      }
    }
    return rv.join("");
  },

  _cleanup: function(css) { // @param String: dirty css
                            // @return String: clean css
    return css.replace(/<!--|-->/g,  ""). // <!-- ... --> (
        replace(/url\(([^\)]*)\)/gi, function(m, data) {
          return 'url("' + data.replace(/^["']|["']$/g, "") + '")';
        }).
        replace(/\\([{};,])/g, function(m, c) { // \} -> \\u007d
          return (0x10000 + c.charCodeAt(0)).
                    toString(16).replace(/^1/, "\\\\u");
        }).
        replace(/@[^\{]+\{[^\}]*\}/g, "").  // @font-face @page
        replace(/@[^;]+\s*;/g, "").         // @charset
        replace(/\s*[\r\n]+\s*/g, " ").     // ...\r\n...
        replace(/[\u0000-\u001f]+/g, "").   // \u0009 -> "" (unicode)
        replace(/\\x[01]?[0-9a-f]/gi, "").  // "\x9"  -> "" (hex \x00~\1f)
        replace(/\\[0-3]?[0-7]/g, "").      // "\9"   -> "" (octet \0~\37)
        replace(TRIM, "");
  }
});

function toAbsURL(url, curtdir) {
  if (!/^(file|https|http)\:\/\//.test(url)) {
    var div = _doc.createElement("div");

    div.innerHTML = '<a href="' + (curtdir || "") + url + '" />';
    url = div.firstChild ? div.firstChild.href
                         : /href\="([^"]+)"/.exec(div.innerHTML)[1];
  }
  return url;
}

function toDir(absurl) {
  var ary = absurl.split("/");
  ary.pop();
  return ary.join("/") + "/";
}

function sync(url) {
  try {
    if (!_xhr && _ie && "ActiveXObject" in _win) {
      _xhr = new ActiveXObject("Microsoft.XMLHTTP");
    }
    if (!_xhr && "XMLHttpRequest" in _win) {
      _xhr = new XMLHttpRequest();
    }
    if (_xhr) {
      _xhr.open("GET", url, false); // XDomain no check
      _xhr.send(null);
      return (_xhr.status === 200 || !_xhr.status) ? _xhr.responseText : "";
    }
  } catch (err) {}
  return "";
}

// === uuCSSValidator ===
// depend: uuColor
_validator = function(prop, value) {
  return _validatorProps[prop] ? _validator[prop](value) : true;
};

_validator.width = function(value) {
  return /^(?:0|[\d\.]+(%|px|em|pt|cm|mm|in|pc|px)|auto)$/i.test(value);
};

// CSS2.1 Level background props validation
_validator.background = function(value) {
  var rv = true, ary = value.split(/\s+/), v, i = 0,
      url = [], color = [], repeat = [], position = [], attachment = [];

  while ( (v = ary[i++]) ) {
    if (BACKGROUND_URL.test(v)) {
      url.push(v);
    } else if (BACKGROUND_REPEAT.test(v)) {
      repeat.push(v);
    } else if (BACKGROUND_POSITION.test(v)) {
      position.push(v);
    } else if (BACKGROUND_ATTACHMENT.test(v)) {
      attachment.push(v);
    } else {
      try {
        color.push(_color.parse(v, 1, 1));
      } catch(err) { rv = false; }
    }
  }
  if (rv) {
    if (url.length > 1 ||
        color.length > 1 ||
        repeat.length > 1 ||
        position.length > 2 ||
        attachment.length > 1) {
      rv = false;
    }
  }
  return rv;
};

// --- initialize ---
function autoexec() {
  // --- conditional selector ---
  //  <style>
  //    div>ul { color: black }                /* for Generic browser */
  //    html.ifwebkit div>ul { color: blue }   /* for Safari, Chrome */
  //    html.ifchrome3 div>ul { color: green } /* for Google Chrome3 */
  //    html.ifopera92 div>ul { color: red }   /* for Opera9.27 */
  //    html.ifswlt800.ifshlt600 div>ul { font-size: large }
  //                                           /* screen width  < 800 and
  //                                              screen height < 600 */
  //  </style>
  //  +----------------+---------------+------------------------------
  //  | CONDITION      | IDENT         | NOTE
  //  +----------------+---------------+------------------------------
  //  | IE             | "ifie"        | version >= 6
  //  | IE6.0          | "ifie6"       |
  //  | IE7.0          | "ifie7"       |
  //  | IE8.0          | "ifie8"       |
  //  | Opera          | "ifopera"     | version >= 9.20
  //  | Opera9.27      | "ifopera92"   |
  //  | Opera9.63      | "ifopera96"   |
  //  | Opera10.00     | "ifopera10"   |
  //  | Opera10.10     | "ifopera101"  |
  //  | Gecko          | "ifgecko"     |
  //  | Gecko1.9.1     | "ifgecko191b" | Gecko engine version >= 191(Firefox3.5)
  //  | Firefox        | "iffx"        | version >= 2
  //  | Firefox2.0     | "iffx2"       |
  //  | Firefox3.0     | "iffx3"       |
  //  | Firefox3.5     | "iffx35"      |
  //  | WebKit         | "ifwebkit"    | has Safari, Chrome, iPhone
  //  | WebKit522      | "ifwebkit522b"| WebKit engine version >= 522(Safari3)
  //  | WebKit530      | "ifwebkit530b"| WebKit engine version >= 530(Safari4)
  //  | Safari         | "ifsafari"    | version >= 3
  //  | Safari3.2.3    | "ifsafari32"  |
  //  | Safari4.0      | "ifsafari4"   |
  //  | iPod           | "ifiphone"    |
  //  | iPhone         | "ifiphone"    |
  //  | Chrome         | "ifchrome"    | version >= 1
  //  | Chrome1.0      | "ifchrome1"   |
  //  | Chrome2.0      | "ifchrome2"   |
  //  | Chrome3.0      | "ifchrome3"   |
  //  | Silverlight    | "ifsl"        | version >= 1
  //  | Flash          | "ifflash"     | version >= 7
  //  | HTML5::Canvas  | "ifcanvas"    | enable HTML5::Canvas
  //  | JavaScript     | "ifjs"        | enable JavaScript
  //  | width  <  800  | "ifw800s"     | screen width  <  800px
  //  | width  >= 1200 | "ifw1200b"    | screen width  >= 1200px
  //  | height <  600  | "ifh600s"     | screen height <  600px
  //  | height >= 1000 | "ifh1000b"    | screen height >= 1000px
  //  +----------------+---------------+------------------------------
  _root = _query.tag("html")[0];

  var cn = [_root.className.replace(/ifnojs/, ""), "ifjs"],
      css = "", context, tick,
      sw = screen.width, sh = screen.height;

  _ie          && cn.push("ifie ifie" + _uaver);
  _opera       && cn.push("ifopera ifopera" + _uaver);
  _gecko       && cn.push("ifgecko");
  _gecko       && _egver >= 1.91 && cn.push("ifgecko191b");
  _mm.firefox  && cn.push("iffx iffx" + _uaver);
  _webkit      && cn.push("ifwebkit");
  _webkit      && _egver >= 522  && cn.push("ifwebkit522b");
  _webkit      && _egver >= 530  && cn.push("ifwebkit530b");
  _safari      && cn.push("ifsafari ifsafari" + _uaver);
  _chrome      && cn.push("ifchrome ifchrome" + _uaver);
  _mm.iphone   && cn.push("ifiphone");
  _mm.slver    && cn.push("ifsl");
  _mm.flashver && cn.push("ifflash");
  _mm.canvas   && cn.push("ifcanvas");
  (sw <   800) && cn.push("ifw800s");
  (sw >= 1200) && cn.push("ifw1200b");
  (sh <   600) && cn.push("ifh600s");
  (sh >= 1000) && cn.push("ifh1000b");

  if (!_win.UUALTCSS_DISABLE_CONDITIONAL_SELECTOR) {
    _root.className = cn.join(" ").replace(TRIM, "").replace(/\./g, "");
  }

  _plus = uuAltCSSPlus;
  _codec = _win.uuCodecDataURI ? uuCodecDataURI : 0;

  if (_markup || _boostup) {
    tick = +new Date;
    // --- begin perf block ---
        if (!_altcss._init(css)) {
          return; // reload from browser cache
        }
        _boostup && _plus.init(context);
        _altcss._validate(context);
        _init = 1;
    // --- end perf block ---
    status = new Date - tick;
  }
}

_ie     && (_uaver >= 6   && _uaver <  9  ) && (++_markup, ++_boostup);
_opera  && (_uaver >= 9.2 && _uaver <  9.5) && (++_markup);
_opera  && (_uaver >= 9.2 && _uaver <  11 ) && (++_boostup); // Opera9.2-10
_gecko  && (_egver >  1.8 && _egver <= 1.9) && (++_markup);  // Fx2-Fx3
_gecko  && (_egver >  1.8 && _egver <= 1.91)&& (++_boostup); // Fx2-Fx3.5
_safari && (_uaver >= 3   && _uaver <  3.1) && (++_markup);
_chrome && (_uaver <  2                   ) && (++_markup);

switch (_win.UUALTCSS_FORCE_MARKUP || 2) {
case 0: _markup = 0; break;
case 1: _markup = 1;
}
_mm.boot(autoexec);

// --- export ---
_win.uuAltCSS = _altcss;
_win.uuCSSValidator = _validator;

})(); // uuAltCSS scope

