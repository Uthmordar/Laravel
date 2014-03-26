<?php

class HelperBlog {

    protected static $className = "active";

    public static function isHome() {
        if (Request::is('/'))
            return "class=" . self::$className;
    }

    public static function isAdmin($name) {
        if (Request::is('admin/*' && Request::segment(2) == $name))
            return "class=" . self::$className;
    }

    public static function isCat($id) {
        if (Request::is('cat/*') && Request::segment(2) == $id)
            return "class=" . self::$className;
    }

    public static function isSectionDash() {
        if (Request::is('admin/*/dashSection'))
            return self::$className;
    }

    public static function isSection($id) {
        if (Request::is('admin/*/dashSection') && Request::segment(2) == $id)
            return "class=" . self::$className;
    }

    public static function isPage($name) {
        if (Request::is('page/*') && Request::segment(2) == $name)
            return "class=" . self::$className;
    }

    public static function getGravatar($email) {
        $gravatar = "http://www.gravatar.com/avatar/";
        $gravatar.=MD5(strtolower(trim($email)));
        return "<img src='$gravatar' alt='gravatar'/>";
    }

    public static function patternReg($pattern) {
        $array = explode(';', trim($pattern, ';'));
        $data = [];
        foreach ($array as $k) {
            $key = explode(':', $k);
            $data[$key[0]] = $key[1];
        }
        return $data;
    }

}

?>