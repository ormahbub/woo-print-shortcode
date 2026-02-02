# Woo Print Shortcode

A simple WordPress plugin to create custom HTML shortcodes and automatically display them on WooCommerce product pages based on the category.

I built this to solve two problems:

1. Converting repetitive HTML snippets into easy-to-use shortcodes.
2. Automating where those snippets appear on product pages without editing template files.

## Features

- Create custom shortcodes from any HTML/CSS snippet.
- Manage all your snippets from one dashboard.
- Auto-inject shortcodes into product pages.
- Filter display by product category.
- Choose from 5 different WooCommerce hook positions.

## Installation

1. Upload the `woo-print-shortcode` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Make sure WooCommerce is active.

## How it works

### Creating Shortcodes

Go to **Woo Print > Shortcode Creator**. Give your shortcode a name (like `shipping-info`) and paste your HTML. Once saved, you can use `[shipping-info]` in any post, page, or product description.

### Setting Display Rules

Go to **Woo Print > Display Rules**.

- Enter the shortcode you want to show.
- Select which category of products should show it.
- Pick a position (e.g., After Add to Cart).
- Save changes.

The content will now automatically appear on all products in that category.

## Hooks Used

The plugin hooks into standard WooCommerce actions:

- `woocommerce_single_product_summary`
- `woocommerce_before_add_to_cart_form`
- `woocommerce_after_add_to_cart_form`
- `woocommerce_product_meta_end`
- `woocommerce_after_single_product_summary`

## License

GPLv2 or later.
