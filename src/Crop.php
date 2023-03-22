<?php

namespace Boris99999\Image;

class Crop
{
    /**
     *  How much can you crop?
     *
     * @param string $direction - top, bottom, left, right
     * @param array <int> $size
     * @param int $colorBackground
     * @param \GdImage $im
     * @return int $rez
     */
    public static function lineCrop(int $colorBackground, array $size, \GdImage $im, string $direction): int|null
    {
        $color=null;
        $rez=0; // сколько срезаем сверху
        for ($h=0; $h<$size[1]; $h++) {
            for ($w=0; $w<$size[0]; $w++) {
                if ($direction=='top') {
                    $deltaW=$w;
                    $deltaH=$h;
                    $rez=$h;
                } elseif ($direction=='bottom') {
                    $deltaW=$w;
                    $deltaH=$size[1]-$h-1;
                    $rez=$h;
                } elseif ($direction=='left') {
                    $deltaW=$w;
                    $deltaH=$h;
                    $rez=$w;
                } elseif ($direction=='right') {
                    $deltaW=$size[0]-$w-1;
                    $deltaH=$h;
                    $rez=$w;
                } else {
                    return null;
                }

                $color=imagecolorat($im, $deltaW, $deltaH);
                if ($color!=$colorBackground) {
                    break;
                }
            }
            if ($color!=$colorBackground) {
                break;
            }
        }
        return $rez;
    }




    /**
     * Change size and save image.
     *
     * @param string $sourcePatch source patch
     * @param string $urlNew destination patch
     * @param int $width new image width
     * @param int $height new image height
     * @return bool
     */
    public static function makeimage(string $sourcePatch, string $urlNew, int $width, int $height): bool
    {
        $mime =	mime_content_type($sourcePatch);

        if ($mime=='image/jpeg') {
            $im  = imagecreatefromjpeg($sourcePatch);
        } elseif ($mime=='image/png') {
            $im  = imagecreatefrompng($sourcePatch);
        } else {
            return false;
        }

        if ($im===false) {
            return false;
        }

        $size = getimagesize($sourcePatch);
        if (!is_array($size)) {
            return false;
        }

        $colorBackground=imagecolorat($im, 0, 0);
        if ($colorBackground===false) {
            return false;
        }

        $h_rez=self::lineCrop($colorBackground, $size, $im, 'top');
        $h_rez2=self::lineCrop($colorBackground, $size, $im, 'bottom');
        $w_rez=self::lineCrop($colorBackground, $size, $im, 'left');
        $w_rez2=self::lineCrop($colorBackground, $size, $im, 'right');

        if (($w_rez===null)
                || ($w_rez2===null)
                || ($h_rez===null)
                || ($h_rez2===null)) {
            return false;
        }

        $new_w=$size[0]-$w_rez-$w_rez2;
        $new_h=$size[1]-$h_rez-$h_rez2;

        if (($new_h/$height) > ($new_w/$height)) {
            $koeff=($new_h/$height);
        } else {
            $koeff=($new_w/$height);
        }

        if (($size[1]<=$height)
                && ($w_rez==0)
                && ($w_rez2==0)
                && ($h_rez==0)
                && ($h_rez2==0)) {
            return false;
        }

        $im2     = imagecreatetruecolor($new_w, $new_h);
        if ($im2===false) {
            return false;
        }
        $fill=imagecolorallocate($im2, 255, 255, 255);
        if ($fill===false) {
            return false;
        }
        imagefill($im2, 0, 0, $fill);
        imagecopyresampled($im2, $im, 0, 0, $w_rez, $h_rez, $new_w, $new_h, $new_w, $new_h);

        $im3     = imagecreatetruecolor((int)round($new_w/$koeff), (int)round($new_h/$koeff));
        if ($im3===false) {
            return false;
        }
        $fill=imagecolorallocate($im3, 255, 255, 255);
        if ($fill===false) {
            return false;
        }
        imagefill($im3, 0, 0, $fill);
        imagecopyresampled($im3, $im2, 0, 0, 0, 0, (int)round($new_w/$koeff), (int)round($new_h/$koeff), $new_w, $new_h);

        // Create dir if it needs
        $pos = strrpos($urlNew, '/');
        if (!$pos) {
            $pos=-1;
        }
        $dir = \substr($urlNew, 0, $pos);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }


        if ($mime=='image/jpeg') {
            \imagejpeg($im3, $urlNew);
        } elseif ($mime=='image/png') {
            \imagepng($im3, $urlNew);
        }


        imagedestroy($im);
        imagedestroy($im2);
        imagedestroy($im3);

        return true;
    }
}
