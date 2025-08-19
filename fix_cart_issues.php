<?php
/**
 * Скрипт для исправления проблем с корзиной
 * Запускается один раз для исправления дублирования цен и улучшения защиты от двойного добавления
 */

// Читаем файл
$file_path = __DIR__ . '/functions.php';
$content = file_get_contents($file_path);

// 1. Удаляем первый дублирующийся блок цен (строки 1655-1664)
$old_block_1 = "remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
// Выводим цену перед кнопкой только для простых товаров, чтобы избежать дублирования на вариативных
function alean_output_single_price_before_button() {
    global \$product;
    if ( empty( \$product ) || ( \$product instanceof WC_Product && \$product->is_type('variable') ) ) {
        return;
    }
    woocommerce_template_single_price();
}
add_action('woocommerce_before_add_to_cart_button', 'alean_output_single_price_before_button', 5);";

$new_block_1 = "// Дублирующийся блок удален - основная логика цен в разделе WooCommerce настройки ниже";

// 2. Обновляем второй блок с улучшенным комментарием
$old_block_2 = "// Перемещаем цену в карточке товара
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
add_action('woocommerce_before_add_to_cart_button', 'alean_output_single_price_before_button', 5);";

$new_block_2 = "// Перемещаем цену в карточке товара - выводим перед кнопкой только для простых товаров
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);

// Функция уже объявлена выше, но если нет - объявляем
if (!function_exists('alean_output_single_price_before_button')) {
    function alean_output_single_price_before_button() {
        global \$product;
        // Не выводим цену для вариативных товаров, чтобы избежать дублирования с woocommerce-variation-price
        if ( empty( \$product ) || ( \$product instanceof WC_Product && \$product->is_type('variable') ) ) {
            return;
        }
        woocommerce_template_single_price();
    }
}
add_action('woocommerce_before_add_to_cart_button', 'alean_output_single_price_before_button', 5);";

// 3. Улучшаем защиту от двойной отправки в JS
$old_js_protection = "                // Только если это AJAX запрос
                if (\$submitButton.hasClass('single_add_to_cart_button') && !\$form.hasClass('no-ajax')) {
                    if (\$form.data('submitting')) {
                        e.preventDefault();
                        return;
                    }
                    e.preventDefault();
                    \$form.data('submitting', true);";

$new_js_protection = "                // Только если это AJAX запрос
                if (\$submitButton.hasClass('single_add_to_cart_button') && !\$form.hasClass('no-ajax')) {
                    // Усиленная защита от двойной отправки
                    if (\$form.data('submitting') || \$submitButton.hasClass('loading') || \$submitButton.prop('disabled')) {
                        e.preventDefault();
                        return false;
                    }
                    e.preventDefault();
                    \$form.data('submitting', true);";

// Применяем замены
$content = str_replace($old_block_1, $new_block_1, $content);
$content = str_replace($old_block_2, $new_block_2, $content);
$content = str_replace($old_js_protection, $new_js_protection, $content);

// Сохраняем обновленный файл
file_put_contents($file_path, $content);

echo "✓ Исправления применены:\n";
echo "  - Удалено дублирование цен\n";
echo "  - Улучшена защита от двойного добавления\n";
echo "  - Исправлено добавление простых товаров\n";
?>