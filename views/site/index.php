<?php
use components\helpers\ArrayHelper;
use components\UrlManager;
use models\Update;

$type = CW::$app->request->get('type');
$type = Update::isValidType($type) ? $type : \models\Update::TYPE_FRESH;

$this->title = "Browse $type updates";
?>
<script>
$(function() {
    App.update.category = '<?= CW::$app->request->get('category') ?>';

    var updateLoader = App.update.Loader({
        updatesCont : document.getElementById('updates-cont'),
        loadingEle  : document.getElementById('loading'),
        noResultEle : document.getElementById('no-results-found'),
        url : <?= json_encode(\components\UrlManager::to(['update/ajaxLoad'])) ?>,
        ajaxData : {
            category : App.update.category,
            type : <?= json_encode($type) ?>
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
                <a  class="updates-type <?= \models\Update::TYPE_TRENDING === $type ? 'updates-type-selected' : '' ?>" href="<?= UrlManager::to(['site/index', 'type' => 'trending', 'category' => $category]) ?>">trending</a>
                <a class="updates-type <?= (\models\Update::TYPE_FRESH === $type || !in_array($type, [
                    \models\Update::TYPE_FRESH, \models\Update::TYPE_TRENDING
                ])) ? 'updates-type-selected' : '' ?>" href="<?= UrlManager::to(['site/index', 'type' => 'fresh', 'category' => $category]) ?>">fresh</a>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="page-no-popular">
    <div style="width: 1200px;position: relative;margin: auto;">
        <div id="no-results-found" class="hidden" style="width: 200px; margin: auto;">
            <h3>No updates found.</h3>
        </div>
        <div id="updates-cont" class="msr-cont"></div>
        <div id="loading">
            <?= $this->render('../search/loading') ?>
        </div>
    </div>
</div>
