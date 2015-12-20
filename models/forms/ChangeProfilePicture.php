<?php
namespace models\forms;

use CW;
use models\User;
use components\helpers\ImageHelper;
use models\Image;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class ChangeProfilePicture extends \models\BaseModel {

    public $image;

    public function rules() {
        return [
            'image' => [
                'type' => self::TYPE_IMAGE
            ]
        ];
    }

    public function save() {
        $imageDg = $this->image->getImage();
        $ext = "jpeg";

        $userDir = CW::$app->params['sitePath'] . 'public_html/images/users/' . $this->userId;

        if (!is_dir($userDir)) {
            mkdir($userDir);
        }

        $imageMedium = ImageHelper::scaleAndCrop(
            0, 0,
            User::IMAGE_MEDIUM_SIZE, User::IMAGE_MEDIUM_SIZE,
            $imageDg,
            User::IMAGE_MEDIUM_SIZE, User::IMAGE_MEDIUM_SIZE
        );

        $imageSmall = ImageHelper::scaleAndCrop(
            0, 0,
            User::IMAGE_SMALL_SIZE, User::IMAGE_SMALL_SIZE,
            $imageDg,
            User::IMAGE_SMALL_SIZE, User::IMAGE_SMALL_SIZE
        );

        imagejpeg($imageMedium, sprintf("%s/%dx%d.%s", $userDir, User::IMAGE_MEDIUM_SIZE, User::IMAGE_MEDIUM_SIZE, $ext));
        imagejpeg($imageSmall, sprintf("%s/%dx%d.%s", $userDir, User::IMAGE_SMALL_SIZE, User::IMAGE_SMALL_SIZE, $ext));

        imagedestroy($imageMedium);
        imagedestroy($imageSmall);

        $imageId = Image::create([
            'rel_id' => CW::$app->user->identity->id,
            'rel_type' => Image::REL_TYPE_USER,
            'type' => Image::TYPE_PROFILE_PIC,
            'image_type' => Image::IMAGE_TYPE_NORMAL
        ]);

        if (null !== $imageId) {
            CW::$app->db->executeUpdate("UPDATE `users` SET `profile_img_id` = $imageId WHERE id = " . CW::$app->user->identity->id);
            $_SESSION['user']->profile_img_id = $imageId;
        }

        return true;
    }

}
