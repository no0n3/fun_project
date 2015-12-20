<?php
namespace components\helpers;

use Imagecraft\ImageBuilder;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class ImageHelper {

    /**
     * 
     * @param type $imagePath
     * @param type $imageWidth
     * @param type $imageHeight
     * @return type
     */
    public static function loadImage(
        $imagePath,
        $imageWidth = 0,
        $imageHeight = 0
    ) {
        list($imageWidth, $imageHeight, $imgType) = getimagesize($imagePath);

        $ext = null;

        switch ($imgType) {
            case IMAGETYPE_GIF:
                $ext = 'jpeg';
                $gdImage = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_JPEG:
                $ext = 'jpeg';
                $gdImage = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $ext = 'png';
                $gdImage = imagecreatefrompng($imagePath);
                break;
        }

        return !empty($gdImage) ?
            new \models\misc\Image($gdImage, $ext) : 
            null;
    }

    /**
     * 
     * @param type $img
     * @param type $imgExt
     * @return type
     */
    public static function imageToBytes($img, $imgExt) {
        $imgFunc = 'image' . $imgExt;

        ob_start();
        $imgFunc($img);

        return ob_get_clean();
    }

    /**
     * 
     * @param type $x
     * @param type $y
     * @param type $w
     * @param type $h
     * @param type $targetImage
     * @return type
     */
    public static function cropImage($x, $y, $w, $h, $targetImage) {
        $canvas = imagecreatetruecolor($w, $h);

        $current_width = imagesx($targetImage);
        $current_height = imagesy($targetImage);

        imagecopy(
                $canvas,
                $targetImage,
                0, 0,
                $x, $y,
                $current_width, $current_height
        );

        return $canvas;
    }

    /**
     * 
     * @param type $x
     * @param type $y
     * @param type $w
     * @param type $h
     * @param type $targetImage
     * @param type $resizeW
     * @param type $resizeH
     * @return type
     */
    public static function scaleAndCrop($x, $y, $w, $h, $targetImage, $resizeW, $resizeH) {
        $resizedImage =  self::scaleImage($targetImage, $resizeW, $resizeH);

        $_w = imagesx($resizedImage);

        if ($w > $_w) {
            $w = $h = $_w;
        }

        $_h = imagesy($resizedImage);

        if ($h > $_h) {
            $h = $w = $_h;
        }

        $croppedImage = self::cropImage($x, $y, $w, $h, $resizedImage);
        imagedestroy($resizedImage);

        return $croppedImage;
    }

    /**
     * Resizes an image.
     * @param integer $w resize width
     * @param integer $h resize height
     * @param resource $image the image to be resized
     * @return resource the resized image
     */
    public static function resizeImage($w, $h, $image) {
        $resizedImage = imagecreatetruecolor($w, $h);

        imagecopyresampled(
                $resizedImage, $image, 0, 0, // dest x, y
                0, 0, // src x, y
                $w, $h, //$_w, $_h, // dest w, h
                imagesx($image), imagesy($image) // src w, h
        );

        return $resizedImage;
    }

    /**
     * 
     * @param type $sourceImagePath
     * @param type $maxWidth
     * @param type $maxHeight
     * @return boolean
     */
    public static function scaleImage($sourceImagePath, $maxWidth = PHP_INT_MAX, $maxHeight = PHP_INT_MAX) {
        if (is_string($sourceImagePath)) {
            $result = self::loadImage($sourceImagePath, $maxWidth, $maxHeight);

            $gdImage = $result ? $result->getImage() : null;
        } else {
            $gdImage = $sourceImagePath;
        }

        if (0 == $maxWidth) {
            $maxWidth = PHP_INT_MAX;
        }
        if (0 == $maxHeight) {
            $maxHeight = PHP_INT_MAX;
        }

        if (!$gdImage) {
            return false;
        }

        $imageWidth = imagesx($gdImage);
        $imageHeight = imagesy($gdImage);

        $aspectRatio = $imageWidth / $imageHeight;
        $thumbnailAspectRatio = $maxWidth / $maxHeight;

        if ($imageWidth <= $maxWidth && $imageHeight <= $maxHeight) {
            $thumbnailWidth = $imageWidth;
            $thumbnailHeight = $imageHeight;
        } elseif ($thumbnailAspectRatio > $aspectRatio) {
            $thumbnailWidth = (int) ($maxHeight * $aspectRatio);
            $thumbnailHeight = $maxHeight;
        } else {
            $thumbnailWidth = $maxWidth;
            $thumbnailHeight = (int) ($maxWidth / $aspectRatio);
        }

        $thumbnailGdImage = imagecreatetruecolor($thumbnailWidth, $thumbnailHeight);
        imagecopyresampled(
                $thumbnailGdImage, $gdImage,
                0, 0, 0, 0,
                $thumbnailWidth, $thumbnailHeight,
                $imageWidth, $imageHeight
        );

        return $thumbnailGdImage;
    }

    /**
     * 
     * @param type $imgSrc
     * @param type $width
     * @param type $heigh
     * @return type
     */
    public static function resizeGif($imgSrc, $width, $heigh = PHP_INT_MAX) {
        $options = ['engine' => 'php_gd', 'locale' => 'en_EU'];

        $builder = new ImageBuilder($options);

        $layer = $builder->addBackgroundLayer();
        $layer->filename($imgSrc);
        $layer->resize($width, $heigh);

        $layer = $builder->addTextLayer();
        $layer->font(
            sprintf('%sfonts/Airstream.ttf', \CW::$app->params['sitePath'])
        );
        $layer->label('');
        $layer->move(-10, -10, 'bottom_right');

        $image = $builder->save();

        if ($image->isValid()) {
            return $image->getContents();
        } else {
            echo $image->getMessage().PHP_EOL;
        }
    }

    /**
     * 
     * @param type $inpFile
     * @param type $outFile
     */
    public static function gifToVideo($inpFile, $outFile) {
        shell_exec("ffmpeg -f gif -i $inpFile $outFile");
    }

}
