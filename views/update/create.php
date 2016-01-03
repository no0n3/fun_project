<?php
$modelName = $model->getClassName(false);

$this->title = 'Create update - ' . \CW::$app->params['siteName'];
?>
<script>

var $categoriesError;
var categories;

var $title;
var $titleError;

var $imgInp;
var $imageError;
var $tagsError;

function getCheckedCategoriesCount() {
    var c = 0;

    for (var i in categories) {
        if (categories[i].checked) {
            c++;
        }
    }

    return c;
}

function validateTags() {
    if (document.getElementsByName('<?= $modelName ?>[tags][]').length <= 0) {
        $tagsError.html('Tags cannot be empty.');
        return false;
    } else {
        $tagsError.html('');
        return true;
    }
}

$(function() {
    categories = document.getElementsByName('<?= $modelName ?>[categories][]');

    $imgInp = $('#image');
    $imageError = $('#image-error');

    $title = $('#title');
    $titleError = $('#title-error');
    $tagsError = $('#tags-error');

    $categoriesError = $('#categories-error');
    
    $title.on('change', function() {
        if (0 >= $title.val().length) {
            $titleError.html('Title cannot be empty.');
        } else {
            $titleError.html('');
        }
    });

    $('#f1').on('submit', function() {
        var status = true;

        if (0 >= getCheckedCategoriesCount()) {
            $categoriesError.html('You must choose at least one category.');
            status = false;
        }
        if (0 >= $title.val().length) {
            status = false;
            $titleError.html('Title cannot be empty.');
        }
        if (0 >= $imgInp.val().length) {
            $imageError.html('You must choose an image.');
        }

        status = status && validateTags();

        return status;
    });
});

function checkboxChecked(checkbox) {
    var $checkbox = $(checkbox);

    if (checkbox.checked) {
        $categoriesError.html('');
    } else if (0 >= getCheckedCategoriesCount()) {
        $categoriesError.html('You must choose at least one category.');
    }
}

$(function() {
    function removeEle(ele) {
        if (ele && ele.parentNode) {
            ele.parentNode.removeChild(ele);
            validateTags();
        }
    }

    function addTag(name) {
            var input = document.createElement('input');
            var cont = document.createElement('span');
            var remove = document.createElement('i');
            remove.onclick = function() {
                removeEle(cont);
            };
            remove.setAttribute('style', 'background: transparent url("/images/x.png") repeat scroll 0% 0% / 10px 16px;height: 16px;width: 10px;display: inline-block;vertical-align: middle;border: 0px none;cursor: pointer;margin-left: 5px;')

            input.setAttribute('type', 'hidden');
            input.setAttribute('name', '<?= $modelName ?>[tags][]');
            input.setAttribute('value', name);
            cont.setAttribute('class', 'category-cont category-cbx');

            cont.innerHTML = name
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

            cont.appendChild(input);
            cont.appendChild(remove);

            return cont;
        }

        $('#tags-inp').on('keydown', function(e) {
            if (13 == e.keyCode && '' != this.value.trim()) {
                document.getElementById('tags-cont').appendChild(addTag(this.value));
                validateTags();
                this.value = '';
                e.stopPropagation();
                e.preventDefault();
            }
        });
})

</script>

<div class="create-update-cont">
    <?php if ($success) : ?>
    <div class="success">
        <i class="fa fa-check-circle"></i>
        <span class="success-msg"> Update successfully created. <a class="success-link" href="<?= \components\UrlManager::to(['update/view', 'id' => $model->newUpdateId]) ?>">Click here</a> to view update.</span>
    </div>
    <?php else : ?>
    <h1> Create Update </h1>
    <div style="padding: 15px;" class="start-page-cont">
    <form id="f1" method="post" enctype="multipart/form-data">
        <div class="field-sep">
            <div>
                <span> Title: </span>
            </div>
            <div>
                <textarea class="textarea" id="title" name="<?= $modelName ?>[title]" value="<?= $model->title ?>"></textarea>
            </div>
            <span id="title-error" class="error"></span>
        </div>
        <div class="field-sep">
            <div>
                <span> Image: </span>
            </div>
            <div>
                <input id="image" type="file" name="<?= $modelName ?>[image]" value="" accept="image/jpeg, image/png, image/gif">
            </div>
            <span id="image-error" class="error">
                <?= $model->getError('image') ?>
            </span>
        </div>
        <div class="field-sep">
            <div>
                <span> Choose categories: </span>
            </div>
            <div>
                <?php foreach ($categories as $category) : ?>
                <span class="category-cont category-cbx">
                    <input class="category-opt" onchange="checkboxChecked(this)"id="img-inp" type="checkbox" name="<?= $modelName ?>[categories][]" value="<?= $category['id'] ?>">
                    <span><?= $category['name'] ?></span>
                </span>
                <?php endforeach; ?>
            </div>
            <span id="categories-error" class="error"></span>
        </div>
        
        <div style="padding: 0px; padding-top: 10px;">
                <div>Tags</div>
                <div style="margin-top: 5px;">
                    <input id="tags-inp" type="text" class="form-inp">
                </div>
                <div id="tags-cont">
                    <?php foreach($model->tags as $tag) : ?>
                    <span class="category-cont category-cbx">
                        <?= $tag ?>
                        <input type="hidden" name="<?= $modelName ?>[tags][]" value="<?= $tag ?>">
                        <span onclick="removeEle(this.parentNode)">x</span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <div id="tags-error"style="margin-top: 5px; color: red;">
                    <?= $model->getError('tags') ?>
                </div>
            </div>

        <input type="hidden" name="_csrf" value="<?= \components\Security::hash($_SESSION['_csrf']) ?>">
        <input type="submit" value="create update" class="submit-btn" style="margin-top: 10px;">
    </form>
    </div>

    <?php endif; ?>
</div>
