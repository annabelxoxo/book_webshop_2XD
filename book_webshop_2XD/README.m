book_webshop_2XD/
│
├── index.php                  # Homepage (featured / best sellers)
├── catalog.php                # Alle boeken + filter op genre + search
├── product.php                # Detailpagina van 1 boek
│
├── register.php               # Account aanmaken
├── login.php                  # Inloggen
├── logout.php                 # Uitloggen
├── change_password.php        # Wachtwoord wijzigen
│
├── cart.php                   # Winkelmandje bekijken/bewerken
├── checkout.php               # Afrekenen met units
│
├── orders.php                 # Overzicht van bestellingen
├── order.php                  # Detail van 1 bestelling
│
├── includes/
│   ├── config.php             # PDO database connectie
│   ├── auth.php               # Login/admin checks
│   ├── header.php             # HTML header + navigation
│   ├── footer.php             # HTML footer
│
├── classes/
│   ├── Database.php           # PDO wrapper (optioneel maar netjes)
│   ├── User.php               # Register, login, wallet, password
│   ├── Book.php               # CRUD boeken + search/filter
│   ├── Cart.php               # Add/remove/update cart
│   ├── Order.php              # Orders aanmaken + ophalen
│   ├── Review.php             # Reviews + aankoop-check
│
├── admin/
│   ├── dashboard.php          # Admin overzicht
│   ├── product_new.php        # Product toevoegen
│   ├── product_edit.php       # Product bewerken
│   ├── product_delete.php     # Product verwijderen
│
├── api/
│   ├── review_add.php         # AJAX: review toevoegen
│   ├── reviews_list.php       # AJAX: reviews ophalen
│
├── assets/
│   ├── css/
│   │   └── styles.css         # Alle CSS
│   ├── js/
│   │   └── script.js          # JS + AJAX
│   └── images/
│       └── *.jpg              # Boek covers & icons
│
└── README.md                  # (optioneel) uitleg project



elke pagina waar long in vereist moet dit in 
require __DIR__ . "/includes/auth.php";
requireLogin();

bij elke admin pagina moet dit in
require __DIR__ . "/../includes/auth.php";
requireAdmin();



Hashing zet een wachtwoord om in een onomkeerbare, veilige string die gebruikt wordt om wachtwoorden te verifiëren zonder ze op te slaan.