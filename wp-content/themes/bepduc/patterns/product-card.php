<?php
/**
 * Title: Bepduc Product Card
 * Slug: bepduc/product-card
 * Categories: woocommerce
 * Inserter: no
 * Description: Reusable product collection card powered by WooCommerce blocks.
 */
?>
<!-- wp:group {"className":"bd-product-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group bd-product-card">
    <!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","showSaleBadge":false,"aspectRatio":"1"} -->
        <!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
    <!-- /wp:woocommerce/product-image -->
    <!-- wp:group {"className":"bd-product-card__body","layout":{"type":"constrained"}} -->
    <div class="wp-block-group bd-product-card__body">
        <!-- wp:bepduc/product-brand /-->
        <!-- wp:post-title {"level":3,"isLink":true,"className":"bd-product-card__title","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->
        <!-- wp:woocommerce/product-price /-->
        <!-- wp:woocommerce/product-stock-indicator /-->
        <!-- wp:woocommerce/product-button {"textAlign":"left"} /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->
