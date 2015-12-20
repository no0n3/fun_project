<?php
$term = CW::$app->request->get('term');
$term = null !== $term ? trim($term) : $term;
?>
<script>
$(function() {
    App.update.category = '<?= CW::$app->request->get('category') ?>';

    App.update.Loader({
        updatesCont : document.getElementById('updates-cont'),
        loadingEle  : document.getElementById('loading'),
        noResultEle : document.getElementById('no-results-found'),
        url : "/ajax/search",
        ajaxData : {
            term : '<?= \CW::$app->request->get('term') ?>',
            category : '<?= CW::$app->request->get('category') ?>'
        }
    })
        .load();fds
});
</script>

<div class="page-search">
    <h1 class="search-result-txt">Search results for '<?= htmlspecialchars($term) ?>'</h1>
    <div style="width: 1200px;
        position: relative;
        margin: auto;">
    <!--<div style="width : 100%;">-->
        <div id="updates-cont" class="msr-cont">
            <!-- <div id="no-results-found" class="hidden" style="width: 200px;">
                <h3>No updates found.</h3>
            </div> -->
        </div>
        <div id="loading">
            <?= $this->render('../search/loading') ?>
        </div>
    </div>
</div>
