
// === uuStyleSheet ===
// depend: uuMeta
/*
uuStyleSheet.create(id) - return object
uuStyleSheet.insertRule(id, expr, decl, index = last) - return index
uuStyleSheet.removeRule(id, index = last)
uuStyleSheet.removeAllRules(id)
 */
(function() {
var _ss, // inner namespace
    _mm = uuMeta,
    _win = window,
    _doc = document,
    _ie = _mm.ie,
    _sheet = {}, // private style sheet
    TRIM = /^\s+|\s+$/g;

_ss = {
  // uuStyleSheet.create - create StyleSheet
  create: function(id) { // @param String: StyleSheet id
                         // @return StyleSheet: new or already object
    if (id in _sheet) { // already exists
      return _sheet[id];
    }
    if (_ie) {
      _sheet[id] = _doc.createStyleSheet();
    } else {
      var elm = _doc.createElement("style");
      elm.appendChild(_doc.createTextNode(""));
      _sheet[id] = _doc.getElementsByTagName("head")[0].appendChild(elm);
    }
    return _sheet[id];
  },

  // uuStyleSheet.insertRule - insert CSS rule
  insertRule: function(id,      // @param String: StyleSheet id
                       expr,    // @param String: css selector
                       decl,    // @param String: css declaration
                       index) { // @param Number(= last): insertion position
                                // @return Number: inserted rule index
                                //                 or -1(error)
    if (!(id in _sheet)) { return -1; }

    var r = _sheet[id];
    if (_ie) {
      index = index === void 0 ? r.rules.length : index;
      r.addRule(expr.replace(TRIM, ""), decl.replace(TRIM, ""), index);
    } else {
      index = index === void 0 ? r.sheet.cssRules.length : index;
      index = r.sheet.insertRule(expr + "{" + decl + "}", index);
      if (_mm.opera && _mm.uaver < 9.5) { // Opera90 bug
        index = r.sheet.cssRules.length - 1;
      }
    }
    return index;
  },

  // uu.style.removeRule - remove CSS rule
  removeRule: function(id,      // @param String: StyleSheet id
                       index) { // @param Number(= last): deletion position
    if (!(id in _sheet)) { return; }

    var r = _sheet[id];
    if (_ie) {
      index = (index === void 0) ? r.rules.length - 1 : index;
      (index >= 0) && r.removeRule(index);
    } else {
      index = (index === void 0) ? r.sheet.cssRules.length - 1 : index;
      (index >= 0) && r.sheet.deleteRule(index);
    }
  },

  // uuStyleSheet.removeAllRules - remove all CSS rules
  removeAllRules: function(id) {
    if (!(id in _sheet)) { return; }

    var r = _sheet[id],
        i = _ie ? r.rules.length
                : r.sheet.cssRules.length;
    while (i--) {
      _ie ? r.removeRule(i)
          : r.sheet.deleteRule(i);
    }
  }
};

// --- initialize ---

// --- export ---
_win.uuStyleSheet = _ss;

})(); // uuStyleSheet scope
