<?php
use components\helpers\ArrayHelper;

$type = CW::$app->request->get('type');
?>
<script>
$(function() {
    App.update.category = '<?= CW::$app->request->get('category') ?>';

    var updateLoader = App.update.Loader({
        updatesCont : document.getElementById('updates-cont'),
        loadingEle  : document.getElementById('loading'),
        noResultEle : document.getElementById('no-results-found'),
        url : "/ajax/update/load",
        ajaxData : {
            category : App.update.category,
            type : '<?= \models\Update::isValidType($type) ? $type : \models\Update::TYPE_FRESH ?>'
        }
    });

    updateLoader.load();
});
</script>

<?php if (!empty($category) && in_array($category, ArrayHelper::getKeyArray($this->categories, 'name'))) : ?>
    <div class="category-info-cont-1">
        <div class="page-no-popular category-info-cont-2">
            <h2 class="category-section-title">
                <?= htmlspecialchars($category) ?>
            </h2>
            <div class="category-types">
                <a  class="updates-type <?= \models\Update::TYPE_TRENDING === $type ? 'updates-type-selected' : '' ?>" href="/<?= htmlspecialchars($category) ?>/trending">trending</a>
                <a class="updates-type <?= (\models\Update::TYPE_FRESH === $type || !in_array($type, [
                    \models\Update::TYPE_FRESH, \models\Update::TYPE_TRENDING
                ])) ? 'updates-type-selected' : '' ?>" href="/<?= htmlspecialchars($category) ?>/fresh">fresh</a>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="page-no-popular">
    <div style="width: 1200px;
        position: relative;
        margin: auto;">
        <div id="no-results-found" class="hidden" style="width: 200px; margin: auto;">
            <h3>No updates found.</h3>
        </div>
        <div id="updates-cont" class="msr-cont"></div>
        <div id="loading">
            <?= $this->render('../search/loading') ?>
        </div>
    </div>
</div>
