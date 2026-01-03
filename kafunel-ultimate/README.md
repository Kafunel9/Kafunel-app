# Kafunel Live Match Ultimate

**Écosystème hybride pour la couverture de matchs de football (CAN, Coupe du Monde, LDC, CAF) incluant un Plugin WordPress Pro et une structure d'Application Mobile.**

## Description

Kafunel Live Match Ultimate est une solution complète pour la diffusion et la couverture de matchs de football en direct. Le plugin WordPress s'intègre parfaitement avec une application mobile React Native pour offrir une expérience utilisateur complète.

## Fonctionnalités

### Plugin WordPress
- **Logique de Fallback API** : Utilisation automatique d'API-Football en priorité, avec bascule vers RapidAPI en cas d'échec
- **Affichage en temps réel** : Scores, minute de jeu, buteurs, cartons, compositions
- **Bouton clignotant "LIVE STREAMING / DIRECT EN COURS"** : Animation CSS pour indiquer les matchs en direct
- **Tableau de bord journalistique** : Interface permettant de voir les données API et de les modifier manuellement
- **Système de paiement intégré** : Support pour Wave, Orange Money, YAS Money et PayPal
- **Partage social** : Génération de texte formaté pour WhatsApp/Facebook/X
- **Mode démo** : Fonctionne sans clés API avec des données de démonstration

### Application Mobile (React Native)
- **Connexion à l'API WordPress** : Récupération des scores en direct
- **Interface moderne** : Affichage des matchs, scores, événements et compositions
- **Expérience utilisateur optimisée** : Design intuitif et navigation fluide

## Installation

### Plugin WordPress
1. Téléchargez le plugin et décompressez-le dans le dossier `wp-content/plugins/` de votre installation WordPress
2. Activez le plugin depuis le tableau de bord WordPress
3. Allez dans `Paramètres > Kafunel Ultimate` pour configurer vos clés API et informations de paiement

### Application Mobile
1. Assurez-vous que votre environnement React Native est configuré
2. Remplacez l'URL `WORDPRESS_SITE_URL` dans `App.js` avec l'URL de votre site WordPress
3. Installez les dépendances : `npm install` ou `yarn install`
4. Lancez l'application : `npx react-native run-android` ou `npx react-native run-ios`

## Configuration

### Clés API
- **API Football Key** : Clé principale pour API-Football
- **RapidAPI Key** : Clé de secours pour RapidAPI

### Informations de Paiement
- Numéros Wave, Orange Money, YAS Money
- Lien PayPal.Me

### Informations de Contact
- Email et numéro de téléphone pour le support
- Numéro WhatsApp Business

## Utilisation

### Sur le site WordPress
- Utilisez le shortcode `[kafunel_live_match match_id="1"]` pour afficher un match spécifique
- Les données se mettent à jour automatiquement toutes les 30 secondes
- Le mode démo est activé si aucune clé API n'est configurée

### Dans l'application mobile
- Les matchs en direct sont affichés en temps réel
- Tirez vers le bas pour actualiser les données
- Cliquez sur un match pour voir les détails complets

## Sécurité

- Toutes les clés API et informations sensibles sont stockées de manière sécurisée dans les options WordPress
- Utilisation de nonces pour la sécurité des requêtes AJAX
- Validation et échappement de toutes les données utilisateur

## Personnalisation

Le plugin est entièrement personnalisable :
- Styles CSS modifiables dans `public/css/live-match.css`
- Modèles modifiables dans `public/templates/`
- Fonctionnalités extensibles via les classes dans `includes/`

## Support

Pour tout problème ou question, veuillez contacter l'équipe Kafunel via les coordonnées spécifiées dans les paramètres du plugin.

---

**Kafunel Live Match Ultimate** - *Votre solution complète pour la couverture de matchs de football*

Développé par : Kafunel  
Version : 1.0.0  
License : GPL v2 ou ultérieure