<h2>Change Profile Picture</h2>
<div class="start-page-cont" style="padding: 15px;">
<form method="post" enctype="multipart/form-data" accept="image/*">
    <div class="field-sep">
        <div>
            <span> Profile Picture: </span>
        </div>
        <div>
            <input type="file" name="<?= $modelName ?>[image]" accept="image/*">
        </div>
    </div>
    <input type="hidden" name="_csrf" value="<?= components\Security::hash($_SESSION['_csrf']) ?>">
    <input type="submit" value="chance picture" class="submit-btn" style="margin-top: 10px;">
</form>
</div>
