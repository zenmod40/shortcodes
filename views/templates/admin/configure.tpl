{* Admin configuration page for ShortCodes *}
<link rel="stylesheet" href="{$module_dir}views/css/zm40-common.css">

{* ===== ZM40 Admin Header (inliné) ===== *}
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
</style>
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

{* ===== ZM40 : notice de mise à jour (notify-only) ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_update.tpl"}

<div class="alert alert-info">
  <strong>{l s='Paramètres du module' mod='shortcodes'}</strong><br>
  {l s='Utilisez le formulaire ci-dessus pour régler le comportement du slider (global) et les overrides Accueil/CMS. Laissez vide un override pour hériter de la valeur globale.' mod='shortcodes'}
</div>
<div class="panel">
  <h3><i class="icon icon-cogs"></i> {$module_name} <small>v{$module_version}</small></h3>
  <p>
    {l s='Insérez des shortcodes (produits, sliders, descriptions, etc.) dans vos contenus CMS, catégories, produits et champs WYSIWYG.' mod='shortcodes'}
  </p>

  <hr>
  <h4>{l s='Guide d\'utilisation' mod='shortcodes'}</h4>

  <div class="well">
    <p>{l s='Par défaut, les listes s’affichent en grille. Ajoutez le paramètre' mod='shortcodes'} <code>slider</code> {l s='(ou' mod='shortcodes'} <code>view=slider</code>) {l s='pour activer le carrousel.' mod='shortcodes'}</p>

    <h5>Shortcodes disponibles</h5>
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

    <h5>Paramètres détaillés</h5>
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

    <h5>Exemples</h5>
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

    <h5>Notes</h5>
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

{* ===== ZM40 : bloc « libre & open source » + prestations ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_panel.tpl"}

{* ===== ZM40 : écosystème (autres modules) ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_modules.tpl"}

{* ===== ZM40 : footer d'attribution ===== *}
{include file="module:shortcodes/views/templates/admin/_partials/zm40_footer.tpl"}
