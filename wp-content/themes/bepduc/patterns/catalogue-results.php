<?php
/**
 * Title: Bepduc Catalogue Results
 * Slug: bepduc/catalogue-results
 * Categories: woocommerce
 * Inserter: no
 * Description: Shared archive filters and native product collection layout.
 */
?>
    <!-- wp:columns {"align":"wide","className":"bd-catalogue-layout","isStackedOnMobile":true} -->
    <div class="wp-block-columns alignwide bd-catalogue-layout">
        <!-- wp:column {"width":"260px","className":"bd-catalogue-filters-column"} -->
        <div class="wp-block-column bd-catalogue-filters-column" style="flex-basis:260px">
<!-- wp:bepduc/catalogue-controls /-->
        </div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"bd-catalogue-results-column"} -->
        <div class="wp-block-column bd-catalogue-results-column">
<!-- wp:query {"queryId":0,"query":{"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"className":"bd-catalogue-collection"} -->
<div class="wp-block-query bd-catalogue-collection">
    <!-- wp:post-template -->
        <?php require get_theme_file_path('inc/product-card-markup.php'); ?>
    <!-- /wp:post-template -->
    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->
    <!-- wp:query-no-results -->
        <!-- wp:paragraph {"className":"bd-catalogue-empty"} -->
        <p class="bd-catalogue-empty">Không tìm thấy sản phẩm phù hợp. Hãy thử điều chỉnh bộ lọc.</p>
        <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
