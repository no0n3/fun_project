<h2> Edit Profile </h2>
<div class="start-page-cont" style="padding: 15px;">
<form id="f1" method="post">
    <div class="field-sep">
        <div>
            <span> Username: </span>
        </div>
        <div>
            <input id="username" class="form-inp" type="text" name="<?= $modelName ?>[username]" value="<?= $model->username ?>">
        </div>
        <div style="margin-top: 5px;color: red;">
            <span><?= $model->getError('username') ?></span>
        </div>
    </div>
    <div class="field-sep">
        <div>
            <span> Description: </span>
        </div>
        <div>
            <textarea id="image" class="textarea" name="<?= $modelName ?>[description]"><?= $model->description ?></textarea>
        </div>
    </div>
    <div class="field-sep">
        <div>
            <span> Choose categories: </span>
        </div>
        <div>
            <?php foreach ($categories as $category) { ?>
            <span class="category-cont category-cbx">
                <input <?= in_array($category['id'], $model->userCategories) ? 'checked="true"' : '' ?> class="category-opt" type="checkbox" name="<?= $modelName ?>[categories][]" value="<?= $category['id'] ?>">
                <span><?= htmlspecialchars($category['name']) ?></span>
            </span>
            <?php } ?>
        </div>
    </div>
    <input type="hidden" name="_csrf" value="<?= components\Security::hash($_SESSION['_csrf']) ?>">
    <input type="submit" value="update" class="submit-btn" style="margin-top: 15px;">
</form>
</div>
