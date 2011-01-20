<?php

# Facebook code
# See  http://xenonxm.blogspot.com/2009/06/
#             photo-upload-to-facebook-from-your-php.html
# for reference.  Also,
#      http://www.easycodingclub.com/facebook-api-tutorials/
#             facebook-php-api-for-photos-upload

function doFacebook() {

  session_start();
  $facebook = new FacebookPhotos($GLOBALS['FB_API_KEY'],
                                 $GLOBALS['FB_SECRET']);

  if ($_REQUEST['fb'] == 'auth') {
    $album = $_SESSION['album'];
    if (!file_exists("a_$album")) {
      print "Please navigate here from the main album page";
      exit(0);
    }
    $desc = getAlbumDescription($album);
    $fb_sig_user = $_REQUEST['fb_sig_user'];

    # First create the album
    $create_album_response = $facebook->api_client->photos_createAlbum(
      $desc, '', '', '', $fb_sig_user);
    $album_link = $create_album_response['link'];
    $aid = $create_album_response['aid'];

    print "Created album <a href='$album_link'>$desc</a><br/>\n";
    flush();

    $photos = getPhotoList($album);
    $num_photos = count($photos);
    print "Found $num_photos photos<br/>\n";
    flush();

    foreach ($photos as $photo) {
      try {
        $file = "a_$album/m/m_$photo";
        $photo_desc = getPhotoDescription($album, $photo);
      
        if (file_exists($file)) {
	  $upload_response = $facebook->api_client->photos_upload(
              $file, $aid, $photo_desc, $fb_sig_user);
          $photo_link = $upload_response['link'];
          print "Uploaded photo <a href='$photo_link'>$photo</a><br/>\n";
          flush();
        } else {
	  print "Skipping nonexistant file $file<br/>\n";
	  flush();
        }
      } catch (Exception $e) {
        print "Caught exception uploading $photo<br/>\n";
	flush();
      }
    }    
    print "Done<br/>";
  } else {
    $_SESSION['album'] = $_REQUEST['fb'];
    $facebook->require_frame();
  }
}

?>