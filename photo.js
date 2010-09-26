// The client will do several things:
// 1. Send "like" and "comment" requests
// 2. Keep track of who the user is with a cookie (no auth)
// 3. Replace dates with the correct time-zone??? (fuzzy time?)

window.onload = function() {
  var commentDefault = "Write a comment...";
  var sendAjaxRequest=function(url, handler) {
    if (window.XMLHttpRequest) { // IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
    } else { // IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
          if (handler) handler(xmlhttp.responseText);
      }
    }
    xmlhttp.open("GET",url,true);
    xmlhttp.send();
  };
  var cookie={create:function(name, value, days) {
    if (days) {
      var date = new Date();
      date.setTime(date.getTime()+(days*24*60*60*1000));
      var expires = "; expires="+date.toGMTString();
    } else var expires = "";
    document.cookie = name+"="+value+expires+"; path=/";
  },read:function(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0)
        return c.substring(nameEQ.length,c.length);
    }
    return null;
  },erase:function(name) {
    cookie.create(name,"",-1);
  }};

  var elts={info:document.getElementById("info"),
    commentLink:document.getElementById("a-comment"),
    likeLink:document.getElementById("a-like"),
    signoutLink:document.getElementById("a-signout"),
    signoutSpan:document.getElementById("signout"),
    loginDiv:document.getElementById("login"),
    nameField:document.getElementById("name"),
    emailField:document.getElementById("email"),
    websiteField:document.getElementById("website"),
    likeButton:document.getElementById("submit-like"),
    commentForm:document.getElementById("comment-form"),
    commentArea:document.getElementById("comment-text"),
    commentButton:document.getElementById("submit-comment")};

  var loggedIn=function(){return cookie.read("logged")!=null;};
  var enc=encodeURIComponent; // We double-encode for PHP's sake
  var logIn=function(){
      cookie.create("logged", enc(enc(elts.nameField.value))
                  + "&" + enc(enc(elts.emailField.value))
                  + "&" + enc(enc(elts.websiteField.value)), 1000);
  };
  var hide=function(element){element.style['display']="none";};
  var show=function(element){element.style['display']="inline";};
  var checkButtons=function() {
    // does some complicated checks to see whether to enable things.
    if (loggedIn() || (elts.nameField.value
                       && elts.emailField.value)) {
      elts.likeButton.disabled = false;
      if (elts.commentArea.value &&
          elts.commentArea.value != commentDefault) {
        elts.commentButton.disabled = false;
      } else {
        elts.commentButton.disabled = true;
      }
    } else {
      elts.likeButton.disabled = true;
      elts.commentButton.disabled = true;
    }  
  };
  var showLogin=function(){show(elts.loginDiv);};
  var tryLogin=function() {
    checkButtons();
    if (!elts.likeButton.disabled && elts.loginDiv.style['display']=='inline') {
      logIn();
    }
  };
  var postCallback=function(s) {
    if (s=='OK') window.location.reload();
    else alert(s);
  }
  var like=function() {
    tryLogin();
    if (!loggedIn()) {
      elts.likeButton.disabled = true;
      show(elts.likeButton);
      showLogin();
    } else {
      sendAjaxRequest("?like=" + enc(enc(elts.info.value)),
                      postCallback);
    }
  };
  var comment=function() {
    tryLogin();
    if (!loggedIn()) {
      elts.commentButton.disabled = true;
      showLogin();
    } else if (elts.commentArea.value &&
               elts.commentArea.value != commentDefault) {
      sendAjaxRequest("?comment=" + enc(enc(elts.info.value))
                      + "&text=" + enc(enc(elts.commentArea.value)),
                      postCallback);
    } else {
      elts.commentButton.disabled = true;
    }
  }
  var hideLike=function(){hide(elts.likeButton);hide(elts.loginDiv);};
  var startComment=function() {
    tryLogin();
    if (!loggedIn()) {
      elts.commentButton.disabled = true;
      showLogin();
      elts.nameField.focus();
    } else {
      elts.commentArea.focus();
    }
    show(elts.commentButton);
    if (elts.commentArea.value==commentDefault) {
      elts.commentArea.value='';
      elts.commentArea.style['color']='black';
    };
  };
  var stopComment=function() {
    if (elts.commentArea.value=='') {
      elts.commentArea.value=commentDefault;
      elts.commentArea.style['color']='#555';
    }
  };
  var hideComment=function(){hide(elts.commentButton);hide(elts.loginDiv);};

  elts.commentArea.style['color']='#555';
  if (loggedIn()) show(elts.signoutSpan);
  elts.commentLink.onclick = startComment;
  elts.likeLink.onclick = like;
  elts.likeButton.onclick = like;
  elts.signoutLink.onclick =
    function(){cookie.erase("logged");hide(elts.signoutSpan);};
  elts.nameField.onkeypress = checkButtons;
  elts.emailField.onkeypress = checkButtons;
  elts.commentButton.onclick = comment;
  elts.commentArea.onfocus = startComment;
  elts.commentArea.onblur = stopComment;
  elts.commentArea.onkeypress = checkButtons;
};
