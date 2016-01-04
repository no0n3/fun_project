<?php
$term = CW::$app->request->get('term');
$term = null !== $term ? trim($term) : $term;

$this->title = 'Search updates - ' . \CW::$app->params['siteName'];
?>
<script>
$(function() {
    App.update.category = '<?= CW::$app->request->get('category') ?>';

    App.update.Loader({
        updatesCont : document.getElementById('updates-cont'),
        loadingEle  : document.getElementById('loading'),
        noResultEle : document.getElementById('no-results-found'),
        url : <?= json_encode(\components\UrlManager::to(['site/ajaxSearch'])) ?>,
        ajaxData : {
            term : <?= json_encode(\CW::$app->request->get('term')) ?>,
            category : '<?= CW::$app->request->get('category') ?>'
        }
    })
        .load();
});
</script>

<div class="page-search">
    <p class="search-result-txt" style="text-align: center;font-size: 20px;">Search results for '<span style="color: #359C48;"><?= htmlspecialchars($term) ?></span>'</p>
    <div style="width: 1200px;position: relative;margin: auto;">
        <div id="no-results-found" class="hidden" style="width: 200px; margin: auto;">
            <h3>No results found.</h3>
        </div>
        <div id="updates-cont" class="msr-cont">
        </div>
        <div id="loading">
            <?= $this->render('../search/loading') ?>
        </div>
    </div>
</div>
