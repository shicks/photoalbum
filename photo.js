// The client will do several things:
// 1. Send "like" and "comment" requests
// 2. Keep track of who the user is with a cookie (no auth)
// 3. Replace dates with the correct time-zone

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
    value = value.replace(/&/g,'&a').replace(/;/g,'&s');
    document.cookie = name+"="+value+expires+"; path=/";
  },read:function(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
      var c = ca[i];
      while (c.charAt(0)==' ') c = c.substring(1,c.length);
      if (c.indexOf(nameEQ) == 0)
        return c.substring(nameEQ.length,c.length)
            .replace(/&s/g,';').replace(/&a/g,'&');
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
    commentButtonDiv:document.getElementById("comment-button"),
    commentButton:document.getElementById("submit-comment")};

  var loggedIn=function(){return cookie.read("logged")!=null;};
  var colonEscape=function(s){
      return s.replace(/&/g, "&amp;").replace(/:/g, "&#58;");
  };
  var logIn=function(){
    cookie.create("logged", colonEscape(elts.nameField.value)
                  + ":" + colonEscape(elts.emailField.value)
                  + ":" + colonEscape(elts.websiteField.value), 1000);
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
  var like=function() {
    tryLogin();
    if (!loggedIn()) {
      elts.likeButton.disabled = true;
      show(elts.likeButton);
      showLogin();
    } else {
      sendAjaxRequest("?like=" + elts.info.value, function(s){window.location.reload();});
    }
  };
  var comment=function() {
    tryLogin();
    if (!loggedIn()) {
      elts.commentButton.disabled = true;
      showLogin();
    } else if (elts.commentArea.value &&
               elts.commentArea.value != commentDefault) {
      sendAjaxRequest("?comment=" + elts.info.value + "&text="
                      + escape(elts.commentArea.value), function(s){alert(s);window.location.reload();});
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
    show(elts.commentButtonDiv);
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
  var hideComment=function(){hide(elts.commentButtonDiv);hide(elts.loginDiv);};

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
