# 🛒 Online Grocery Store - Assignment 1

This project is a dynamic web application for an **online grocery store**, developed for the _Internet Programming (IP)_ course, Assignment 1.

The application simulates an e-commerce shopping experience with full frontend and backend integration using **HTML, CSS, JavaScript, PHP, and MySQL**.

---

## 📦 Features

- ✅ Product browsing by categories and sub-categories
- 🔍 Keyword-based search with hint text
- 🛍️ Shopping cart (session-based): add, update, remove, clear
- 🚫 "Add to cart" button disabled for out-of-stock items
- 📋 Delivery details form with validation
- 📦 Order placement with stock re-check and simulated confirmation
- 💬 Visual interaction (hover effects, disabled states)
- 📱 Responsive and accessible layout

---

## 💻 Tech Stack

| Layer    | Technology                         |
| -------- | ---------------------------------- |
| Frontend | HTML, CSS, JavaScript              |
| Backend  | PHP (session-based)                |
| Database | MySQL                              |
| Server   | PHP built-in server / XAMPP / MAMP |

---

## 🗂️ Folder Structure

```
/grocery-store/
├── index.php               # Homepage + search
├── category.php            # Category item display
├── cart.php                # Shopping cart view & update
├── checkout.php            # Delivery details form
├── confirm.php             # Order confirmation
├── db.php                  # Database connection
├── /css/style.css          # Main stylesheet
├── /js/script.js           # UI interactions
├── /img/                   # Product images
├── init.sql                # MySQL DB schema & sample data
└── README.md               # Project documentation
```

---

## 🗃️ Database

- The MySQL database contains one main table: `products`
- The structure and data are provided in the `init.sql` file
- Import the file using `phpMyAdmin` or MySQL CLI before running the app

---

## ▶️ Getting Started

1. Clone this repository
2. Import `init.sql` into your MySQL server
3. Update `db.php` with your database credentials
4. Run the project with PHP:
   ```bash
   php -S localhost:8000
   ```
5. Open [http://localhost:8000](http://localhost:8000) in your browser

---

## 🧑‍🎓 Assignment Info

- **Course:** Internet Programming (AUT2025)
- **Assignment:** A1 - Online Grocery Store
- **Student:** [Jin Lee]

---

## 📌 Notes

This project simulates core e-commerce functionalities without requiring login or payment gateway integration. It is intended as a course assignment submission only.

---

## 📷 Screenshots

> _(Optional)_ Include screenshots or screen recordings of your UI here.

---

## 📄 License

This project is for educational use only and not licensed for commercial distribution.

```

---
```
