function $(tag, attrs, styles) {
    var elem = document.createElement(tag), attr;
    if (attrs) {
        for (attr in attrs)
            elem[attr] = attrs[attr];
    }
    if (styles) {
        for (attr in styles)
            elem.style[attr] = styles[attr];
    }
    return elem;
}

var ajax = {
    createRequest: function() {
        if (window.XMLHttpRequest)
            return new XMLHttpRequest();
        if (window.ActiveXObject)
            return new ActiveXObject("Msxml2.XMLHTTP");
        return null;
    },

    sendQuery: function(query) {
        var request = this.createRequest();
        if (!request)
            return null;

        request.onreadystatechange = function() {
            if (request.readyState != 4)
                return;
            if (request.status == 200) {
                query.callback(request);
                return;
            }
            var msg = request.status + " (" + request.statusText + ")";
            query.callback(null, { type: "HttpError", message: msg, url: query.url });
        };

        var queryStr = "", arg;
        for (arg in query.args) {
            var value = query.args[arg];
            if (!(value instanceof Array))
                value = [ value ];
            for (var i = 0; i < value.length; ++i) {
                if (queryStr != "")
                    queryStr += "&";
                queryStr += arg + "=" + encodeURIComponent(value[i]);
            }
        }
        var queryUrl = query.url;
        if (query.method == "GET") {
            queryUrl += "?" + queryStr;
            queryStr = null;
        }

        request.open(query.method, queryUrl, true);
        if (query.method == "POST")
            request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.send(queryStr);
        return request;
    }
};

var json = {
    TIMEOUT: 7000, // milliseconds
    id: 0,
    r: {},

    sendQuery: function(query) {
        var id = this.id++;
        this.r[id] = query;

        var queryStr = "_id=" + id, arg;
        for (arg in query.args) {
            var value = query.args[arg];
            if (!(value instanceof Array))
                value = [ value ];
            for (var i = 0; i < value.length; ++i)
                queryStr += "&" + arg + "=" + encodeURIComponent(value[i]);
        }
        var queryUrl = query.url + "?" + queryStr;

        var body = document.getElementsByTagName("BODY")[0];
        var script = $("SCRIPT", { type: "text/javascript", src: queryUrl, charset: "utf-8" });
        this.r[id].timer = window.setTimeout(
            function() { json.timeout(id) }, json.TIMEOUT);
        body.appendChild(script);
    },

    response: function(id, obj) {
        var r = this.r[id];
        if (r) {
            delete this.r[id];
            window.clearTimeout(r.timer);
            r.callback(obj);
        }
    },

    timeout: function(id) {
        var r = this.r[id];
        if (r) {
            delete this.r[id];
            r.callback(null, { type: "TimeoutError", message: "Timeout expired", url: r.url });
        }
    }
};

var util = {
    attachEvent: function(ctrl, event, handler) {
        if (ctrl.addEventListener)
            ctrl.addEventListener(event, handler, false);
        else if (ctrl.attachEvent)
            ctrl.attachEvent("on" + event, handler);
    },

    detachEvent: function(ctrl, event, handler) {
        if (ctrl.removeEventListener)
            ctrl.removeEventListener(event, handler, false);
        else if (ctrl.attachEvent)
            ctrl.detachEvent("on" + event, handler);
    },

    selectItem: function(ctrl, value) {
        ctrl.selectedIndex = -1;
        var options = ctrl.options;
        for (var i = 0; i < options.length; ++i) {
            if (options[i].value == value) {
                ctrl.selectedIndex = i;
                return true;
            }
        }
        return false;
    },

    extend: function(subClass, baseClass) {
        function inherit() {}
        inherit.prototype = baseClass.prototype;
        subClass.prototype = new inherit();
        subClass.prototype.constructor = subClass;
        subClass.superCtor = baseClass;
        subClass.superClass = baseClass.prototype;
    },

    _abc: [ "A-Z", "a-z", "\u0401-\u04e9" ],

    isAlpha: function(ch, pos) {
        var c = ch.charCodeAt(pos || 0);
        for (var i = 0; i < this._abc.length; ++i) {
            var r = this._abc[i];
            var from = r.charCodeAt(0), to = r.charCodeAt(r.length - 1);
            if (c >= from && c <= to)
                return true;
        }
        return false;
    },

    htmlEncode: function(str) {
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace (/>/g, "&gt;");
    },

    trim: function(str) {
        return str.replace(/^\s+|\s+$/g, "");
    },

    setCookies: function(name, dic, days) {
        var attr, value = "", sep = "";
        for (attr in dic) {
            value += sep + attr + "=" + dic[attr];
            sep = "&";
        }
        var now = new Date();
        var expires = new Date(now.getTime() + (days || 100) * 24 * 3600 * 1000);
        document.cookie = name + "=" + escape(value) + "; path=/; expires=" + expires.toUTCString();
    },

    getCookies: function(name) {
        var items = document.cookie.split("; ");
        var dic = {};
        for (var i = 0; i < items.length; ++i) {
            var cookie = items[i].split("=");
            if (cookie[0] != name)
                continue;
            var attrs = unescape(cookie[1]).split("&");
            for (var j = 0; j < attrs.length; ++j) {
                var pair = attrs[j].split("=");
                dic[pair[0]] = unescape(pair[1] || "");
            }
            break;
        }
        return dic;
    },

    modalDialog: function(url, size, args, onResult) {
        var left = 0, top = 0;
        if (window.outerWidth) {
            left = window.screenX + ((window.outerWidth - size.width) >> 1);
            top = window.screenY + ((window.outerHeight - size.height) >> 1);
        }
        if (window.showModalDialog && navigator.userAgent.indexOf("Opera") < 0) {
            var features = "dialogWidth:" + size.width + "px;dialogHeight:" + size.height + "px;scroll:no;help:no;status:no;";
            if (navigator.userAgent.indexOf("Firefox") >= 0)
                features += "dialogLeft:" + left + "px;dialogTop:" + top + "px;";
            window.showModalDialog(url, args, features);
            if (onResult)
                onResult();
        }
        else {
            var name = url.replace(/[\/\.]/g, "");
            var features = "width=" + size.width + ",height=" + size.height + ",toolbar=no,status=no,menubar=no,directories=no,resizable=no";
            if (left || top)
                features += ",left=" + left + ",top=" + top;
            window.theDlgArgs = args;
            var dlg = window.open(url, name, features);
            if (onResult)
                dlg.onunload = onResult;
        }
    },

    localStorage: function() {
        try {
            if (window.localStorage)
                return window.localStorage;
            if (window.globalStorage)
                return window.globalStorage[document.domain];
            return null;
        }
        catch(e) {
            return null;
        }
    }
};

var yandex = {
    json: json
}
function SpellDic(name) {
    this.items = {}
    if (name)
        this.load(name);
    this.dirty = false;
}

SpellDic.prototype.add = function(word, changeTo) {
    this.items[SpellDic.norm(word)] = (changeTo || "");
    this.dirty = true;
}

SpellDic.prototype.remove = function(word) {
    delete this.items[SpellDic.norm(word)];
    this.dirty = true;
}

SpellDic.prototype.check = function(word) {
    return typeof(this.items[SpellDic.norm(word)]) != "undefined";
}

SpellDic.prototype.getChange = function(word) {
    return this.items[SpellDic.norm(word)];
}

SpellDic.prototype.clear = function() {
    this.items = {};
    this.dirty = true;
}

SpellDic.prototype.load = function(name) {
    this.storage = util.localStorage();
    if (!this.storage)
        return;
    this.name = name;
    this.setContent(String(this.storage[name] || ""));
    this.dirty = false;
}

SpellDic.prototype.save = function() {
    if (!this.storage)
        return;
    this.storage[this.name] = this.getContent();
    this.dirty = false;
}

SpellDic.prototype.isDirty = function() {
    return this.dirty;
}

SpellDic.prototype.setContent = function(str) {
    this.items = {};
    var arr = str.split("\n");
    for (var i = 0; i < arr.length; ++i) {
        var word = SpellDic.norm(arr[i]);
        if (word)
            this.items[word] = "";
    }
    this.dirty = true;
}

SpellDic.compare = function(a, b) {
    if (a == b)
        return 0;
    return (a.toLowerCase() + " " + a) < (b.toLowerCase() + " " + b) ? -1 : 1;
}

SpellDic.prototype.getContent = function() {
    var arr = [];
    for (var word in this.items)
        arr.push(word);
    return arr.sort(SpellDic.compare).join("\n");
}

SpellDic.norm = function(s) {
    return s.replace(/\s+/g, " ").replace(/^\s+|\s+$/g, "");
}
//////////////////////////////////////////////////////////////////////////////
//
// SpellDialog
//

function SpellDialog() {
}

SpellDialog.prototype.init = function() {
    var args = { ctrls: [] };
    if (window.dialogArguments)
        args = window.dialogArguments;
    else if (window.opener && window.opener.theDlgArgs)
        args = window.opener.theDlgArgs;
    else
        return;

    window.oncontextmenu = function() { return false; }
    this.form = document.forms["form"];
    this.form.onsubmit = function() { return false; }
    this.form.word.origValue = "";
    this.optionEmpty = this.form.suggest.options[0];
    this.fragment = document.getElementById("fragment");
    this.reason = document.getElementById("reason");
    this.errMessages = this.getStr("spellErrors").split(":");

    this.speller = new Speller(this, args);
    this.session = this.speller.session;
    this.speller.startCheck();

    var ref = this;
    this.form.word.onkeyup =
    this.form.word.onmousedown = function() { ref.updateUI(); }
    this.form.ignoreOnce.onclick = function() { ref.session.ignore(false); }
    this.form.ignoreAll.onclick = function() { ref.session.ignore(true); }
    this.form.change.onclick = function() { ref.doChange(0); }
    this.form.changeAll.onclick = function() { ref.doChange(1); }
    if (this.speller.userDic) {
        this.form.addToDic.style.visibility = "visible";
        this.form.addToDic.onclick = function() { ref.session.addToDic(); }
    }
    this.form.cancel.onclick = function() { ref.close(); }
    this.form.suggest.ondblclick = function() { ref.doChange(2); }
    this.form.undo.onclick = function() { ref.session.undo(); }
    this.form.options.onclick = function() { ref.speller.optionsDialog(); }
    this.form.langList.onchange = function() {
        var langList = ref.form.langList;
        ref.speller.setLang(langList.options[langList.selectedIndex].value);
    }
}

SpellDialog.prototype.close = function() {
    window.close();
}

SpellDialog.prototype.enable = function(isActive) {
    for (var i = 0; i < this.form.elements.length; ++i) {
        var ctrl = this.form.elements[i];
        if (ctrl.name == "cancel")
            continue;
        ctrl.disabled = !isActive;
    }
    this.updateUI();
}

SpellDialog.prototype.setError = function(e) {
    var errIndex = e.code < this.errMessages.length ? e.code : 0;
    this.form.word.value = e.word;
    this.form.word.origValue = e.word;
    this.fragment.innerHTML = this.session.textDoc.getHtmlFragment(e.r);
    this.reason.innerHTML = this.errMessages[errIndex] + ":";
    this.form.addToDic.disabled = (e.code == Speller.REPEAT_WORD);
    this.form.undo.disabled = !this.session.canUndo();

    var suggest = this.form.suggest;
    suggest.options.length = 0;
    suggest.options.length = e.s.length;
    for (var i = 0; i < e.s.length; ++i)
        suggest.options[i].text = e.s[i];
    if (e.s.length > 0)
        suggest.selectedIndex = 0;
    else
        suggest.options.add(this.optionEmpty);
    this.updateUI();
}

SpellDialog.prototype.clearError = function() {
    this.form.word.value = "";
    this.fragment.innerHTML = "";
    this.reason.innerHTML = "";
    this.form.suggest.options.length = 0;
}

SpellDialog.prototype.setLang = function(lang) {
    return util.selectItem(this.form.langList, lang);
}

SpellDialog.prototype.doChange = function(cmd) {
    var all = (cmd == 1), isOk = false;
    var s = this.form.suggest;
    var suggest = "";
    if (s.selectedIndex >= 0)
        suggest = s.options[s.selectedIndex].text;
    if (cmd != 2 && this.form.word.value != this.form.word.origValue)
        suggest = this.form.word.value;

    for (var i = 0; i < s.options.length; ++i) {
        if (s.options[i].text == suggest)
            isOk = true;
    }

    var ref = this;
    if (!isOk) {
        this.session.checkWord(suggest, function(ok) {
            if (!ok) {
                var msg = ref.getStr("wordNotFound").replace("{0}", suggest);
                if (!window.confirm(msg))
                    return;
            }
            ref.session.change(all, suggest) || ref.changeFailed(suggest);
        });
        return;
    }

    this.session.change(all, suggest) || this.changeFailed(suggest);
}

SpellDialog.prototype.updateUI = function() {
    var word = this.form.word;

    var changeDisabled = (word.value == word.origValue && !this.hasSuggest());
    this.form.change.disabled = changeDisabled;
    this.form.changeAll.disabled = changeDisabled;

    var suggestDisabled = (word.value != word.origValue || !this.hasSuggest());
    this.form.suggest.disabled = suggestDisabled;
}

SpellDialog.prototype.changeFailed = function(suggest) {
    alert(this.getStr("changeError").replace("{0}", suggest));
}

SpellDialog.prototype.hasSuggest = function() {
    var s = this.form.suggest;
    return (s.options.length >= 1) && (s.options[0] != this.optionEmpty);
}

SpellDialog.prototype.getStr = function(id) {
    var p = document.getElementById(id);
    if (!p)
        return "";
    return p.innerHTML;
}

SpellDialog.prototype.stateChanged = function(isStopped) {
    this.enable(isStopped);
}

SpellDialog.prototype.errorFound = function() {
    this.setError(this.session.getError());
}

SpellDialog.prototype.checkCompleted = function() {
    alert(this.getStr("checkComplete"));
    this.speller.endCheck();
}

SpellDialog.prototype.onError = function(err) {
    var msg = err.message;
    switch (err.type) {
    case "HttpError":
        msg = "HTTP error: " + msg + "\nURL: " + err.url;
        break;
    case "TimeoutError":
        msg = this.getStr("timeoutError") + "\nURL: " + err.url;
        break;
    }
    alert(msg);
}
//////////////////////////////////////////////////////////////////////////////
//
// Speller
//

function Speller(dialog, args) {
    this.dialog = dialog;
    this.args = args;
    this.initParams();
    this.session = new SpellSession(dialog, args.ctrls);
    if (util.localStorage()) {
        this.userDic = new SpellDic("yandex.userdic")
        this.session.setUserDic(this.userDic);
    }
}

Speller.URL = "http://speller.yandex.net/services/spellservice.js";
Speller.MAX_TEXT_LEN = 300;
Speller.LATIN_OPTIONS = 0x0090;
Speller.REPEAT_WORD = 2;
Speller.TOO_MANY_ERRORS = 4;
Speller.START = 0;
Speller.CONTINUE = 1;

function s(value) {
    return typeof(value) == "undefined" ? "" : value.toString();
}

Speller.prototype.initParams = function() {
    var cookies = util.getCookies("yandex.spell");
    this.params = {};
    this.params.lang = this.args.lang || cookies.lang || this.args.defLang;
    this.params.options = parseInt(
        s(this.args.options) || s(cookies.options) || s(this.args.defOptions));
    this.dialog.setLang(this.params.lang);
}

Speller.prototype.startCheck = function() {
    this.doStart(Speller.START);
}

Speller.prototype.endCheck = function() {
    this.dialog.close();
}

Speller.prototype.doStart = function(cmd) {
    this.session.setParams(this.params);
    this.session.start(cmd);
}

Speller.prototype.optionsDialog = function() {
    var ref = this;
    var args = { lang: this.params.lang, options: this.params.options,
        recheck: false, userDicDlg: this.args.userDicDlg };
    util.modalDialog("spellopt.html", this.args.optDlg, args,
        function() {
            var cmd = args.recheck ? Speller.START : Speller.CONTINUE;
            if (ref.setParams(args) || args.recheck)
                ref.doStart(cmd);
        }
    );
}

Speller.prototype.setLang = function(lang) {
    if (this.setParams({ lang: lang, options: this.params.options }))
        this.doStart(Speller.CONTINUE);
}

Speller.prototype.setParams = function(params) {
    if (params.lang == this.params.lang && params.options == this.params.options)
        return false;
    this.params.lang = params.lang;
    this.params.options = params.options;
    this.dialog.setLang(params.lang);
    this.saveParams();
    return true
}

Speller.prototype.saveParams = function() {
    this.args.lang = this.params.lang;
    this.args.options = this.params.options;
    util.setCookies("yandex.spell", this.params);
}

//////////////////////////////////////////////////////////////////////////////
//
// SpellSession
//

function SpellSession(listener, ctrls) {
    this.listener = listener;
    this.url = Speller.URL;
    this.protocol = this.url.indexOf(".js") > 0 ? "json" : "xml";
    this.textDoc = new TextDoc(ctrls);
    this.ignoreDic = new SpellDic();
    this.changeDic = new SpellDic();
    this.userDic = null;
    this.resetErrors();
    this.params = { lang: "ru", options: 0 };
}

SpellSession.prototype.setParams = function(params) {
    this.params.lang = params.lang;
    this.params.options = params.options;
}

SpellSession.prototype.setUserDic = function(dic) {
    this.userDic = dic;
}

SpellSession.prototype.start = function(cmd) {
    var ref = this;
    if (cmd == Speller.START) {
        this.ignoreDic.clear();
        this.changeDic.clear();
        this.resetErrors();
    }
    else {
        var e = this.getError();
        if (e) {
            this.errors = this.errors.slice(0, this.errorIndex);
            this.docSel.endDoc = e.docIndex;
            this.docSel.endPos = e.pos;
        }
    }
    this.checkText(this.textDoc.getTexts(this.docSel),
        function(results) { ref.completeCheck(results); });
}

SpellSession.prototype.completeCheck = function(results) {
    this.listener.stateChanged(true);
    for (var docIndex = 0; docIndex < results.length; ++docIndex) {
        var errors = results[docIndex];
        for (var i = 0; i < errors.length; ++i) {
            var e = errors[i];
            if (docIndex == 0)
                e.pos += this.docSel.startPos;
            e.docIndex = docIndex + this.docSel.startDoc;
            if (e.code == Speller.TOO_MANY_ERRORS) {
                this.docSel.endDoc = e.docIndex;
                this.docSel.endPos = e.pos;
                break;
            }
            e.r = { docIndex: e.docIndex, pos: e.pos, text: e.origWord };
            this.errors.push(e);
        }
    }
    this.nextError(0);
}

SpellSession.prototype.checkWord = function(word, callback) {
    var ref = this;
    this.checkText([ word ],
        function(results) {
            ref.listener.stateChanged(true);
            callback(results[0].length == 0);
        });
}

SpellSession.prototype.ignore = function(all) {
    var error = this.getError();
    if (!error)
        return;
    if (all) {
        var dic = this.ignoreDic;
        error.undo = function() { dic.remove(error.word); }
        this.ignoreDic.add(error.word);
    }
    this.nextError(1);
}

SpellSession.prototype.change = function(all, suggest) {
    var error = this.getError();
    if (!this.changeText(error.r, suggest))
        return false;
    if (all) {
        var dic = this.changeDic;
        error.undo = function() { dic.remove(error.word); }
        this.changeDic.add(error.word, suggest);
    }
    this.nextError(1);
    return true;
}

SpellSession.prototype.addToDic = function() {
    var error = this.getError();
    if (!this.userDic || !error || error.code == Speller.REPEAT_WORD)
        return false;
    var dic = this.userDic;
    dic.add(error.word);
    dic.save();
    error.undo = function() { dic.remove(error.word); dic.save(); }
    this.nextError(1);
    return true;
}

SpellSession.prototype.undo = function() {
    while (this.errorIndex > 0) {
        var error = this.errors[this.errorIndex - 1];
        var word = error.word;
        if (word != error.r.text) {
            if (!this.changeText(error.r, word))
                return false;
        }
        --this.errorIndex;
        if (error.undo) {
            error.undo();
            error.undo = null;
        }
        if (this.ignoreDic.check(word) || this.changeDic.check(word))
            continue;
        break;
    }

    var error = this.getError();
    this.textDoc.select(error.r);
    this.listener.errorFound();
}

SpellSession.prototype.canUndo = function() {
    return this.errorIndex > 0;
}

SpellSession.prototype.getError = function() {
    if (this.errorIndex >= this.errors.length)
        return null;
    return this.errors[this.errorIndex];
}

SpellSession.prototype.nextError = function(skip) {
    this.errorIndex += skip;
    for (; this.errorIndex < this.errors.length; ++this.errorIndex) {
        var error = this.errors[this.errorIndex];
        var isRepeat = (error.code == Speller.REPEAT_WORD);
        if (this.ignoreDic.check(error.word))
            continue;
        if (this.changeDic.check(error.word)) {
            var change = this.changeDic.getChange(error.word);
            if (this.changeText(error.r, change))
                continue;
        }
        if (!isRepeat && this.userDic && this.userDic.check(error.word))
            continue;
        this.textDoc.select(error.r);
        this.listener.errorFound();
        return;
    }

    if (this.docSel.endDoc < this.textDoc.length) {
        this.start(Speller.CONTINUE);
        return;
    }

    this.listener.checkCompleted();
}

SpellSession.prototype.resetErrors = function() {
    this.errors = [];
    this.errorIndex = 0;
    this.docSel = { startDoc: 0, startPos: 0, endDoc: 0, endPos: 0 };
}

SpellSession.prototype.changeText = function(r, text) {
    if (!this.textDoc.change(r, text))
        return false;
    this.updateRanges(r, text);
    return true;
}

SpellSession.prototype.updateRanges = function(r, text) {
    var diff = text.length - r.text.length;
    for (var i = 0; i < this.errors.length; ++i) {
        var e = this.errors[i].r;
        if (e.docIndex != r.docIndex)
            continue;
        if (e.pos > r.pos)
            e.pos += diff;
        if (e.pos == r.pos)
            e.text = text;
    }
    if (this.docSel.endDoc == r.docIndex)
        this.docSel.endPos += diff;
}

SpellSession.prototype.parseResult = function(xml) {
    var results = [];
    var resultNodes = xml.getElementsByTagName("SpellResult");
    for (var i = 0; i < resultNodes.length; ++i) {
        var errors = [];
        var resultNode = resultNodes[i];
        var errorNodes = resultNode.getElementsByTagName("error");
        for (var j = 0; j < errorNodes.length; ++j) {
            var errorNode = errorNodes[j];
            var error = {
                code: parseInt(errorNode.getAttribute("code")),
                pos:  parseInt(errorNode.getAttribute("pos")),
                len:  parseInt(errorNode.getAttribute("len")),
                word: errorNode.getElementsByTagName("word")[0].firstChild.nodeValue
            };
            var sNodes = errorNode.getElementsByTagName("s");
            var s = [];
            for (var k = 0; k < sNodes.length; ++k)
                s.push(sNodes[k].firstChild.nodeValue);
            error.s = s;
            errors.push(error);
        }
        results.push(errors);
    }
    return results;
}

SpellSession.prototype.onResponse = function(query, result, error) {
    if (error) {
        this.listener.onError(error);
        return;
    }
    if (this.protocol == "xml")
        result = this.parseResult(result.responseXML);
    this.setOrigWords(result, query.args.text);
    query.complete(result);
}

SpellSession.prototype.checkText = function(text, complete) {
    var ref = this;
    var lang = this.params.lang;
    var options = this.params.options;
    if (lang == "en")
        options &= ~Speller.LATIN_OPTIONS;
    else if ((options & Speller.LATIN_OPTIONS) == 0)
        lang += ";en"

    this.listener.stateChanged(false);
    var args = { lang: lang, options: options, text: text };
    var query = { complete: complete, args: args, url: this.url + "/checkTexts",
        callback: function(result, error) { ref.onResponse(query, result, error) }};
    if (this.protocol == "json") {
        json.sendQuery(query);
    }
    else {
        query.method = "POST";
        ajax.sendQuery(query);
    }
}

SpellSession.prototype.setOrigWords = function(results, text) {
    for (var i = 0; i < results.length; ++i) {
        var t = text[i].split("\n");
        var errors = results[i];
        for (var j = 0; j < errors.length; ++j) {
            var e = errors[j];
            e.origWord = t[e.row].substr(e.col, e.len);
        }
    }
}

//////////////////////////////////////////////////////////////////////////////
//
// TextDoc
//

function TextDoc(ctrls) {
    this.ctrls = [];
    for (var i = 0; i < ctrls.length; ++i)
        this.ctrls[i] = ctrls[i];
    this.length = this.ctrls.length;
}

TextDoc.prototype.getTexts = function(docSel) {
    var texts = [], textLen = 0, maxLen = Speller.MAX_TEXT_LEN;
    docSel.startDoc = docSel.endDoc;
    docSel.startPos = docSel.endPos;
    for (var i = docSel.startDoc; i < this.ctrls.length; ++i) {
        var text = "", len = 0;
        for (;;) {
            text = this.ctrls[i].value.substr(docSel.endPos);
            len = text.length;
            if (textLen + len <= maxLen)
                break;
            if (textLen > 0)
                return texts;
            var seps = "\n \t|,;";
            var leftPos = 0, rightPos = text.length;
            for (var j = 0; j < seps.length; ++j) {
                var s = seps.charAt(j);
                if (s == "|") {
                    if (leftPos > 0)
                        break;
                    continue;
                }
                leftPos = Math.max(leftPos, (s + text).lastIndexOf(s, maxLen));
                rightPos = Math.min(rightPos, (text + s).indexOf(s));
            }
            if ((len = leftPos) > 0)
                break;
            docSel.startPos = (docSel.endPos += rightPos);
        }
        docSel.endDoc = i; docSel.endPos += len;
        if (len == 0 && texts.length == 0) { // Not to insert "" as 1st element
            docSel.startDoc = docSel.endDoc = i + 1;
            docSel.startPos = docSel.endPos = 0;
            continue;
        }
        texts.push(text.substr(0, len)); textLen += len;
        if (len != text.length)
            break;
        docSel.endDoc = i + 1; docSel.endPos = 0;
    }
    return texts;
}

TextDoc.prototype.select = function(pos) {
    var ctrl = this.ctrls[pos.docIndex];
    var range = TextDoc.createRange(ctrl);
    if (!range.select(pos))
        return null;
    return range;
}

TextDoc.prototype.change = function(pos, changeTo) {
    var r = this.select(pos);
    if (!r)
        return false;
    r.setText(changeTo);
    return true;
}

TextDoc.prototype.getHtmlFragment = function(pos, fragmentLen) {
    fragmentLen = fragmentLen || 150;
    var endPos = pos.pos + pos.text.length;
    var text = this.ctrls[pos.docIndex].value;
    var leftPos = text.lastIndexOf("\n", pos.pos) + 1;
    var rightPos = (text + "\n").indexOf("\n", endPos);
    var selected = text.substring(pos.pos, endPos);
    var left = text.substring(leftPos, pos.pos);
    var right = text.substring(endPos, rightPos);
    return util.htmlEncode(TextDoc.truncStr(left, 70, -1))
        + "<WBR>" + util.htmlEncode(selected).fontcolor("red").bold()
        + util.htmlEncode(TextDoc.truncStr(right, 200));
}

TextDoc.createRange = function(ctrl) {
    if (ctrl.createTextRange)
        return new TextRange(ctrl);
    return new CtrlRange(ctrl);
}

TextDoc.truncStr = function(str, len, dir) {
    if (str.length <= len)
        return str;
    if (dir && dir < 0) {
        var pos = str.lastIndexOf(" ", str.length - len);
        if (pos >= 0)
            str = "..." + str.substr(pos);
    }
    else {
        var pos = str.indexOf(" ", len);
        if (pos >= 0)
            str = str.substr(0, pos + 1) + "...";
    }
    return str;
}

function TextRange(ctrl) {
    this.ctrl = ctrl;
    this.r = null;
}

TextRange.prototype.select = function(pos) {
    var r = this.ctrl.createTextRange();
    var text = this.ctrl.value + "\n", off = 0;
    while (off < pos.pos) {
        var nextOff = text.indexOf("\n", off);
        if (nextOff >= pos.pos)
            break;
        var len = nextOff - off;
        if (len > 0 && text.substr(nextOff - 1, 1) == "\r")
            --len;
        r.move("character", len);
        r.moveEnd("character", 1);
        if (r.text == "\r")
            r.moveEnd("character", 1);
        r.collapse(false);
        off = nextOff + 1;
    }
    r.move("character", pos.pos - off);
    r.moveEnd("character", pos.text.length);
    if (r.text != pos.text)
        return false;
    r.select();
    this.r = r;
    return true;
}

TextRange.prototype.setText = function(text) {
    this.r.text = text;
}

function CtrlRange(ctrl) {
    this.ctrl = ctrl;
}

CtrlRange.prototype.select = function(pos) {
    var valueText = this.ctrl.value.substr(pos.pos, pos.text.length);
    if (valueText != pos.text)
        return false;
    this.pos = pos.pos;
    this.len = pos.text.length;
    return true;
}

CtrlRange.prototype.setText = function(text) {
    var v = this.ctrl.value;
    this.ctrl.value = v.substr(0, this.pos) + text + v.substr(this.pos + this.len);
}

//////////////////////////////////////////////////////////////////////////////
//
// SpellOptDialog
//

function SpellOptDialog() {
    this.args = { lang: "ru", options: 0 };
    if (window.dialogArguments)
        this.args = window.dialogArguments;
    else if (window.opener && window.opener.theDlgArgs)
        this.args = window.opener.theDlgArgs;
}

SpellOptDialog.OPTIONS = [
    [ "ignoreUppercase", 0x0001, -1 ],
    [ "ignoreDigits",    0x0002, -1 ],
    [ "ignoreUrls",      0x0004, -1 ],
    [ "findRepeat",      0x0008, -1 ],
    [ "latin",           0x0010,  0 ],
    [ "latin",           0x0080,  1 ]
];

SpellOptDialog.prototype.init = function() {
    var ref = this;
    window.oncontextmenu = function() { return false; }
    this.form = document.forms["form"];
    this.form.userDic.onclick = function() { ref.userDicDialog(); }
    this.form.userDic.disabled = !util.localStorage();
    this.form.ok.onclick = function() { ref.onOk(); window.close(); }
    this.form.cancel.onclick = function() { window.close(); }
    this.form.langList.onchange = function() { ref.updateUI(); }
    for (var i = 0; i < 3; ++i)
        this.form.latin[i].onclick = function() { ref.updateUI(); return true; }
    this.initParams();
    this.setLang(this.params.lang);
    this.setOptions(this.params.options);
    if (typeof(this.args.recheck) != "boolean") {
        document.getElementById("recheck").style.visibility = "hidden";
        document.getElementById("labelRecheck").style.visibility = "hidden";
    }
}

function s(value) {
    return typeof(value) == "undefined" ? "" : value.toString();
}

SpellOptDialog.prototype.initParams = function() {
    var cookies = util.getCookies("yandex.spell"), a = this.args;
    this.params = {};
    this.params.lang = a.lang || cookies.lang || a.defLang;
    this.params.options = parseInt(s(a.options) || s(cookies.options) || s(a.defOptions) || "0");
}

SpellOptDialog.prototype.setOptions = function(options) {
    this.form["latin"][2].checked = true;
    for (var i = 0; i < SpellOptDialog.OPTIONS.length; ++i) {
        var o = SpellOptDialog.OPTIONS[i], name = o[0], value = o[1];
        var ctrl = this.form[name];
        if (o[2] >= 0)
            ctrl = ctrl[o[2]];
        ctrl.checked = ((options & value) != 0);
    }
    this.updateUI();
}

SpellOptDialog.prototype.setLang = function(lang) {
    util.selectItem(this.form.langList, lang);
    this.updateUI();
}

SpellOptDialog.prototype.getOptions = function() {
    var options = 0;
    for (var i = 0; i < SpellOptDialog.OPTIONS.length; ++i) {
        var o = SpellOptDialog.OPTIONS[i], name = o[0], value = o[1];
        var ctrl = this.form[name];
        if (o[2] >= 0)
            ctrl = ctrl[o[2]];
        if (ctrl.checked)
            options |= value;
    }
    return options;
}

SpellOptDialog.prototype.getLang = function() {
    var langList = this.form.langList;
    if (langList.selectedIndex < 0)
        return "";
    return langList.options[langList.selectedIndex].value;
}

SpellOptDialog.prototype.updateUI = function() {
    var lang = this.getLang();
    this.enableGroup("groupLatin", lang != "en");
    this.form.latinLang.disabled = (lang == "en" || !this.form.latin[2].checked);
}

SpellOptDialog.prototype.enableGroup = function(id, enable) {
    var groupRoot = document.getElementById(id);
    var tags = ["INPUT", "SELECT", "LABEL"];
    for (var i = 0; i < tags.length; ++i) {
        var ctrls = groupRoot.getElementsByTagName(tags[i]);
        for (var j = 0; j < ctrls.length; ++j)
            ctrls[j].disabled = !enable;
    }
}

SpellOptDialog.prototype.onOk = function() {
    this.args.lang = this.getLang();
    this.args.options = this.getOptions();
    this.args.recheck = this.form["recheck"].checked;
    util.setCookies("yandex.spell", this.params);
}

SpellOptDialog.prototype.userDicDialog = function() {
    util.modalDialog("userdic.html", this.args.userDicDlg, {});
}

function UserDicDialog() {
    this.userDic = new SpellDic("yandex.userdic");
    this.dicContent = this.userDic.getContent();
    this.closed = false;
}

UserDicDialog.prototype.init = function() {
    var ref = this;
    this.form = document.forms["form"];
    this.form.dic.value = this.dicContent;
    this.form.ok.onclick = function() { ref.end(1); window.close(); }
    window.onbeforeunload = function() { ref.end(-1); }
    window.onunload = function() { ref.end(-1); }
    window.oncontextmenu = function() { return false; }
}

UserDicDialog.prototype.end = function(save) {
    if (this.closed)
        return;
    var dicValue = this.form.dic.value.replace(/\r/g, "");
    if (this.dicContent == dicValue)
        save = 0;
    if (save < 0) {
        var msg = document.getElementById("saveDic").innerHTML;
        save = window.confirm(msg) ? 1 : 0;
    }
    if (save) {
        this.userDic.setContent(dicValue);
        this.userDic.save();
    }
    this.closed = true;
}
