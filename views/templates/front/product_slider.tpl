{* Simple product slider for shortcode outputs *}
<div id="{$sc_slider_id}"
     class="slider-cards swiper spv-xl-{$sc_spv_xl|default:4} spv-lg-{$sc_spv_lg|default:4} spv-md-{$sc_spv_md|default:3} spv-sm-{$sc_spv_sm|default:2} spv-xs-{$sc_spv_xs|default:1}{if $page_name == 'index'} full-width-responsive overflow-visible{/if}"
     data-autoplay-enabled="{if isset($sc_autoplay_enabled)}{if $sc_autoplay_enabled}true{else}false{/if}{else}false{/if}"
     data-center-enabled="{if isset($sc_center_enabled)}{if $sc_center_enabled}true{else}false{/if}{else}{if $page_name == 'index'}true{else}false{/if}{/if}"
     data-center-enabled-xl="{if isset($sc_center_enabled_xl)}{if $sc_center_enabled_xl}true{else}false{/if}{/if}"
     data-center-enabled-lg="{if isset($sc_center_enabled_lg)}{if $sc_center_enabled_lg}true{else}false{/if}{/if}"
     data-center-enabled-md="{if isset($sc_center_enabled_md)}{if $sc_center_enabled_md}true{else}false{/if}{/if}"
     data-center-enabled-sm="{if isset($sc_center_enabled_sm)}{if $sc_center_enabled_sm}true{else}false{/if}{/if}"
     data-space-between="{if isset($sc_space_between)}{$sc_space_between}{else}20{/if}"
     data-auto-height="{if isset($sc_auto_height)}{if $sc_auto_height}true{else}false{/if}{else}false{/if}"
     data-loop="{if $page_name == 'index' || $page_name == 'cms'}true{else}false{/if}"
     data-speed="{if isset($sc_speed)}{$sc_speed}{else}600{/if}"
     data-slides-per-view="{$sc_spv|default:1}"
     data-slides-per-view-xl="{$sc_spv_xl|default:4}"
     data-slides-per-view-lg="{$sc_spv_lg|default:4}"
     data-slides-per-view-md="{$sc_spv_md|default:3}"
     data-slides-per-view-sm="{$sc_spv_sm|default:2}"
     data-slides-per-view-xs="{$sc_spv_xs|default:1}">
    {assign var=_needed value=$sc_spv|default:1}
    {if isset($sc_spv_xl) && $sc_spv_xl > $_needed}{assign var=_needed value=$sc_spv_xl}{/if}
    {if isset($sc_spv_lg) && $sc_spv_lg > $_needed}{assign var=_needed value=$sc_spv_lg}{/if}
    {if isset($sc_spv_md) && $sc_spv_md > $_needed}{assign var=_needed value=$sc_spv_md}{/if}
    {if isset($sc_spv_sm) && $sc_spv_sm > $_needed}{assign var=_needed value=$sc_spv_sm}{/if}
    {if isset($sc_spv_xs) && $sc_spv_xs > $_needed}{assign var=_needed value=$sc_spv_xs}{/if}

    {assign var=_count value=$sc_items|@count}
    <ul class="swiper-wrapper element-cards">
        {if $_count > 0}
            {if $_count >= $_needed}
                {foreach from=$sc_items item=it}
                    <li class="swiper-slide">
                        {assign var=sc_product value=$it.product}
                        {assign var=sc_product_link value=$it.product_link}
                        {assign var=sc_product_image value=$it.product_image}
                        {assign var=sc_product_price value=$it.product_price}
                        {include file='module:shortcodes/views/templates/front/product_card.tpl'}
                    </li>
                {/foreach}
            {else}
                {for $i=0 to $_needed-1}
                    {assign var=__idx value=$i%$_count}
                    {assign var=it value=$sc_items[__idx]}
                    <li class="swiper-slide">
                        {assign var=sc_product value=$it.product}
                        {assign var=sc_product_link value=$it.product_link}
                        {assign var=sc_product_image value=$it.product_image}
                        {assign var=sc_product_price value=$it.product_price}
                        {include file='module:shortcodes/views/templates/front/product_card.tpl'}
                    </li>
                {/for}
            {/if}
        {/if}
    </ul>
    <div class="swiper-navigation d-flex d-md-flex">
        <div class="prev swiper-button-prev" role="button" aria-label="Previous slide" tabindex="0"></div>
        <div class="next swiper-button-next" role="button" aria-label="Next slide" tabindex="0"></div>
    </div>
  </div>