(function() {

  CodeMirror.extendMode("css", {
    commentStart: "/*",
    commentEnd: "*/",
    newlineAfterToken: function(_type, content) {
      return /^[;{}]$/.test(content);
    }
  });

  CodeMirror.extendMode("javascript", {
      commentStart: "/*",
      commentEnd: "*/",
      wordWrapChars: [";", "\\{", "\\}"],

      autoFormatLineBreaks: function (text) {
          var curPos = 0;
          var split = this.jsonMode ? function (str) {
              return str.replace(/([,{])/g, "$1\n").replace(/}/g, "\n}");
          } : function (str) {
              return str.replace(/(;|\{|\})([^\r\n;])/g, "$1\n$2");
          };
          var nonBreakableBlocks = jsNonBreakableBlocks(text), res = "";
          if (nonBreakableBlocks != null) {
              for (var i = 0; i < nonBreakableBlocks.length; i++) {
                  if (nonBreakableBlocks[i].start > curPos) { // Break lines till the block
                      res += split(text.substring(curPos, nonBreakableBlocks[i].start));
                      curPos = nonBreakableBlocks[i].start;
                  }
                  if (nonBreakableBlocks[i].start <= curPos
                      && nonBreakableBlocks[i].end >= curPos) { // Skip non-breakable block
                      res += text.substring(curPos, nonBreakableBlocks[i].end);
                      curPos = nonBreakableBlocks[i].end;
                  }
              }
              if (curPos < text.length)
                  res += split(text.substr(curPos));
          } else {
              res = split(text);
          }
          return res.replace(/^\n*|\n*$/, "");
      }
  });


  function jsNonBreakableBlocks(text) {
      var nonBreakableRegexes = [/for\s*?\((.*?)\)/g,
                                 /&#?[a-z0-9]+;[\s\S]/g,
                                 /\"(.*?)((\")|$)/g,
                                 /\/\*(.*?)(\*\/|$)/g,
                                 /^\/\/.*/g]
      var nonBreakableBlocks = [];
      for (var i = 0; i < nonBreakableRegexes.length; i++) {
          var curPos = 0;
          while (curPos < text.length) {
              var m = text.substr(curPos).match(nonBreakableRegexes[i]);
              if (m != null) {
                  nonBreakableBlocks.push({
                      start: curPos + m.index,
                      end: curPos + m.index + m[0].length
                  });
                  curPos += m.index + Math.max(1, m[0].length);
              }
              else { // No more matches
                  break;
              }
          }
      }
      nonBreakableBlocks.sort(function (a, b) {
          return a.start - b.start;
      });

      return nonBreakableBlocks;
  }
  var inlineElements = /^(a|abbr|acronym|area|base|bdo|big|br|button|caption|cite|code|col|colgroup|dd|del|dfn|em|frame|hr|iframe|img|input|ins|kbd|label|legend|link|map|object|optgroup|option|param|q|samp|script|select|small|span|strong|sub|sup|textarea|tt|var)$/;

  CodeMirror.extendMode("xml", {
      commentStart: "<!--",
      commentEnd: "-->",
      noBreak: false,
      noBreakEmpty: null,
      tagType: "",
      tagName: "",
      isXML: false,
      newlineAfterToken: function (type, content, textAfter, state) {
          var noBreakTagsInner = "label|li|option|textarea|title";
          var noBreakTagsOuter = "a|b|bdi|bdo|big|center|cite|del|em|font|i|img|ins|s|small|span|strike|strong|sub|sup|u";
          var noBreakTagsEither = noBreakTagsInner + "|" + noBreakTagsOuter;
          var noBreak = false, matches = null, tagname = "";
          this.isXML = this.configuration == "xml" ? true : false;
          if (type == "comment" || /<!--/.test(textAfter)) return false;
          if (type == "tag") {
              if (content.indexOf("<") == 0 && !content.indexOf("</") == 0) {
                  this.tagType = "open";
                  matches = content.match(/^<\s*?([\w]+?)$/i);
                  this.tagName = matches != null ? matches[1] : "";
                  var tagname = this.tagName.toLowerCase();
                  if (("|" + noBreakTagsEither + "|").indexOf("|" + tagname + "|") != -1)
                      this.noBreak = true;
              }
              if (content.indexOf(">") == 0 && this.tagType == "open") {
                  this.tagType = "";
                  var textInsert = this.isXML ? "[^<]*?" : "";
                  if (RegExp("^" + textInsert + "<\/\s*?" + this.tagName + "\s*?>", "i").test(textAfter)) {
                      this.noBreak = false;
                      if (!this.isXML) this.tagName = "";
                      return false;
                  }
                  noBreak = this.noBreak;
                  this.noBreak = false;
                  return noBreak ? false : true;
              }
              if (content.indexOf("</") == 0) {
                  this.tagType = "close";
                  matches = content.match(/^<\/\s*?([\w]+?)$/i);
                  if (matches != null) tagname = matches[1].toLowerCase();
                  if (("|" + noBreakTagsOuter + "|").indexOf("|" + tagname + "|") != -1)
                      this.noBreak = true;
              }
              if (content.indexOf(">") == 0 && this.tagType == "close") {
                  this.tagType = "";
                  if (textAfter.indexOf("<") == 0) {
                      matches = textAfter.match(/^<\/?\s*?([\w]+?)(\s|>)/i);
                      tagname = matches != null ? matches[1].toLowerCase() : "";
                      if (("|" + noBreakTagsEither + "|").indexOf("|" + tagname + "|") == -1) {
                          this.noBreak = false;
                          return true;
                      }
                  }
                  noBreak = this.noBreak;
                  this.noBreak = false;
                  return noBreak ? false : true;
              }
          }
          if (textAfter.indexOf("<") == 0) {
              this.noBreak = false;
              if (this.isXML && this.tagName != "") {
                  this.tagName = "";
                  return false;
              }
              matches = textAfter.match(/^<\/?\s*?([\w]+?)(\s|>)/i);
              tagname = matches != null ? matches[1].toLowerCase() : "";
              if (("|" + noBreakTagsEither + "|").indexOf("|" + tagname + "|") != -1)
                  return false;
              else return true;
          }
          return false;
      }
  });

  // Comment/uncomment the specified range
  CodeMirror.defineExtension("commentRange", function (isComment, from, to) {
    var cm = this, curMode = CodeMirror.innerMode(cm.getMode(), cm.getTokenAt(from).state).mode;
    cm.operation(function() {
      if (isComment) { // Comment range
        cm.replaceRange(curMode.commentEnd, to);
        cm.replaceRange(curMode.commentStart, from);
        if (from.line == to.line && from.ch == to.ch) // An empty comment inserted - put cursor inside
          cm.setCursor(from.line, from.ch + curMode.commentStart.length);
      } else { // Uncomment range
        var selText = cm.getRange(from, to);
        var startIndex = selText.indexOf(curMode.commentStart);
        var endIndex = selText.lastIndexOf(curMode.commentEnd);
        if (startIndex > -1 && endIndex > -1 && endIndex > startIndex) {
          // Take string till comment start
          selText = selText.substr(0, startIndex)
          // From comment start till comment end
            + selText.substring(startIndex + curMode.commentStart.length, endIndex)
          // From comment end till string end
            + selText.substr(endIndex + curMode.commentEnd.length);
        }
        cm.replaceRange(selText, from, to);
      }
    });
  });

  // Applies automatic mode-aware indentation to the specified range
  CodeMirror.defineExtension("autoIndentRange", function (from, to) {
    var cmInstance = this;
    this.operation(function () {
      for (var i = from.line; i <= to.line; i++) {
        cmInstance.indentLine(i, "smart");
      }
    });
  });

  // Applies automatic formatting to the specified range
  CodeMirror.defineExtension("autoFormatRange", function (from, to) {
    var cm = this;
    var outer = cm.getMode(), text = cm.getRange(from, to).split("\n");
    var state = CodeMirror.copyState(outer, cm.getTokenAt(from).state);
    var tabSize = cm.getOption("tabSize");

    var out = "", lines = 0, atSol = from.ch == 0;
    function newline() {
      out += "\n";
      atSol = true;
      ++lines;
    }

    for (var i = 0; i < text.length; ++i) {
      var stream = new CodeMirror.StringStream(text[i], tabSize);
      while (!stream.eol()) {
        var inner = CodeMirror.innerMode(outer, state);
        var style = outer.token(stream, state), cur = stream.current();
        stream.start = stream.pos;
        if (!atSol || /\S/.test(cur)) {
          out += cur;
          atSol = false;
        }
        if (!atSol && inner.mode.newlineAfterToken &&
            inner.mode.newlineAfterToken(style, cur, stream.string.slice(stream.pos) || text[i+1] || "", inner.state))
          newline();
      }
      if (!stream.pos && outer.blankLine) outer.blankLine(state);
      if (!atSol && i < text.length - 1) newline();
    }

    cm.operation(function () {
      cm.replaceRange(out, from, to);
      for (var cur = from.line + 1, end = from.line + lines; cur <= end; ++cur)
        cm.indentLine(cur, "smart");
      cm.setSelection(from, cm.getCursor(false));
    });
  });
})();
