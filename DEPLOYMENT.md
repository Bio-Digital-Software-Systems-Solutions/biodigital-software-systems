# 🚀 Déploiement et Configuration CI/CD

## Activation de la Documentation Automatique

### 1. GitHub Pages Configuration

1. **Activez GitHub Pages** dans votre repository :
   - Allez dans `Settings` → `Pages`
   - Source: **GitHub Actions**
   - Aucune configuration supplémentaire nécessaire

2. **Mettez à jour les URLs** dans le README :
   - Remplacez `VOTRE_USERNAME` par votre nom d'utilisateur GitHub
   - Exemple : `https://elmarce.github.io/icc-munich/docs`

### 2. Première Exécution

1. **Push le code** sur la branche `main` :
   ```bash
   git add .
   git commit -m "feat: add automated documentation generation"
   git push origin main
   ```

2. **Vérifiez le workflow** :
   - Allez dans `Actions` → `Generate Documentation`
   - Attendez la fin de l'exécution (~8-12 minutes)
   - La documentation sera disponible à : `https://VOTRE_USERNAME.github.io/icc-munich/docs`

## Utilisation Locale

### Configuration de l'environnement

1. **Dépendances système** (macOS) :
   ```bash
   # Déjà installées via nos sessions précédentes
   brew install graphviz librsvg
   npm install -g tsuml2
   ```

2. **Base de données** (optionnel pour SchemaSpy) :
   ```bash
   # Copiez et configurez SchemaSpy
   cp schemaspy.properties.example schemaspy.properties

   # Éditez avec vos paramètres de base de données
   nano schemaspy.properties
   ```

### Commandes de génération

```bash
# Génération complète (recommandé)
make docs-full

# Servir localement
make docs-serve  # http://localhost:8080

# Nettoyer
make docs-clean

# Commandes individuelles
make docs                    # PHPDocumentor only
make uml-diagram            # PHP UML diagram
make ts-uml-diagram         # TypeScript UML
make class-diagram          # PlantUML classes
make er-diagram             # Entity-Relationship
make convert-diagrams-png   # SVG → PNG conversion
```

## Structure de la Documentation

```
docs/
├── index.html                     # Hub principal
├── api-php/                       # Documentation PHP (PHPDocumentor)
│   ├── index.html
│   └── ...
├── api-typescript/                # Documentation TypeScript (TypeDoc)
│   ├── index.html
│   └── ...
├── database/                      # Schéma base de données (SchemaSpy)
│   ├── index.html
│   └── ...
└── diagrams/                      # Diagrammes UML
    ├── uml-class-diagram.svg      # PHP UML complet (1.1MB)
    ├── typescript-uml-diagram.svg # TypeScript UML (557KB)
    ├── class-diagram.svg          # Classes PlantUML (69KB)
    ├── entity-relationship-diagram.svg # ER diagram (569KB)
    └── *.png                      # Versions PNG haute résolution
```

## Personnalisation

### Styles et Thèmes

1. **Index HTML** : Modifiez `scripts/generate-docs.sh` section `generate_index()`
2. **PHPDocumentor** : Ajoutez un fichier `phpdoc.xml` personnalisé
3. **TypeDoc** : Modifiez `typedoc.json` pour le style
4. **SchemaSpy** : Ajoutez un CSS personnalisé dans `schemaspy.properties`

### Outils Supplémentaires

Pour ajouter des outils de documentation :

1. **Dans le workflow GitHub** (`.github/workflows/documentation.yml`) :
   - Section "Download documentation tools"
   - Section de génération appropriée

2. **Dans le script local** (`scripts/generate-docs.sh`) :
   - Fonction `download_tools()`
   - Nouvelle fonction de génération

3. **Dans le Makefile** :
   - Nouvelle commande
   - Mise à jour de l'aide

## Monitoring et Debug

### Logs d'exécution

```bash
# GitHub Actions
# Allez dans Actions → Generate Documentation → Logs

# Local
make docs-full 2>&1 | tee docs-generation.log
```

### Problèmes fréquents

1. **Permissions GitHub Pages** :
   - Vérifiez Settings → Pages → Source = "GitHub Actions"
   - Attendez quelques minutes après le premier déploiement

2. **Outils manquants** :
   ```bash
   # Vérifiez les dépendances
   which java    # Pour PlantUML/SchemaSpy
   which node    # Pour TypeDoc/tsuml2
   which php     # Pour PHPDocumentor
   which dot     # Pour Graphviz (diagrammes)
   ```

3. **Base de données** :
   - SchemaSpy nécessite une connexion DB active
   - Configurez `schemaspy.properties` ou définissez les variables d'environnement

4. **Mémoire insuffisante** :
   - Les gros projets peuvent nécessiter plus de mémoire
   - Ajustez les paramètres JVM si nécessaire

### Performance

- **Temps de génération** : 2-5 minutes localement, 8-12 minutes CI/CD
- **Taille typique** : 50-200MB de documentation
- **Cache** : Les outils sont mis en cache entre les exécutions

## Maintenance

### Mises à jour

```bash
# Outils de documentation
wget -O tools/phpDocumentor.phar https://github.com/phpDocumentor/phpDocumentor/releases/latest/download/phpDocumentor.phar

# Packages npm
npm update -g tsuml2

# Extensions Composer
composer update --dev
```

### Optimisations

1. **Excludes** : Ajoutez des patterns d'exclusion pour les fichiers non pertinents
2. **Cache** : Utilisez le cache GitHub Actions pour les dépendances
3. **Parallélisation** : Exécutez certains outils en parallèle
4. **Compression** : Optimisez la taille des diagrammes SVG/PNG

## Support

- **Documentation** : Consultez les README des outils individuels
- **Issues** : Ouvrez une issue GitHub pour les problèmes spécifiques
- **Logs** : Toujours joindre les logs d'exécution completes

---

Cette configuration fournit une documentation complète, automatisée et professionnelle pour votre projet Laravel + React. La documentation est mise à jour automatiquement à chaque push et accessible publiquement via GitHub Pages.