<?php

class HelperImage {

    protected static $MIME = array('image/jpg', 'image/jpeg', 'image/png', 'image/gif');

    public static function thumb($file, $thumbX, $thumbY, $path) {
        //var_dump($file); die();
        $fileTrueName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        // $file=$file['tmp_name'];

        $size = getimagesize($file);
        $x = $size[0];
        $y = $size[1];

        $mime = $size['mime'];

        if (!in_array($mime, HelperImage::$MIME)) {
            return false;
        }

        /* if(count(scandir(url('uploads/_min')))>200){
          return false;
          } */

        $thumb = imagecreatetruecolor($thumbX, $thumbY);


        if ($mime == HelperImage::$MIME[0]) {

            $img = imagecreatefromjpg($file);
            $copy = imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbX, $thumbY, $x, $y);

            header('Content-Type : ' . HelperImage::$MIME[0]);

            imagejpg($thumb);
            imagejpg($thumb, $path);

            imagedestroy($img);
            imagedestroy($thumb);

            return true;
        } else if ($mime == HelperImage::$MIME[1]) {

            $img = imagecreatefromjpeg($file);
            $copy = imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbX, $thumbY, $x, $y);

            header('Content-Type : ' . HelperImage::$MIME[1]);

            imagejpeg($thumb);
            imagejpeg($thumb, $path);

            imagedestroy($img);
            imagedestroy($thumb);

            return true;
        } else if ($mime == HelperImage::$MIME[2]) {

            $img = imagecreatefrompng($file);
            $copy = imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbX, $thumbY, $x, $y);

            header('Content-Type : ' . HelperImage::$MIME[2]);

            imagepng($thumb);
            imagepng($thumb, $path);

            imagedestroy($img);
            imagedestroy($thumb);

            return true;
        } else if ($mime == HelperImage::$MIME[3]) {

            $img = imagecreatefromgif($file);
            $copy = imagecopyresampled($thumb, $img, 0, 0, 0, 0, $thumbX, $thumbY, $x, $y);

            header('Content-Type : ' . HelperImage::$MIME[3]);

            imagegif($thumb);
            imagegif($thumb, $path);

            imagedestroy($img);
            imagedestroy($thumb);

            return true;
        }
    }

}
