<p align="center">
  <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759b?style=for-the-badge&logo=wordpress&logoColor=white" alt="WordPress 5.8+">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 7.4+">
  <img src="https://img.shields.io/badge/License-GPLv2-blue?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Version-1.0.0-059669?style=for-the-badge" alt="Version">
</p>

# 🌾 AgroManager Pro

**Komplex mezőgazdasági vállalatirányítási rendszer WordPress-hez**

Országos szintű, mezőgazdálkodással foglalkozó cégek számára fejlesztett WordPress plugin, amely egyetlen admin felületen biztosítja a teljes gazdasági tevékenység nyomon követését – a földterületektől a pénzügyekig.

---

## ✨ Funkciók

### 🗺️ Földterület-kezelés
- Parcellák nyilvántartása (név, méret hektárban, helyszín)
- GPS koordináták tárolása
- Talajminőség értékelés (1-10 skála, vizuális indikátorral)
- Művelési ágak: szántó, rét, legelő, szőlő, gyümölcsös, kert, erdő stb.
- Státuszkezelés: aktív, pihentetett, bérbe adva, inaktív

### 🌱 Növénykultúra-nyilvántartás
- Kultúrák parcellákhoz kötése
- Vetési és betakarítási dátumok
- Várható és tényleges hozam követés (tonnában)
- Státusz: tervezett → elvetve → növekedésben → betakarítva

### 🚜 Géppark & Eszközkezelés
- Gépek leltára: traktor, kombájn, permetező, vetőgép, eke stb.
- Gyártó, rendszám, üzemóra nyilvántartás
- Állapotjelzés: üzemképes, karbantartás alatt, meghibásodott, kivonva
- **Szerviz figyelmeztetések** – automatikus jelzés a közelgő karbantartásokról

### 🌤️ Időjárás-integráció
- [Open-Meteo](https://open-meteo.com/) API (ingyenes, API kulcs nem szükséges)
- Aktuális időjárás: hőmérséklet, csapadék, szél, páratartalom
- **7 napos előrejelzés** vizuális kártyákkal
- Parcella GPS koordináta alapú lekérdezés
- 1 órás transient cache a teljesítmény érdekében

### 💰 Pénzügyi modul
- Bevételek és kiadások nyilvántartása
- Kategóriák: vetőmag, műtrágya, üzemanyag, szerviz, munkabér, értékesítés, támogatás stb.
- Parcellához és kultúrához rendelés
- **Éves összesítők** – bevétel, kiadás, eredmény
- **Chart.js havi diagramok** a dashboardon

### 👷 Dolgozó-kezelés
- Munkatársak nyilvántartása (név, beosztás, telefon, email)
- **Munkaidő napló** – dátum, kezdés/befejezés, tevékenység, parcella
- Automatikus óraszám számítás

### 📊 Dashboard
- KPI kártyák: összes terület, kultúrák, gépek, aktív dolgozók
- Pénzügyi összesítő éves bontásban
- Havi bevétel/kiadás oszlopdiagram (Chart.js)
- Időjárás widget
- Közelgő szervizek figyelmeztetés lista

---

## 📦 Telepítés

### Manuális telepítés

1. **Klónozd** a repót vagy töltsd le ZIP-ként:
   ```bash
   git clone https://github.com/your-username/agromanager-pro.git
   ```

2. **Másold** a mappát a WordPress plugins könyvtárba:
   ```bash
   cp -r agromanager-pro/ /path/to/wordpress/wp-content/plugins/agromanager-pro/
   ```

3. **Aktiváld** a plugint:  
   WordPress Admin → **Bővítmények** → **AgroManager Pro** → **Aktiválás**

4. Keresd az **🌾 AgroManager** menüpontot az admin oldalsávban

### Első lépések

1. Navigálj: **⚙️ Beállítások** → állítsd be a pénznemet és az alapértelmezett helyszínt
2. Adj hozzá **parcellákat** GPS koordinátákkal
3. Hozz létre **kultúrákat** és rendeld hozzá a parcellákhoz
4. Vezesd be a **gépeket** és a **dolgozókat**
5. A **Dashboard** automatikusan összesíti az adatokat

---

## 📁 Projekt struktúra

```
agromanager-pro/
├── agromanager-pro.php                    # Fő plugin fájl
├── readme.txt                             # WordPress.org readme
├── README.md                              # GitHub readme
│
├── admin/
│   ├── class-agromanager-admin.php        # Admin menü, dashboard, beállítások, routing
│   ├── css/
│   │   └── agromanager-admin.css          # Prémium admin stílusok
│   └── js/
│       └── agromanager-admin.js           # Animációk, Chart.js integráció
│
└── includes/
    ├── class-agromanager-activator.php    # DB táblák létrehozása (6 tábla)
    ├── class-agromanager-deactivator.php  # Cleanup (adatok megmaradnak)
    ├── class-agromanager-parcels.php      # Földterület modul
    ├── class-agromanager-crops.php        # Kultúra modul
    ├── class-agromanager-machines.php     # Géppark modul
    ├── class-agromanager-weather.php      # Időjárás modul
    ├── class-agromanager-finances.php     # Pénzügyi modul
    └── class-agromanager-workers.php      # Dolgozó modul
```

---

## 🗄️ Adatbázis

A plugin 6 egyedi táblát hoz létre aktiváláskor:

| Tábla | Leírás |
|-------|--------|
| `wp_agro_parcels` | Földterületek / parcellák |
| `wp_agro_crops` | Növénykultúrák (→ parcella FK) |
| `wp_agro_machines` | Géppark és eszközök |
| `wp_agro_finances` | Bevételek és kiadások (→ parcella/kultúra FK) |
| `wp_agro_workers` | Dolgozók |
| `wp_agro_work_logs` | Munkaidő bejegyzések (→ dolgozó/parcella FK) |

> **Megjegyzés:** A plugin deaktiválása **nem** törli az adatbázis táblákat, így az adatok megmaradnak.

---

## 🔒 Biztonság

- ✅ **Nonce** alapú CSRF védelem minden űrlapon
- ✅ `$wpdb::prepare()` – SQL injection elleni védelem
- ✅ `sanitize_text_field()`, `sanitize_textarea_field()`, `sanitize_email()` – input tisztítás
- ✅ `esc_html()`, `esc_attr()`, `esc_url()` – output escaping
- ✅ `current_user_can()` – jogosultság ellenőrzés (`manage_options`)

---

## 🛠️ Követelmények

| Követelmény | Minimum verzió |
|-------------|---------------|
| WordPress | 5.8+ |
| PHP | 7.4+ |
| MySQL | 5.7+ / MariaDB 10.3+ |

### Külső függőségek

- **[Chart.js](https://www.chartjs.org/)** v4.4 (CDN) – pénzügyi diagramok
- **[Open-Meteo API](https://open-meteo.com/)** – időjárási adatok (ingyenes, regisztráció nélkül)
- **[Google Fonts – Inter](https://fonts.google.com/specimen/Inter)** – admin tipográfia

---

## 🤝 Közreműködés

1. **Fork**-old a repót
2. Hozz létre egy feature branch-et: `git checkout -b feature/uj-funkcio`
3. Commitolj: `git commit -m 'Új funkció hozzáadása'`
4. Push-old: `git push origin feature/uj-funkcio`
5. Nyiss egy **Pull Request**-et

---

## 📄 Licenc

Ez a projekt a [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html) licenc alatt áll.

---

<p align="center">
  Készítette ❤️-vel mezőgazdasági vállalkozásoknak
</p>
