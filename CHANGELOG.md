# Changelog ShortCodes

Toutes les évolutions notables du module sont listées ici.
Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions [SemVer](https://semver.org/lang/fr/).

## [1.0.12] — 2026-09-30

### Sécurité

- **Correctif de sécurité, mise à jour recommandée.** Le module interprétait les shortcodes dans tout le HTML de la page, y compris dans du texte saisi par les visiteurs, et ses paramètres n'étaient pas plafonnés : une requête anonyme pouvait déclencher un rendu très lourd et rendre la boutique indisponible. Le détail sera publié ultérieurement dans une note de sécurité.
- **Produits masqués.** `[product:ID]`, `[products:IDs]` et `[product-description:ID]` n'affichent plus un produit inactif, absent de la boutique courante ou réservé à d'autres groupes clients.

### Modifié

- **Où les shortcodes sont lus.** Plus de lecture de la page entière. Les shortcodes sont interprétés dans les contenus saisis en back-office : pages et catégories CMS, descriptions de produits, de catégories, de marques et de fournisseurs (hooks `filter*Content` du cœur), et blocs des modules de contenu choisis dans la configuration (réglage « Modules dont le contenu est lu », `ps_customtext` par défaut, c'est-à-dire le bloc texte de l'accueil). Ces blocs restent compatibles avec le cache Smarty : les prix et le stock sont calculés à chaque affichage. Si un shortcode de votre accueil ne s'affiche plus après la mise à jour, ajoutez le module qui le contient à ce réglage.
- **Plafonds.** 50 produits au plus par shortcode, 100 marques, 12 produits visibles par vue de slider, 50 shortcodes rendus par contenu.
- **PrestaShop 1.7.1 minimum** (les hooks `filter*Content` n'existent pas en 1.7.0).

## [1.0.11] — 2026-09-05

### Corrigé

- **Cartes produits présentées par le cœur, enfin.** `presentProduct()` instanciait `ProductAssembler` et `ProductPresenterFactory` sous l'espace de noms `PrestaShop\PrestaShop\Adapter\Presenter\Product\`, où ces deux classes n'ont jamais existé : ce sont des classes du cœur en espace de noms global. L'`Error` était avalée par le `catch (\Throwable)` et tous les shortcodes produits retombaient silencieusement sur la présentation dégradée, sans `has_discount`, `add_to_cart_url` et consorts. Trois correctifs dans le même bloc : classes du cœur (`\ProductAssembler`, `\ProductPresenterFactory`), `assembleProduct()` appelé avec un tableau (`['id_product' => ...]`) et non un objet `Product`, et lecture du `LazyArray` via `jsonSerialize()` — un transtypage `(array)` ne renvoyait que ses propriétés internes, pas les clés. Affecte les quatre points d'entrée : `[product:ID]`, `[products:IDs]`, `[category-products:ID]`, `[last-products:N]`.
- **Avertissements PHP dans le slider produits.** `product_slider.tpl` testait `$page_name`, variable PrestaShop 1.6 disparue en 1.7 : quatre avertissements par slider en mode debug, et trois branches conditionnelles inactives depuis toujours sur 1.7/8/9. Les branches ont été retirées plutôt que rebranchées : les réactiver appliquait `full-width-responsive overflow-visible` et `data-loop` sur l'accueil, ce qui produisait une barre de défilement horizontale sur toute la page et un carrousel non fonctionnel. Le rendu est identique à ce qu'il était réellement, sans les avertissements.

## [1.0.10] — 2026-08-21

### Modifié
- **Licence : GPL v3 vers Open Software License 3.0 (OSL-3.0).** Le cœur de PrestaShop est publié sous OSL-3.0, licence notoirement incompatible avec la GPL quelle que soit sa version. Un module ne pouvant fonctionner sans le cœur, la combinaison des deux ne peut satisfaire les deux copyleft à la fois, ce qui plaçait quiconque redistribue une boutique dans une situation insoluble. L'OSL-3.0 lève l'ambiguïté, aligne le module sur la licence de l'écosystème, et conserve ce qui comptait : l'obligation d'attribution et le partage des modifications. Les versions déjà publiées restent régies par la licence sous laquelle elles ont été distribuées.

## [1.0.9] — 2026-06-17

### Corrigé

- **Compatibilité PrestaShop 9** : remplacement des 5 appels à `Tools::displayPrice()` (méthode retirée en PS9) par un helper `formatPrice()` qui bascule automatiquement sur `Context::getCurrentLocale()->formatPrice()` lorsque la méthode legacy n'existe plus. Affecte tous les shortcodes qui rendent un prix (`[product:ID]`, `[products:IDs]`, `[last-products:N]`, sliders produits, etc.).

## [1.0.8] — 2026-06-14

Première publication open source.

### Open source (GPL v3)
- Module désormais libre et open source sous licence GPL v3 : code source ouvert, auditable, modifiable et redistribuable sans restriction. Aucune clé, aucune limite.
- Suppression du système de licence et de l'obfuscation : le rendu des shortcodes en front n'est plus conditionné à une activation.

### ZM40 Common (attribution + écosystème)
- Footer d'attribution discret et bloc « libre & open source » en page de configuration (panel natif, prestations sur devis, liens GitHub / contact / autres modules).
- Vérification de mise à jour notify-only via l'API publique GitHub Releases (au maximum une fois par jour, cache, fail-silent).
- Bloc « écosystème ZM40 » en page de config : autres modules depuis le feed zm40.com (masqué si feed indisponible).
- Interrupteur opt-out global ZM40_NET_ENABLED (activé par défaut) : désactive tout appel réseau. Requêtes anonymes, aucune donnée boutique transmise.

## Versions antérieures

Les versions 1.0.0 à 1.0.7 étaient distribuées sous licence propriétaire (avant le passage en open source) et n'ont pas de changelog public.
