# Changelog ShortCodes

Toutes les évolutions notables du module sont listées ici.
Format inspiré de [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), versions [SemVer](https://semver.org/lang/fr/).

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
