{* brands grid - templates-only rendering *}
{if isset($sc_brands) && $sc_brands|@count > 0}
<div class="mgsc-brand-grid{if isset($sc_brand_grid_classes) && $sc_brand_grid_classes} {$sc_brand_grid_classes|escape:'html':'UTF-8'}{/if}" role="list">
  {foreach from=$sc_brands item=brand}
    <a class="mgsc-brand-grid__item" role="listitem" href="{$brand.link|escape:'html':'UTF-8'}" title="{$brand.name|escape:'html':'UTF-8'}">
      {if $brand.logo}
        <img class="mgsc-brand-grid__logo" src="{$brand.logo|escape:'html':'UTF-8'}" alt="{$brand.name|escape:'html':'UTF-8'}" loading="lazy" />
      {else}
        <span class="mgsc-brand-grid__name">{$brand.name|escape:'html':'UTF-8'}</span>
      {/if}
    </a>
  {/foreach}
</div>
{/if}
