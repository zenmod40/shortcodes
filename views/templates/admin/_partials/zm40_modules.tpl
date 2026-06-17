{* ZM40 Common — bloc « écosystème » en .panel natif (modules sans système d'onglets).
   Alimenté par $zm40_modules (Zm40Common::modulesFeed, module courant exclu, fail-silent).
   Rendu UNIQUEMENT si le feed renvoie des modules. Réutilise les classes .zm40-eco-* de zm40-common.css. *}
{if isset($zm40_modules) && $zm40_modules|@count}
<div class="panel zm40-eco-panel">
    <div class="panel-heading"><i class="icon-th-large"></i> L'écosystème ZM40</div>
    <p>D'autres modules PrestaShop, gratuits et open source. Installez ce dont vous avez besoin — le code est à vous.</p>
    <div class="zm40-eco-grid">
        {foreach from=$zm40_modules item=m}
            <div class="zm40-eco-card">
                <div class="zm40-eco-card-head">
                    {if $m.icon}<img class="zm40-eco-icon" src="{$m.icon|escape:'html':'UTF-8'}" alt="" loading="lazy">{/if}
                    <div class="zm40-eco-titles">
                        <span class="zm40-eco-name">{$m.name|escape:'html':'UTF-8'}</span>
                        {if $m.tagline}<span class="zm40-eco-tagline">{$m.tagline|escape:'html':'UTF-8'}</span>{/if}
                    </div>
                </div>
                <div class="zm40-eco-badges">
                    <span class="zm40-eco-badge">Gratuit &middot; Open source</span>
                    {if $m.installed}<span class="zm40-eco-badge zm40-eco-badge-installed">Déjà installé</span>{/if}
                </div>
                <div class="zm40-eco-links">
                    {if $m.url}<a href="{$m.url|escape:'html':'UTF-8'}" target="_blank" rel="noopener">Voir</a>{/if}{if $m.url && $m.github} &middot; {/if}{if $m.github}<a href="{$m.github|escape:'html':'UTF-8'}" target="_blank" rel="noopener">GitHub</a>{/if}
                </div>
            </div>
        {/foreach}
    </div>
</div>
{/if}
