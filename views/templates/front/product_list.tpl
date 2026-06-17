{* Simple product grid list for shortcode outputs *}
{if $sc_items}
  <div class="mgsc-grid">
      {foreach from=$sc_items item=it}
        <div class="mgsc-grid__item">
          {assign var=sc_product value=$it.product}
          {assign var=sc_product_link value=$it.product_link}
          {assign var=sc_product_image value=$it.product_image}
          {assign var=sc_product_price value=$it.product_price}
          {include file='module:shortcodes/views/templates/front/product_card.tpl'}
        </div>
      {/foreach}
  </div>
{/if}
