# GitHub Actions Workflows

Ce répertoire contient tous les workflows GitHub Actions pour le pipeline CI/CD du projet AIG-App.

## Workflows Disponibles

### 1. `ci.yml` - Pipeline Principal CI/CD

**Déclenchement:**
- Push sur `main`, `develop`, `staging`
- Pull Requests vers ces branches

**Jobs:**
1. **Code Quality** - PHPStan, PHPCS, PHPMD, Pint
2. **Backend Tests** - Tests Pest/PHPUnit sur PHP 8.2 et 8.3
3. **Frontend Tests** - Tests Jest et build sur Node 18 et 20
4. **Security** - Audits Composer et npm
5. **Full Quality Check** - Commande `make quality` complète

**Durée:** ~12-15 minutes (parallèle)

---

### 2. `pr-checks.yml` - Vérifications Pull Requests

**Déclenchement:**
- Ouverture, synchronisation, ou réouverture d'une PR

**Jobs:**
1. **PR Analysis** - Analyse les fichiers modifiés et commente la PR
2. **PR Size Check** - Ajoute des labels de taille (XS, S, M, L, XL)

**Durée:** ~3-5 minutes

---

### 3. `dependency-review.yml` - Revue des Dépendances

**Déclenchement:**
- Tous les lundis à 9h00
- Manuellement (workflow_dispatch)
- PRs modifiant les fichiers de dépendances

**Jobs:**
1. **Check Outdated** - Liste les dépendances obsolètes
2. **Security Audit** - Audit de sécurité approfondi
3. **License Check** - Vérification des licences

**Durée:** ~5-7 minutes

---

## Utilisation Locale

Pour tester localement les commandes du pipeline:

```bash
# Simulation complète du pipeline
make quality
npm test
npm run build
composer audit
npm audit
```

## Artifacts Générés

- `coverage-report` - Rapport de couverture de code XML
- `frontend-build` - Assets frontend compilés
- `dependency-reports` - Rapports de dépendances obsolètes
- `security-reports` - Audits de sécurité JSON/texte
- `license-reports` - Rapports de licences

## Badges de Statut

Voir le README.md principal pour les badges de statut des workflows.

## Documentation Complète

Consultez [CI_CD.md](../../CI_CD.md) pour la documentation complète du pipeline.

---

**Note:** Ces workflows sont automatiques. Ils s'exécutent à chaque push/PR et ne nécessitent aucune configuration manuelle.
