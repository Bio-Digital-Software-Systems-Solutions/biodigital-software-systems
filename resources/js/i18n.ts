import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';

// Translation files
const resources = {
  fr: {
    translation: {
      // Navigation
      "dashboard": "Tableau de bord",
      "events": "Événements",
      "books": "Bibliothèque",
      "articles": "Articles",
      "chat": "Chat",
      "profile": "Profil",
      "logout": "Déconnexion",
      "login": "Connexion",
      "register": "Inscription",

      // Common actions
      "create": "Créer",
      "edit": "Modifier",
      "delete": "Supprimer",
      "save": "Enregistrer",
      "cancel": "Annuler",
      "search": "Rechercher",
      "filter": "Filtrer",
      "clear": "Effacer",
      "view": "Voir",
      "back": "Retour",
      "next": "Suivant",
      "previous": "Précédent",
      "loading": "Chargement...",
      "submit": "Soumettre",

      // Dashboard
      "dashboard.welcome": "Bienvenue sur AIG-App",
      "dashboard.overview": "Aperçu de votre organisation",
      "dashboard.quickActions": "Actions rapides",
      "dashboard.recentActivity": "Activité récente",
      "dashboard.statistics": "Statistiques",
      "dashboard.events": "événements",
      "dashboard.books": "livres",
      "dashboard.articles": "articles",
      "dashboard.users": "utilisateurs",

      // Events
      "events.title": "Événements",
      "events.description": "Gérez et participez aux événements de votre organisation",
      "events.create": "Nouvel événement",
      "events.noEvents": "Aucun événement",
      "events.noEventsDescription": "Commencez par créer votre premier événement",
      "events.status.planned": "Planifié",
      "events.status.ongoing": "En cours",
      "events.status.completed": "Terminé",
      "events.status.cancelled": "Annulé",
      "events.participants": "participants",
      "events.viewDetails": "Voir détails",
      "events.join": "Rejoindre",
      "events.leave": "Quitter",

      // Books
      "books.title": "Bibliothèque",
      "books.description": "Découvrez et louez des livres de votre organisation",
      "books.create": "Ajouter un livre",
      "books.noBooks": "Aucun livre trouvé",
      "books.noBooksDescription": "Commencez par ajouter votre premier livre",
      "books.available": "Disponible",
      "books.unavailable": "Indisponible",
      "books.rent": "Louer un livre",
      "books.author": "Auteur",
      "books.category": "Catégorie",
      "books.isbn": "ISBN",
      "books.descriptionLabel": "Description",
      "books.rentalPrice": "Prix de location",
      "books.maxRentalDays": "Durée maximale",
      "books.stock": "Stock",
      "stock.stock": "Gestion de stock",

      // Articles
      "articles.title": "Articles",
      "articles.description": "Découvrez et partagez des articles avec votre organisation",
      "articles.create": "Nouvel article",
      "articles.noArticles": "Aucun article trouvé",
      "articles.noArticlesDescription": "Commencez par créer votre premier article",
      "articles.status.published": "Publié",
      "articles.status.draft": "Brouillon",
      "articles.readArticle": "Lire l'article",
      "articles.publishedOn": "Publié le",
      "articles.createdOn": "Créé le",

      // Chat
      "chat.title": "Messages",
      "chat.noConversations": "Aucune conversation",
      "chat.noConversationsDescription": "Créez votre première conversation",
      "chat.newConversation": "Nouvelle conversation",
      "chat.selectConversation": "Sélectionnez une conversation",
      "chat.selectConversationDescription": "Choisissez une conversation existante ou créez-en une nouvelle",
      "chat.typeMessage": "Tapez votre message...",
      "chat.searchUsers": "Rechercher des utilisateurs...",
      "chat.participants": "participants",

      // Forms
      "form.required": "Obligatoire",
      "form.optional": "Optionnel",
      "form.title": "Titre",
      "form.description": "Description",
      "form.content": "Contenu",
      "form.startDate": "Date de début",
      "form.endDate": "Date de fin",
      "form.location": "Lieu",
      "form.maxParticipants": "Participants maximum",
      "form.isPublic": "Public",
      "form.status": "Statut",
      "form.category": "Catégorie",
      "form.tags": "Tags",
      "form.featuredImage": "Image mise en avant",

      // Messages
      "message.success": "Succès",
      "message.error": "Erreur",
      "message.warning": "Attention",
      "message.info": "Information",
      "message.confirmDelete": "Êtes-vous sûr de vouloir supprimer cet élément ?",

      // Theme
      "theme.light": "Clair",
      "theme.dark": "Sombre",
      "theme.system": "Système",

      // Languages
      "language.fr": "Français",
      "language.en": "English",
      "language.de": "Deutsch",
    }
  },
  en: {
    translation: {
      // Navigation
      "dashboard": "Dashboard",
      "events": "Events",
      "books": "Books",
      "articles": "Articles",
      "chat": "Chat",
      "profile": "Profile",
      "logout": "Logout",
      "login": "Login",
      "register": "Register",

      // Common actions
      "create": "Create",
      "edit": "Edit",
      "delete": "Delete",
      "save": "Save",
      "cancel": "Cancel",
      "search": "Search",
      "filter": "Filter",
      "clear": "Clear",
      "view": "View",
      "back": "Back",
      "next": "Next",
      "previous": "Previous",
      "loading": "Loading...",
      "submit": "Submit",

      // Dashboard
      "dashboard.welcome": "Welcome to AIG-App",
      "dashboard.overview": "Overview of your organization",
      "dashboard.quickActions": "Quick actions",
      "dashboard.recentActivity": "Recent activity",
      "dashboard.statistics": "Statistics",
      "dashboard.events": "events",
      "dashboard.books": "books",
      "dashboard.articles": "articles",
      "dashboard.users": "users",

      // Events
      "events.title": "Events",
      "events.description": "Manage and participate in your organization's events",
      "events.create": "New event",
      "events.noEvents": "No events",
      "events.noEventsDescription": "Start by creating your first event",
      "events.status.planned": "Planned",
      "events.status.ongoing": "Ongoing",
      "events.status.completed": "Completed",
      "events.status.cancelled": "Cancelled",
      "events.participants": "participants",
      "events.viewDetails": "View details",
      "events.join": "Join",
      "events.leave": "Leave",

      // Books
      "books.title": "Library",
      "books.description": "Discover and rent books from your organization",
      "books.create": "Add book",
      "books.noBooks": "No books found",
      "books.noBooksDescription": "Start by adding your first book",
      "books.available": "Available",
      "books.unavailable": "Unavailable",
      "books.rent": "Rent a book",
      "books.author": "Author",
      "books.category": "Category",
      "books.isbn": "ISBN",
      "books.descriptionLabel": "Description",
      "books.rentalPrice": "Rental price",
      "books.maxRentalDays": "Maximum duration",
      "books.stock": "Stock",

      // Articles
      "articles.title": "Articles",
      "articles.description": "Discover and share articles with your organization",
      "articles.create": "New article",
      "articles.noArticles": "No articles found",
      "articles.noArticlesDescription": "Start by creating your first article",
      "articles.status.published": "Published",
      "articles.status.draft": "Draft",
      "articles.readArticle": "Read article",
      "articles.publishedOn": "Published on",
      "articles.createdOn": "Created on",

      // Chat
      "chat.title": "Messages",
      "chat.noConversations": "No conversations",
      "chat.noConversationsDescription": "Create your first conversation",
      "chat.newConversation": "New conversation",
      "chat.selectConversation": "Select a conversation",
      "chat.selectConversationDescription": "Choose an existing conversation or create a new one",
      "chat.typeMessage": "Type your message...",
      "chat.searchUsers": "Search users...",
      "chat.participants": "participants",

      // Forms
      "form.required": "Required",
      "form.optional": "Optional",
      "form.title": "Title",
      "form.description": "Description",
      "form.content": "Content",
      "form.startDate": "Start date",
      "form.endDate": "End date",
      "form.location": "Location",
      "form.maxParticipants": "Maximum participants",
      "form.isPublic": "Public",
      "form.status": "Status",
      "form.category": "Category",
      "form.tags": "Tags",
      "form.featuredImage": "Featured image",

      // Messages
      "message.success": "Success",
      "message.error": "Error",
      "message.warning": "Warning",
      "message.info": "Information",
      "message.confirmDelete": "Are you sure you want to delete this item?",

      // Theme
      "theme.light": "Light",
      "theme.dark": "Dark",
      "theme.system": "System",

      // Languages
      "language.fr": "Français",
      "language.en": "English",
      "language.de": "Deutsch",
    }
  },
  de: {
    translation: {
      // Navigation
      "dashboard": "Dashboard",
      "events": "Veranstaltungen",
      "books": "Bücher",
      "articles": "Artikel",
      "chat": "Chat",
      "profile": "Profil",
      "logout": "Abmelden",
      "login": "Anmelden",
      "register": "Registrieren",

      // Common actions
      "create": "Erstellen",
      "edit": "Bearbeiten",
      "delete": "Löschen",
      "save": "Speichern",
      "cancel": "Abbrechen",
      "search": "Suchen",
      "filter": "Filtern",
      "clear": "Löschen",
      "view": "Ansehen",
      "back": "Zurück",
      "next": "Weiter",
      "previous": "Vorherige",
      "loading": "Laden...",
      "submit": "Absenden",

      // Dashboard
      "dashboard.welcome": "Willkommen bei AIG-App",
      "dashboard.overview": "Überblick über Ihre Organisation",
      "dashboard.quickActions": "Schnelle Aktionen",
      "dashboard.recentActivity": "Letzte Aktivität",
      "dashboard.statistics": "Statistiken",
      "dashboard.events": "Veranstaltungen",
      "dashboard.books": "Bücher",
      "dashboard.articles": "Artikel",
      "dashboard.users": "Benutzer",

      // Events
      "events.title": "Veranstaltungen",
      "events.description": "Verwalten Sie Veranstaltungen Ihrer Organisation und nehmen Sie daran teil",
      "events.create": "Neue Veranstaltung",
      "events.noEvents": "Keine Veranstaltungen",
      "events.noEventsDescription": "Erstellen Sie Ihre erste Veranstaltung",
      "events.status.planned": "Geplant",
      "events.status.ongoing": "Laufend",
      "events.status.completed": "Abgeschlossen",
      "events.status.cancelled": "Abgesagt",
      "events.participants": "Teilnehmer",
      "events.viewDetails": "Details ansehen",
      "events.join": "Beitreten",
      "events.leave": "Verlassen",

      // Books
      "books.title": "Bibliothek",
      "books.description": "Entdecken und mieten Sie Bücher Ihrer Organisation",
      "books.create": "Buch hinzufügen",
      "books.noBooks": "Keine Bücher gefunden",
      "books.noBooksDescription": "Fügen Sie Ihr erstes Buch hinzu",
      "books.available": "Verfügbar",
      "books.unavailable": "Nicht verfügbar",
      "books.rent": "Mieten",
      "books.author": "Autor",
      "books.category": "Kategorie",
      "books.isbn": "ISBN",
      "books.descriptionLabel": "Beschreibung",
      "books.rentalPrice": "Mietpreis",
      "books.maxRentalDays": "Maximale Dauer",
      "books.stock": "Bestand",

      // Articles
      "articles.title": "Artikel",
      "articles.description": "Entdecken und teilen Sie Artikel mit Ihrer Organisation",
      "articles.create": "Neuer Artikel",
      "articles.noArticles": "Keine Artikel gefunden",
      "articles.noArticlesDescription": "Erstellen Sie Ihren ersten Artikel",
      "articles.status.published": "Veröffentlicht",
      "articles.status.draft": "Entwurf",
      "articles.readArticle": "Artikel lesen",
      "articles.publishedOn": "Veröffentlicht am",
      "articles.createdOn": "Erstellt am",

      // Chat
      "chat.title": "Nachrichten",
      "chat.noConversations": "Keine Unterhaltungen",
      "chat.noConversationsDescription": "Erstellen Sie Ihre erste Unterhaltung",
      "chat.newConversation": "Neue Unterhaltung",
      "chat.selectConversation": "Wählen Sie eine Unterhaltung",
      "chat.selectConversationDescription": "Wählen Sie eine vorhandene Unterhaltung oder erstellen Sie eine neue",
      "chat.typeMessage": "Geben Sie Ihre Nachricht ein...",
      "chat.searchUsers": "Benutzer suchen...",
      "chat.participants": "Teilnehmer",

      // Forms
      "form.required": "Erforderlich",
      "form.optional": "Optional",
      "form.title": "Titel",
      "form.description": "Beschreibung",
      "form.content": "Inhalt",
      "form.startDate": "Startdatum",
      "form.endDate": "Enddatum",
      "form.location": "Ort",
      "form.maxParticipants": "Maximale Teilnehmer",
      "form.isPublic": "Öffentlich",
      "form.status": "Status",
      "form.category": "Kategorie",
      "form.tags": "Tags",
      "form.featuredImage": "Hauptbild",

      // Messages
      "message.success": "Erfolg",
      "message.error": "Fehler",
      "message.warning": "Warnung",
      "message.info": "Information",
      "message.confirmDelete": "Sind Sie sicher, dass Sie dieses Element löschen möchten?",

      // Theme
      "theme.light": "Hell",
      "theme.dark": "Dunkel",
      "theme.system": "System",

      // Languages
      "language.fr": "Français",
      "language.en": "English",
      "language.de": "Deutsch",
    }
  }
};

i18n
  .use(initReactI18next)
  .init({
    resources,
    lng: 'fr', // default language
    fallbackLng: 'fr',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;