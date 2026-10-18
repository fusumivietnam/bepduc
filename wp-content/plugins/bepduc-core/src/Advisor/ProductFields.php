<?php
/**
 * Optional editorial advisory fields owned by Bepduc, attached to native Woo products.
 *
 * @package BepducCore
 */
declare(strict_types=1);

namespace Bepduc\Core\Advisor;

final class ProductFields
{
    /** Schema in one place; all fields plain text, no raw HTML or unknown URLs. */
    public const FIELDS = [
        'bd_buying_advice' => ['label' => 'Gợi ý lựa chọn', 'help' => 'Chỉ ghi nhận xét được kiểm chứng; không suy đoán tính năng.'],
        'bd_suitable_for' => ['label' => 'Phù hợp với', 'help' => 'Nhu cầu sử dụng được xác minh từ thông tin sản phẩm.'],
        'bd_installation_notes' => ['label' => 'Lưu ý lắp đặt riêng', 'help' => 'Đối chiếu tài liệu chính hãng trước khi điền.'],
        'bd_cutout_dimensions' => ['label' => 'Kích thước khoét lắp đặt', 'help' => 'Ghi nguyên giá trị và đơn vị theo tài liệu, không dùng kích thước đóng gói.'],
        'bd_source_reference' => ['label' => 'Nguồn xác minh', 'help' => 'Mã tài liệu, phiên bản hoặc đường dẫn tài liệu chính hãng.'],
    ];

    public static function boot(): void
    {
        add_action('init', [self::class, 'register']);
        add_action('woocommerce_product_options_general_product_data', [self::class, 'fields']);
        add_action('woocommerce_admin_process_product_object', [self::class, 'save']);
    }

    public static function register(): void
    {
        foreach (self::FIELDS as $key => $data) {
            register_post_meta('product', '_' . $key, [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => 'sanitize_textarea_field',
                'auth_callback' => static function (): bool {
                    return current_user_can('edit_products');
                },
            ]);
        }
    }

    public static function fields(): void
    {
        if (! function_exists('woocommerce_wp_textarea_input')) {
            return;
        }
        echo '<div class="options_group"><p class="form-field"><strong>'
            . esc_html__('Bếp Đức — dữ liệu tư vấn đã xác minh', 'bepduc-core')
            . '</strong></p>';
        foreach (self::FIELDS as $key => $data) {
            woocommerce_wp_textarea_input([
                'id' => '_' . $key,
                'label' => $data['label'],
                'description' => $data['help'],
                'desc_tip' => false,
            ]);
        }
        echo '</div>';
    }

    /**
     * Only save explicit fields from the WooCommerce authenticated edit form.
     * Empty fields are permitted and must not generate customer-facing claims.
     */
    public static function save(\WC_Product $product): void
    {
        if (! current_user_can('edit_post', $product->get_id())) {
            return;
        }
        foreach (self::FIELDS as $key => $data) {
            $field = '_' . $key;
            if (! isset($_POST[$field]) || ! is_string($_POST[$field])) {
                continue;
            }
            $value = sanitize_textarea_field(wp_unslash($_POST[$field]));
            $product->update_meta_data($field, $value);
        }
    }

    /**
     * Advice can be published only with an editorial source reference.
     *
     * @return array<string, string>
     */
    public static function published(\WC_Product $product): array
    {
        $source = trim((string) $product->get_meta('_bd_source_reference', true));
        if ($source === '') {
            return [];
        }
        $values = [];
        foreach (self::FIELDS as $key => $data) {
            if ($key === 'bd_source_reference') {
                continue;
            }
            $value = trim((string) $product->get_meta('_' . $key, true));
            if ($value !== '') {
                $values[$data['label']] = $value;
            }
        }
        return $values;
    }
}
