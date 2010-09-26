<?php

# We have four modes:
#   - gallery list
#   - gallery page
#   - photo
#   - comment

# sub modes - login, logout

# There's also stuff to do with a login cookie.
# We'll set that on the first opportunity and
# then keep it forever... (until signout, anyway)

# We also need some javascript for showing and
# hiding the comment box, etc.

# URL scheme:
#   ?a=<album> - album view
#   &pg=<page> - page view
#   &p=<photo> - photo view

# Directory structure:
#   a_<alb>/<photo>.jpg = full-sized
#   a_<alb>/NAME = metadata
#   a_<alb>/COVER = name of cover photo (thumbnail)
#   a_<alb>/m/m_<photo>.jpg = mid-sized
#   a_<alb>/t/t_<photo>.jpg = thumbnail
#   c/<alb>_<photo>.comment = comments
#   c/<alb>_<photo>.desc = photo description (same fmt as comments, uses 1st)

ini_set("magic_quotes_gpc", "off");
ini_set("magic_quotes_sybase", "off");
$GLOBALS['PERPAGE'] = 50;
$GLOBALS['PERROW'] = 5;

$GLOBALS['WRITE_DESCRIPTION'] = false;

function printHeader($title, $class) {
  print "<html><head><title>$title</title>\n"
    . "<link rel='stylesheet' type='text/css' href='photo.css'/>\n"
    . "<script type='text/javascript' src='photo.js'></script>\n"
    . "</head><body class='$class'><div class='page'>\n";
}

function listAlbums() {
  printHeader("Albums", "gallery");
  print "<h1>Albums</h1><div class='main'>\n";
  $dir = opendir(".");
  $gal = array();
  while ($f = readdir($dir)) {
    if (strpos($f, "a_") === 0 and is_dir($f)) {
      $album = substr($f, 2);
      $description = getAlbumDescription($album);
      $cover = file_get_contents("$f/COVER");
      $thumb = "$f/" . thumbnail($cover);
      $gal[$album] = "<div class='row'><a href='?a=$album'><div "
         . "class='album-cover'><img src='$thumb'/></div><div "
         . "class='album-title'>$description</div></a></div>";
    }
  }
  ksort($gal);
  foreach ($gal as $album => $html) {
    print "$html\n";
  }
  print "<div style='clear:both'></div>\n";
  print "</div><div class='copyright'>Photo gallery software &copy; "
    . "2010 Stephen Hicks.  All rights reserved.</div></div></body></html>";
}

function getAlbumDescription($album) {
  return file_get_contents("a_$album/NAME");
}

function getPhotoDescription($album, $photo) {
  $descFile = "c/${album}_$photo.desc";
  if (!file_exists($descFile)) {
    return "";
  }
  $lines = file($descFile);
  $line = $lines[0];
  $parts = explode("&", trim($line));
  if ($parts[0] != "comment") {
    return "";
  }
  return str_replace("\n", "<br/>", urldecode($parts[5]));
}

function getPhotoList($album) {
  $dir = opendir("a_$album");
  $photos = array();
  while ($f = readdir($dir)) {
    if (strpos($f, ".jpg") !== false || strpos($f, ".mp4") !== false) {
      array_push($photos, $f);
    }
  }
  sort($photos);
  return $photos;
}

function listPhotos() {
  global $PERPAGE, $PERROW;
  $alb = $_REQUEST['a'];
  $page = $_REQUEST['pg'];
  $description = getAlbumDescription($alb);
  $photos = getPhotoList($alb);
  $total = count($photos);
  $pages = floor(($total - 1) / $PERPAGE) + 1;
  printHeader("Album: $description", "album");
  print "<input type='hidden' id='info' value='${alb}'>\n";
  print "<h1>$description</h1><div class='main'><div class='links top'>\n";
  paginate($alb, $page, $pages);
  print "<a href='?'>All Albums</a></div><div style='clear:both'></div>\n";
  for ($i = $page * $PERPAGE;
      $i < ($page + 1) * $PERPAGE and $i < $total;
      $i++) {
    if ($i % $PERROW == 0) {
      print "<div class='row'>\n";
    }
    $photo = $photos[$i];
    $thumbnail = thumbnail($photo);
    print "<div class='item'>"
      . "<a href='?a=$alb&p=$photo'><img class='thumbnail' "
      . "src='a_$alb/$thumbnail'/></a></div>\n";
    if ($i % $PERROW == $PERROW - 1) {
      print "</div>\n";
    }
  }
  print "<div class='links bottom'>";
  paginate($alb, $page, $pages);
  print "</div>\n";
  showComments($alb);
  print "</div>\n";
  print "</div></div><div class='copyright'>Photo gallery software &copy; "
    . "2010 Stephen Hicks.  All rights reserved.</div></body></html>";
}

function paginate($alb, $page, $pages) {
  if ($pages > 1) {
    print "<div class='right'>";
    if ($page != 0) {
      $p = $page - 1;
      print "<a href='?a=$alb&pg=$p'>Previous</a> ";
    }
    for ($p = 0; $p < $pages; $p++) {
      $pp = $p + 1;
      if ($p == $page) {
        print "<b>$pp</b> ";
      } else {
        print "<a href='?a=$alb&pg=$p'>$pp</a> ";
      }
    } 
    if ($page < $pages - 1) {
      $p = $page + 1;
      print "<a href='?a=$alb&pg=$p'>Next</a>";
    }
    print "</div>\n";
  }
}

function thumbnail($photo) {
  return "t/t_" . str_replace(".mp4", ".jpg", $photo);
}

function embed($album, $photo, $next) {
  if (strpos($photo, ".jpg") !== false) { # picture
    print "<div class='img'><a href='?a=$album&p=$next'>"
    . "<img src='a_$album/m/m_$photo'></a></div>\n";
  } else if (strpos($photo, ".mp4") !== false) { # video
    $flv = str_replace(".mp4", ".flv", $photo);
    embedVideo("a_$album/m/m_$flv");
  }
}

function showPhoto() {
  $alb = $_REQUEST['a'];
  $photo = $_REQUEST['p'];
  $description = getAlbumDescription($alb);
  $photos = getPhotoList($alb);
  $ind = array_search($photo, $photos);
  if ($ind === false) {
    printError("not found.");
    return;
  }
    
  printHeader("${alb}_$photo", "photo");
  print "<input type='hidden' id='info' value='${alb}_$photo'>\n";
  print "<h1><a href='?a=$alb'>$description</a></h1><div class='main'>"
    . "<div class='links'>\n";
  $total = count($photos);
  $prev = $photos[($ind + $total - 1) % $total];
  $next = $photos[($ind + 1) % $total];
  print "<div class='right'><a href='?a=$alb&p=$prev'>Previous</a>"
    . "&nbsp;&nbsp;<a href='?a=$alb&p=$next'>Next</a></div>";
  $oneind = $ind + 1;
  $page = floor($ind / $GLOBALS['PERPAGE']);
  print "Photo $oneind of $total&nbsp&nbsp"
    . "<a href='a_$alb/$photo'>Full Size</a> - "
    . "<a href='?a=$alb&pg=$page'>Back to Album</a> - "
    . "<a href='?'>All Albums</a></div>\n"; # </photo-links>
  embed($alb, $photo, $next);
  $desc = getPhotoDescription($alb, $photo);
  if ($desc) {
    print "<div class='description'>$desc</div>\n";
  }
  # load comments
  showComments("${alb}_$photo");
  print "</div></div><div class='copyright'>Photo gallery software &copy; "
    . "2010 Stephen Hicks.  All rights reserved.</div></body></html>";
}

function showComments($id) {
  $commentsFile = "c/$id.comment";
  $lines = file_exists($commentsFile) ? file($commentsFile) : array();
  $likes = array();
  $comments = array();
  foreach ($lines as $line) {
    $parts = explode("&", trim($line));
    $name = urldecode($parts[1]);
    # $email = $parts[2];   # also escape \n as &lb;, then replace w/ <br/>
    $href = urldecode($parts[3]);
    if ($href) {
      if (strpos($href, "http://") !== 0) {
        $href = "http://$href";
      }
      $name = "<a href='$href'>$name</a>";
    }
    if ($parts[0] == 'like') {
      array_push($likes, $name);
    } else if ($parts[0] == 'comment') {
      $time = $parts[4];
      $year = $time < time() - 320 * 86400 ? ", Y" : "";
      $date = date("F j$year \\a\\t g:ia", $time); # use JS too...?
      $text = str_replace("\n", "<br/>", urldecode($parts[5]));
      $comment = "<div class='wrapper'><div class='comment'>\n"
         . "<div class='person'>$name</div>&nbsp;"
         . "<div class='text'>$text</div><div class='date'>$date"
         . "</div></div></div>\n";
      array_push($comments, $comment);
    } else {
      # weird...?
    }
  }
  print "<div class='comments'><div class='comments-links'>"
    . "<a id='a-comment' href='javascript:;'>Comment</a> - "
    . "<a id='a-like' href='javascript:;'>Like</a><span id='signout' "
    . "style='display:none'> - <a id='a-signout' href='javascript:;'"
    . ">Signout</a></span></div>\n";
  $likers = count($likes);
  if ($likers > 0) {
    print "<div class='wrapper'><div class='likes'>";
    if ($likers == 1) {
      print "<div class='person'>$likes[0]</div> likes this.";
    } else if ($likers == 2) {
      print "<div class='person'>$likes[0]</div> and "
        . "<div class='person'>$likes[1]</div> like this.";
    } else {
      print "<div class='person'>$likes[0]</div>";
      for ($i = 1; $i < $likers - 1; $i++) {
        print ", <div class='person'>$likes[$i]</div>";
      }
      $last = $likes[$likers - 1];
      print " and <div class='person'>$last</div> like this.";
    }
    print "</div></div>\n";
  }
  print "<div class='comments-comments'>\n";
  foreach ($comments as $comment) {
    print "$comment\n";
  }
  ?><div class='wrapper'><div class='comments-write'>
<div id='login' style='display:none' class='login'>
Name:&nbsp;<input id='name'/>
Email:&nbsp;<input id='email'/>
Website&nbsp;(optional):&nbsp;<input id='website'/>
<input type='submit' id='submit-like' value='Like' style='display:none'></div>
<textarea
  id='comment-text'>Write a comment...</textarea><input id='submit-comment'
  style='display:none' type='submit' value='Comment'/>
<div style='clear:both'></div>
</div></div></div><?php
}

# We hardcode the size here, too...
function embedVideo($video) {
  $base = "http://" . $_SERVER['HTTP_HOST']
    . preg_replace("|/[^/]*$|", "", $_SERVER['PHP_SELF']);
  $file = "$base/$video";
  $still = str_replace(".flv", ".jpg", $file);
  $player = "$base/mediaplayer.swf";
  print "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' "
    . "codebase='http://download.macromedia.com/pub/shockwave/cabs"
    . "/flash/swflash.cab#version=6,0,40,0' "
    . "width='640' height='380'><param name='flashvars' value='file=$file"
    . "&image=$still'/>"
    . "<param name='movie' value='$player'/><embed src='$player' width='640' "
    . "height='380' type='application/x-shockwave-flash' "
    . "pluginspage='http://www.macromedia.com/go/getflashplayer' "
    . "flashvars='file=$file&image=$still'/></object>";
}

function printError($message) {
    print "<html><head><title>Error</title></head>"
      . "<body>Error: $message</body></html>";
}

function sanitize($s) {
  return urlencode(htmlspecialchars(urldecode($s), ENT_QUOTES));
}

function sanitizeUrl($s) {
  return urlencode(str_replace(array("'", "<", ">"), array("", "", ""),
                               urldecode($s)));
}

function unslash($s) {
  return str_replace("\\'", "'", $s);
}

# Sanitize inputs BEFORE saving to file; trust file not monkeyed with...
function appendComment($type, $text) {
  $cookie = unslash($_COOKIE['logged']); # cf. magic_quotes_gpc (_sybase)
  $parts = explode("&", $cookie);
  $name = sanitize($parts[0]);
  $email = sanitize($parts[1]);
  $website = sanitizeUrl($parts[2]);
  $date = time();
  $text = sanitize(unslash($text));
  $fname = $_REQUEST[$type];
  if ($name and $email and ($type=='like' or $text)) {
    if ($GLOBALS['WRITE_DESCRIPTION']) {
      $f = fopen("c/$_REQUEST[$type].desc", 'w');
    } else {
      $f = fopen("c/$_REQUEST[$type].comment", 'a');
    }
    fwrite($f, "$type&$name&$email&$website&$date&$text\n");
    fclose($f);
    print "OK"; # : <$cookie> <$text>";
  } else {
    print "error: <$cookie> <$text>";
  }
}

if ($_REQUEST['like']) {
  appendComment("like", "");
} else if ($_REQUEST['comment']) {
  appendComment("comment", $_REQUEST['text']);
} else if ($_REQUEST['p']) {
  showPhoto();
} else if ($_REQUEST['a']) {
  listPhotos();
} else {
  listAlbums();
}
