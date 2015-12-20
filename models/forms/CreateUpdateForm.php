<?php
namespace models\forms;

use CW;
use models\Update;
use components\helpers\ImageHelper;
use components\helpers\ArrayHelper;
use models\Image;

/**
 * @author Velizar Ivanov <zivanof@gmail.com>
 */
class CreateUpdateForm extends \models\BaseModel {

    public $title;
    public $image;
    public $categories;
    public $tags = [];

    public $newUpdateId;

    public function rules() {
        return [
            'title' => [
                'validator' => function($name, $value) {
                    return $value <= 0 || $value > 255;
                },
                'message' => 'Username must be at least 2 characters long.'
            ],
            'categories' => [
                'validator' => function($name, $value) {
                    return 0 < count($value);
                }
            ],
            'tags' => [
                'validator' => function($name, $value) {
                    if (0 >= count($value)) {
                        return false;
                    }

                    foreach ($value as $tag) {
                        if ('' !== trim($tag)) {
                            return true;
                        }
                    }

                    return false;
                }
            ],
            'image' => [
                'type' => self::TYPE_IMAGE
            ]
        ];
    }

    public function save() {
        $imageDg = $this->image->getImage();
        $ext = "jpeg";

        $stmt = CW::$app->db->prepare("INSERT INTO `updates` (`user_id`, `description`, `is_gif`, `created_at`) VALUES (:userId, :description, :is_gif, :created_at)");

        if (0 >= $stmt->execute([
            ':userId'      => \CW::$app->user->identity->id,
            ':description' =>  $this->title,
            ':is_gif'      => $this->image->isGif(),
            ':created_at'  => time()
        ])) {
            return false;
        }

        $this->newUpdateId = CW::$app->db->getLastInsertedId();

        if (!Update::addActivity($this->newUpdateId, CW::$app->user->identity->id, Update::ACTIVITY_TYPE_POST)) {
            return false;
        }

        $this->addTags();

        $a = [];

        foreach ($this->categories as $category) {
            $c = (int) $category;
            $a[] = "({$this->newUpdateId}, $c)";
        }

        $categoryInsert = 'INSERT INTO `update_categories` (`update_id`, `category_id`) VALUES ' . implode(',', $a);

        if (0 >= CW::$app->db->executeUpdate($categoryInsert)) {
            return false;
        }

        $updateDir = CW::$app->params['sitePath'] . 'public_html/images/updates/' . $this->newUpdateId;

        mkdir($updateDir);

        if ('image/gif' === $this->image->getExt()) {
            $videoName = $this->newUpdateId;

            $i = ImageHelper::loadImage($this->image->getImage());

            if (!$i) {
                $this->addError('image', 'Invalid gif.');
                return false;
            }

            imagejpeg(
                ImageHelper::scaleImage($i->getImage(), 500),
                CW::$app->params['sitePath'] . "public_html/images/{$videoName}_poster.jpeg"
            );

            $inpFile = CW::$app->params['sitePath'] . "public_html/images/tmp/{$videoName}_medium.gif";

            $outFileMedium = CW::$app->params['sitePath'] . "public_html/images/{$videoName}_medium";

            foreach (['mp4', 'webm'] as $ext) {
                $mediumBytes = ImageHelper::resizeGif($this->image->getImage(), 500);
                file_put_contents($inpFile, $mediumBytes);
                ImageHelper::gifToVideo($inpFile, "$outFileMedium.$ext");
                unlink($inpFile);
            }

            $imageType = Image::IMAGE_TYPE_VIDEO;
            $type = Image::TYPE_IMAGE;
        } else {
            $imageBig = ImageHelper::scaleImage($imageDg, Update::IMAGE_BIG_WIDTH, PHP_INT_MAX);
            $imageMedium = ImageHelper::scaleImage($imageDg, Update::IMAGE_MEDIUM_WIDTH, PHP_INT_MAX);

            $highImage = imagesy($imageMedium) > imagesx($imageMedium) + 150;

            if ($highImage) {
                $imageMedium = ImageHelper::cropImage(0, 0, imagesx($imageMedium), 300, $imageMedium);
            }
            $imageSmall = ImageHelper::scaleImage($imageDg, Update::IMAGE_SMALL_WIDTH, PHP_INT_MAX);

            if ($highImage) {
                $imageSmall = ImageHelper::cropImage(0, 0, imagesx($imageSmall), 150, $imageSmall);
            }

            imagejpeg($imageBig, sprintf("%s/%dxX.%s", $updateDir, Update::IMAGE_BIG_WIDTH, $ext));
            imagejpeg($imageMedium, sprintf("%s/%dxX.%s", $updateDir, Update::IMAGE_MEDIUM_WIDTH, $ext));
            imagejpeg($imageSmall, sprintf("%s/%dxX.%s", $updateDir, Update::IMAGE_SMALL_WIDTH, $ext));

            imagedestroy($imageBig);
            imagedestroy($imageMedium);
            imagedestroy($imageSmall);
 
            $imageType = Image::IMAGE_TYPE_NORMAL;
            $type = $highImage ? Image::TYPE_HIGH_IMAGE : Image::TYPE_IMAGE;
        }

        Image::create([
            'rel_id' => $this->newUpdateId,
            'rel_type' => Image::REL_TYPE_UPDATE,
            'type' => $type,
            'image_type' => $imageType
        ]);

        return true;
    }

    private function addTags() {
        if (!empty($this->tags)) {
            $tagsC = count($this->tags);
            $keys = [];
            $vals = [];
            $tags = [];

            for ($i = 0; $i < $tagsC; $i++) {
                $this->tags[$i] = strtolower(trim($this->tags[$i]));
                $keys[] = ":tag$i";
                $vals[":tag$i"] = $this->tags[$i];
                $tags[$this->tags[$i]] = $this->tags[$i];
            }

            $stmt = CW::$app->db->prepare('SELECT name FROM tags WHERE name IN (' . ArrayHelper::getArrayToString($keys, ',') . ')');
            $stmt->execute($vals);
            $tagsResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($tagsResult as $tag) {
                if (isset($tags[$tag['name']])) {
                    unset($tags[$tag['name']]);
                }
            }

            $tagsToAddCount = count($tags);

            if (0 < $tagsToAddCount) {
                $keys1 = [];
                $vals1 = [];
                $i = 0;

                foreach ($tags as $tag) {
                    $keys1[] = ":tag$i";
                    $vals1[":tag$i"] = $tag;
                    $i++;
                }

                $query = 'INSERT INTO tags (name, created_at) VALUES '
                    . ArrayHelper::getArrayToString($keys1, ',',
                        function($v) {
                            return "($v, ".time().")";
                        }
                    );

                $stmt = CW::$app->db->prepare($query);
                $stmt->execute($vals1);
            }

            $stmt = CW::$app->db->prepare('SELECT id FROM tags WHERE name IN (' . ArrayHelper::getArrayToString($keys, ',') . ')');
            $stmt->execute($vals);
            $tagsResult = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $query = 'INSERT INTO update_tags (tag_id, update_id) VALUES ' .
                ArrayHelper::getArrayToString(
                    ArrayHelper::getKeyArray($tagsResult, 'id'),
                    ',',
                    function($v) {
                        return "($v, {$this->newUpdateId})";
                    }
                );

            $stmt = CW::$app->db->prepare($query);
            $stmt->execute($vals);
        }
    }

    public function getImage() {
        return null !== $this->image ? $this->image->getImage() : null;
    }

}
