# ShortCodes — Moteur de shortcodes pour PrestaShop

> Insérez des cartes produits, des sliders, des descriptions et des grilles de marques
> dans vos pages CMS, catégories, fiches produits et zones HTML, avec une syntaxe simple.

Module libre et open source (OSL 3.0) pour PrestaShop 1.7, 8 et 9.

![PrestaShop 1.7 → 9](https://img.shields.io/badge/PrestaShop-1.7%20%E2%86%92%209-blue) [![Téléchargements](https://img.shields.io/github/downloads/zenmod40/shortcodes/total.svg)](https://github.com/zenmod40/shortcodes/releases) [![Version](https://img.shields.io/github/v/release/zenmod40/shortcodes)](https://github.com/zenmod40/shortcodes/releases/latest)
![License: OSL 3.0](https://img.shields.io/badge/License-OSL--3.0-blue)

> 📦 **[Page du module sur zm40.com](https://zm40.com/shortcodes)** · [Documentation](https://zm40.com/shortcodes/documentation) · [Changelog](https://zm40.com/shortcodes/changelog)

## Fonctionnalités

- Shortcodes produits : `[product:ID]`, `[products:ID1,ID2,...]`, `[last-products:N]`, `[category:catId:limit:orderBy:orderWay]`.
- Descriptions : `[product-description:ID]` et `[product-description-short:ID]`.
- Marques : `[brands:limit:order]` (grille de fabricants actifs).
- Mode grille (par défaut) ou carrousel (`slider`), avec réglages responsives par breakpoint.
- Carrousel basé sur Swiper (CDN optionnel, désactivable si votre thème l'embarque déjà).
- Rendu via les presenters PrestaShop : prix, badges, images et URLs cohérents avec votre thème.
- Insertion partout : pages CMS, catégories, fiches produits et tout contenu HTML rendu en front.

## Compatibilité

PrestaShop 1.7, 8, 9 et PHP 7.2+. Aucune dépendance Composer requise.

> Une version legacy compatible ThirtyBees / PrestaShop 1.6 est disponible sur demande via [zm40.com](https://zm40.com).

## Installation

1. Télécharger la dernière release (`shortcodes.zip`).
2. Back-office PrestaShop, Modules, Téléverser un module.
3. Installer, puis ouvrir ShortCodes dans la liste des modules pour la configuration et le guide.

## Utilisation

Écrivez un shortcode directement dans le contenu d'une page CMS, d'une catégorie ou d'une fiche produit.

```
[products:10,20,30]
[products:10,20,30 slider]
[products:10,20,30 slider autoplay=true]
[category:5:8:price:asc slider]
[last-products:10 slider]
[product-description:123]
[brands:24:position]
```

Le guide complet (tous les paramètres) est disponible directement dans la page de configuration du module.

## Configuration

- Comportement global du slider (autoplay, espacement, vitesse, hauteur auto, centrage).
- Nombre d'articles visibles par breakpoint (Slides Per View), surchargeables par shortcode.
- Overrides spécifiques pour la page d'accueil et les pages CMS.
- Chargement des assets CSS/JS et de Swiper activable ou désactivable selon votre thème.

## Confidentialité

ShortCodes vérifie périodiquement (au maximum une fois par jour) si une nouvelle version est
disponible via l'API publique de GitHub, et récupère la liste des autres modules ZM40 depuis
zm40.com. Ces requêtes sont anonymes : aucune donnée de votre boutique n'est transmise (seule
l'adresse IP de votre serveur est visible, comme pour toute requête HTTP). Vous pouvez tout
désactiver dans la configuration du module (Mises à jour & écosystème, « Vérifier les mises à jour »).

## Support et services

ShortCodes est offert à la communauté, sans support garanti. Les issues GitHub sont les bienvenues
pour les bugs et les idées.

Besoin d'aide à l'installation, d'une adaptation sur mesure, de débogage ou de maintenance ?
Voir [zm40.com](https://zm40.com).

## Contribuer

Les pull requests sont bienvenues. Merci de garder le style du code et d'ouvrir une issue avant les
gros changements.

## Licence

OSL 3.0 — 2026 Nicolas Michaud — ZM40 / Magic Garden — [zm40.com](https://zm40.com)

Voir [LICENSE](LICENSE) pour le texte complet.
