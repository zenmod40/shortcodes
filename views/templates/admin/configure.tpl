{* Admin configuration page for ShortCodes — design ZM40 avec onglets *}
<link rel="stylesheet" href="{$module_dir}views/css/zm40-common.css">

{* ===== Styles BO unifiés (header + tabs + panels) ===== *}
<style>
.zm40-ah {
    background: linear-gradient(135deg, #f97316 0%, #c2410c 100%);
    color: #fff;
    padding: 28px 32px;
    border-radius: 8px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.zm40-ah * { box-sizing: border-box; }
.zm40-ah-brand { display: flex; align-items: center; gap: 16px; }
.zm40-ah h2 { margin: 0; font-size: 22px; font-weight: 600; color: #fff; line-height: 1.2; }
.zm40-ah-sub { opacity: 0.85; font-size: 13px; margin-top: 4px; color: #fff; }
.zm40-ah-badge {
    background: rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    color: #fff;
    white-space: nowrap;
}

/* Onglets */
.zm40-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid #e5e7eb;
    margin: 0 0 24px;
    padding: 0;
    list-style: none;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
.zm40-tabs li {
    padding: 12px 22px;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    font-weight: 600;
    font-size: 14px;
    color: #6b7280;
    transition: all 0.15s ease;
    user-select: none;
}
.zm40-tabs li:hover { color: #f97316; }
.zm40-tabs li.is-active {
    color: #f97316;
    border-bottom-color: #f97316;
}
.zm40-tabs li .icon {
    margin-right: 8px;
    font-size: 13px;
    opacity: 0.7;
}
.zm40-tab-content {
    display: none;
    animation: zm40FadeIn 0.18s ease;
}
.zm40-tab-content.is-active { display: block; }
@keyframes zm40FadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

/* Guide pane */
.zm40-guide-panel .well code,
.zm40-guide-panel pre code,
.zm40-guide-panel pre {
    font-family: 'SF Mono', Monaco, 'Cascadia Mono', monospace;
    font-size: 13px;
}
.zm40-guide-panel pre {
    background: #1f2937;
    color: #e5e7eb;
    padding: 14px 18px;
    border-radius: 6px;
    overflow-x: auto;
}
</style>

{* ===== Header de marque ===== *}
<div class="zm40-ah">
    <div class="zm40-ah-brand">
        <div>
            <h2>{if isset($zm40_ah_name)}{$zm40_ah_name|escape:'html':'UTF-8'}{else}ZM40{/if}</h2>
            {if isset($zm40_ah_sub) || isset($zm40_ah_version)}
                <div class="zm40-ah-sub">
                    {if isset($zm40_ah_sub)}{$zm40_ah_sub|escape:'html':'UTF-8'}{/if}
                    {if isset($zm40_ah_sub) && isset($zm40_ah_version)} &middot; {/if}
                    {if isset($zm40_ah_version)}v{$zm40_ah_version|escape:'html':'UTF-8'}{/if}
                </div>
            {/if}
        </div>
    </div>
    {if isset($zm40_ah_shop) && $zm40_ah_shop}<span class="zm40-ah-badge">{$zm40_ah_shop|escape:'html':'UTF-8'}</span>{/if}
</div>

{* ===== Notice de mise à jour (notify-only) ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_update.tpl"}

{* ===== Onglets ===== *}
<ul class="zm40-tabs" id="zm40-tabs">
    <li class="is-active" data-tab="config">
        <i class="icon icon-cogs"></i>{l s='Configuration' mod='shortcodes'}
    </li>
    <li data-tab="guide">
        <i class="icon icon-book"></i>{l s='Guide d\'utilisation' mod='shortcodes'}
    </li>
    <li data-tab="ecosystem">
        <i class="icon icon-globe"></i>{l s='Écosystème ZM40' mod='shortcodes'}
    </li>
</ul>

{* ===== Onglet 1 : CONFIGURATION ===== *}
<div class="zm40-tab-content is-active" data-content="config">
    {$form_config nofilter}
</div>

{* ===== Onglet 2 : GUIDE D'UTILISATION ===== *}
<div class="zm40-tab-content zm40-guide-panel" data-content="guide">
    <div class="panel">
        <h3><i class="icon icon-book"></i> {l s='Guide d\'utilisation' mod='shortcodes'} <small>{$module_name|escape:'html':'UTF-8'} v{$module_version|escape:'html':'UTF-8'}</small></h3>
        <p>
            {l s='Insérez des shortcodes (produits, sliders, descriptions, etc.) dans vos contenus CMS, catégories, produits et champs WYSIWYG.' mod='shortcodes'}
        </p>

        <div class="well">
            <p>{l s='Par défaut, les listes s’affichent en grille. Ajoutez le paramètre' mod='shortcodes'} <code>slider</code> {l s='(ou' mod='shortcodes'} <code>view=slider</code>) {l s='pour activer le carrousel.' mod='shortcodes'}</p>

            <h5>{l s='Shortcodes disponibles' mod='shortcodes'}</h5>
            <ul>
                <li>
                    <strong>[product:ID]</strong><br>
                    {l s='Affiche une carte d’un seul produit.' mod='shortcodes'}
                </li>
                <li>
                    <strong>[products:ID1,ID2,ID3 ...] [slider]</strong><br>
                    {l s='Affiche une liste de produits par IDs.' mod='shortcodes'}
                </li>
                <li>
                    <strong>[category:catId:limit:orderBy:orderWay] [slider]</strong><br>
                    {l s='Affiche des produits d’une catégorie.' mod='shortcodes'}
                </li>
                <li>
                    <strong>[last-products:N] [slider]</strong><br>
                    {l s='Affiche les N derniers produits.' mod='shortcodes'}
                </li>
                <li>
                    <strong>[product-description:ID]</strong> / <strong>[product-description-short:ID]</strong><br>
                    {l s='Affiche la description longue ou courte du produit.' mod='shortcodes'}
                </li>
                <li>
                    <strong>[brands:limit:order]</strong><br>
                    {l s='Affiche une grille de marques actives (fabricants).' mod='shortcodes'}
                    <ul>
                        <li>{l s='Paramètres' mod='shortcodes'}: <em>limit</em> (24 par défaut), <em>order</em> = name | position | random</li>
                        <li>{l s='Syntaxes' mod='shortcodes'}: <code>[brands:24:position]</code> {l s='ou' mod='shortcodes'} <code>[brands 24 position]</code></li>
                        <li>{l s='Exemples' mod='shortcodes'}: <code>[brands]</code>, <code>[brands:12:random]</code>, <code>[brands 36 name]</code></li>
                    </ul>
                </li>
            </ul>

            <h5>{l s='Paramètres détaillés' mod='shortcodes'}</h5>
            <ul>
                <li>
                    <strong>[products:ID1,ID2,...]</strong> — IDs séparés par des virgules. Exemple: <code>[products:12,45,67]</code>
                </li>
                <li>
                    <strong>[category:catId:limit:orderBy:orderWay]</strong>
                    <ul>
                        <li><em>catId</em>: ID catégorie (obligatoire)</li>
                        <li><em>limit</em>: nombre de produits (défaut 8)</li>
                        <li><em>orderBy</em>: <code>random</code>, <code>price</code>, <code>name</code>, <code>id_product</code>, <code>manufacturer</code>, <code>position</code>, <code>date_add</code>, <code>date_upd</code></li>
                        <li><em>orderWay</em>: <code>asc</code> ou <code>desc</code> (ignoré si <code>orderBy=random</code>)</li>
                    </ul>
                </li>
                <li>
                    <strong>[last-products:N]</strong> — N produits récents (défaut 8). Exemple: <code>[last-products:10]</code>
                </li>
                <li>
                    <strong>slider</strong> (optionnel) — active le carrousel. Exemple: <code>[products:12,45,67 slider]</code>
                </li>
                <li>
                    <strong>autoplay</strong> (optionnel) — surcharge par instance de la lecture auto.
                    <ul>
                        <li>Utiliser un booléen explicite: <code>autoplay=true</code> ou <code>autoplay=false</code></li>
                        <li>Exemples: <code>[category:6:12:price:asc slider autoplay=true]</code>, <code>[products:1,2,3 slider autoplay=false]</code></li>
                        <li>Remarque: le token seul <code>autoplay</code> (sans « = ») est ignoré.</li>
                    </ul>
                </li>
            </ul>

            <h5>{l s='Exemples' mod='shortcodes'}</h5>
            <pre>[products:10,20,30]
[products:10,20,30 slider]
[products:10,20,30 slider autoplay=true]
[category:5:8:price:asc slider]
[category:6:12:price:asc slider autoplay=true]
[last-products:10 slider]
[product-description:123]
[product-description-short:123]
[brands:24:position]
[brands 12 random]</pre>

            <h5>{l s='Notes' mod='shortcodes'}</h5>
            <ul>
                <li>{l s='IDs invalides ou inactifs : aucun rendu.' mod='shortcodes'}</li>
                <li>{l s='Avec orderBy=random, orderWay est ignoré.' mod='shortcodes'}</li>
                <li>{l s='Le mode slider utilise les presenters PrestaShop pour un rendu cohérent (prix, badges, images, URLs, etc.).' mod='shortcodes'}</li>
            </ul>
        </div>

        <p class="help-block">
            {l s='Consultez également le fichier README.md du module pour le même guide.' mod='shortcodes'}
        </p>
    </div>
</div>

{* ===== Onglet 3 : ÉCOSYSTÈME ZM40 ===== *}
<div class="zm40-tab-content" data-content="ecosystem">
    {$form_ecosystem nofilter}

    {* Bloc « libre & open source » + prestations *}
    {include file="module:shortcodes/views/templates/admin/_partials/zm40_panel.tpl"}

    {* Autres modules ZM40 (depuis le feed) *}
    {include file="module:shortcodes/views/templates/admin/_partials/zm40_modules.tpl"}
</div>

{* ===== Footer d'attribution (visible sur tous les onglets) ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_footer.tpl"}

{* ===== JS Vanilla : switching d'onglets (compatible PS 1.7 / 8 / 9 sans dépendance Bootstrap) ===== *}
<script>
(function () {
    var tabs = document.querySelectorAll('#zm40-tabs li');
    var contents = document.querySelectorAll('.zm40-tab-content');
    tabs.forEach(function (li) {
        li.addEventListener('click', function () {
            var target = li.getAttribute('data-tab');
            tabs.forEach(function (x) { x.classList.remove('is-active'); });
            contents.forEach(function (x) { x.classList.remove('is-active'); });
            li.classList.add('is-active');
            var pane = document.querySelector('.zm40-tab-content[data-content="' + target + '"]');
            if (pane) pane.classList.add('is-active');
            try { localStorage.setItem('zm40_shortcodes_tab', target); } catch (e) {}
        });
    });
    // Restore last tab on reload (optional, no breakage if localStorage unavailable)
    try {
        var last = localStorage.getItem('zm40_shortcodes_tab');
        if (last) {
            var li = document.querySelector('#zm40-tabs li[data-tab="' + last + '"]');
            if (li) li.click();
        }
    } catch (e) {}
})();
</script>
