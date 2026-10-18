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
<!-- wp:group {"className":"bd-catalogue-sidebar","layout":{"type":"constrained"}} -->
<div class="wp-block-group bd-catalogue-sidebar">
    <!-- wp:heading {"level":2,"fontSize":"lg"} -->
    <h2 class="wp-block-heading has-lg-font-size">Lọc sản phẩm</h2>
    <!-- /wp:heading -->
    <!-- wp:woocommerce/product-filters {"overlayMode":"mobile"} -->
    <div class="wp-block-woocommerce-product-filters">
        <!-- wp:woocommerce/product-filter-active /-->
        <!-- wp:woocommerce/product-filter-price /-->
        <!-- wp:woocommerce/product-filter-status /-->
        <!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_brand","showCounts":true} /-->
    </div>
    <!-- /wp:woocommerce/product-filters -->
</div>
<!-- /wp:group -->
        </div>
        <!-- /wp:column -->
        <!-- wp:column {"className":"bd-catalogue-results-column"} -->
        <div class="wp-block-column bd-catalogue-results-column">
            <!-- wp:group {"className":"bd-catalogue-toolbar","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
            <div class="wp-block-group bd-catalogue-toolbar">
                <!-- wp:woocommerce/product-results-count /-->
                <!-- wp:woocommerce/catalog-sorting /-->
            </div>
            <!-- /wp:group -->
<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"title","author":"","search":"","exclude":[],"sticky":"","inherit":false},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"queryContextIncludes":["collection"],"className":"bd-catalogue-collection"} -->
<div class="wp-block-woocommerce-product-collection bd-catalogue-collection">
    <!-- wp:woocommerce/product-template -->
        <?php require get_theme_file_path('inc/product-card-markup.php'); ?>
    <!-- /wp:woocommerce/product-template -->
    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->
    <!-- wp:woocommerce/product-collection-no-results -->
        <!-- wp:paragraph {"className":"bd-catalogue-empty"} -->
        <p class="bd-catalogue-empty">Không tìm thấy sản phẩm phù hợp. Hãy thử điều chỉnh bộ lọc.</p>
        <!-- /wp:paragraph -->
    <!-- /wp:woocommerce/product-collection-no-results -->
</div>
<!-- /wp:woocommerce/product-collection -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
