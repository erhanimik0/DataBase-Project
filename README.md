{\rtf1\ansi\ansicpg1254\cocoartf2867
\cocoatextscaling0\cocoaplatform0{\fonttbl\f0\fswiss\fcharset0 Helvetica;}
{\colortbl;\red255\green255\blue255;}
{\*\expandedcolortbl;;}
\margl1440\margr1440\vieww50660\viewh25240\viewkind0
\pard\tx720\tx1440\tx2160\tx2880\tx3600\tx4320\tx5040\tx5760\tx6480\tx7200\tx7920\tx8640\pardirnatural\partightenfactor0

\f0\fs72 \cf0 # \uc0\u55356 \u57318  Bank of \u304 st\'fcn - Advanced Banking Automation System\
\
![Project Status](https://img.shields.io/badge/status-active-success.svg)\
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)\
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)\
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)\
\
## \uc0\u55357 \u56534  Project Overview\
\
**Bank of \uc0\u304 st\'fcn** is a comprehensive web-based banking simulation developed as a final project for the **Database Management Systems** course at \u304 stinye University.\
\
Unlike simple CRUD applications, this project focuses on **real-world financial data integrity**, **security**, and **complex business logic**. It simulates a full-banking environment with three distinct user roles: **Admin (Executive), Staff, and Customer.**\
\
---\
\
## \uc0\u55357 \u56960  Key Features\
\
### \uc0\u55357 \u56594  Security & Architecture\
* **ACID Compliance:** Utilizes MySQL **Transactions (Commit/Rollback)** for all fund transfers to ensure zero data loss during critical operations.\
* **Secure Authentication:** Implements **PDO Prepared Statements** to prevent SQL Injection and **MD5 hashing** for password storage.\
* **RBAC (Role-Based Access Control):** Strict separation of duties between Admins, Staff, and Customers using session-based authorization.\
\
### \uc0\u55357 \u56420  Customer Portal\
* **Dashboard:** View total assets, account details (IBAN, Balance), and recent activity.\
* **Money Transfers:** Secure internal and external transfers with **automatic currency conversion** (e.g., TRY to USD) based on real-time exchange rates.\
* **Loan Application:** Dynamic loan calculator and application submission module.\
* **Time Deposit (Faiz):** Interactive interest yield calculator for savings accounts.\
* **Digital Receipts:** Generate and view official transaction receipts (Dekont) for every operation.\
\
### \uc0\u55357 \u56508  Staff Panel\
* **Customer Management:** View and manage customer portfolios assigned to specific branches.\
* **Loan Operations:** Review, approve, or reject pending loan applications.\
* **Branch Oversight:** Monitor branch-specific liquidity and customer counts.\
\
### \uc0\u55357 \u56522  Executive (Admin) Dashboard\
* **Liquidity Tracking:** Real-time view of the bank's total deposits and assets across all branches.\
* **System Monitoring:** Live feed of all transaction logs and user activities system-wide.\
* **Performance Metrics:** Visual breakdown of active customers and staff distribution.\
\
---\
\
## \uc0\u55357 \u57056  Tech Stack\
\
* **Backend:** PHP 8 (Object-Oriented Programming & PDO)\
* **Database:** MySQL (Relational Schema, Stored Procedures, Triggers)\
* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript (for dynamic calculations)\
* **Tools:** VS Code, MAMP/XAMPP, phpMyAdmin\
\
---\
\
## \uc0\u55357 \u56772  Database Structure\
\
The project features a highly normalized database schema designed to minimize redundancy.\
\
* **Tables:** `Users`, `Customers`, `Employees`, `Accounts`, `Transactions`, `Loans`, `Branches`, `AuditLogs`.\
* **Logic:** Foreign keys are strictly enforced to maintain referential integrity.\
* **Stored Procedures:** Used for complex login verification and transaction history retrieval.\
\
---\
\
## \uc0\u9881 \u65039  Installation & Setup\
\
1.  **Clone the Repository**\
    ```bash\
    git clone [https://github.com/yourusername/bank-of-istun.git](https://github.com/yourusername/bank-of-istun.git)\
    ```\
\
2.  **Database Setup**\
    * Open `phpMyAdmin`.\
    * Create a new database named `banka_db_erhan`.\
    * Import the `banka_db.sql` file located in the `sql/` folder.\
\
3.  **Configuration**\
    * Open `includes/db.php`.\
    * Update the database credentials (`host`, `username`, `password`) to match your local environment.\
\
4.  **Run the Project**\
    * Move the project folder to your local server directory (`htdocs` for MAMP/XAMPP).\
    * Visit `http://localhost/bank_of_istun` in your browser.\
\
---\
\
## \uc0\u55357 \u56568  Screenshots\
\
| Executive Dashboard | Customer Transfer |\
|:-------------------------:|:-------------------------:|\
| ![Boss Screen](path/to/your/image1.jpg) | ![Transfer](path/to/your/image2.jpg) |\
\
| Digital Receipt (Dekont) | Loan Application |\
|:-------------------------:|:-------------------------:|\
| ![Receipt](path/to/your/image3.jpg) | ![Loan](path/to/your/image4.jpg) |\
\
*(Note: Screenshots are placeholders. Please update image paths.)*\
\
---\
\
## \uc0\u55357 \u56424 \u8205 \u55357 \u56507  Author\
\
**Erhan \uc0\u304 M\u304 K**\
* Computer Engineering Student @ \uc0\u304 stanbul Health and Technology University\
* [LinkedIn Profile](https://www.linkedin.com/in/erhan-imik-781809229/)\
\
---\
\
*This project is for educational purposes.*\
\
\'97\'97\'97\'97\'97\'97\'97\
\
# \uc0\u55356 \u57318  Bank of \u304 st\'fcn - Geli\u351 mi\u351  Bankac\u305 l\u305 k Otomasyon Sistemi\
\
![Proje Durumu](https://img.shields.io/badge/durum-aktif-success.svg)\
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)\
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)\
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap&logoColor=white)\
\
## \uc0\u55357 \u56534  Proje Hakk\u305 nda\
\
**Bank of \uc0\u304 st\'fcn**, \u304 stinye \'dcniversitesi **Veritaban\u305  Y\'f6netim Sistemleri** dersi final projesi kapsam\u305 nda geli\u351 tirilmi\u351 , u\'e7tan uca bir web tabanl\u305  bankac\u305 l\u305 k sim\'fclasyonudur.\
\
Basit veri kay\uc0\u305 t uygulamalar\u305 n\u305 n aksine, bu proje **ger\'e7ek d\'fcnya finansal veri b\'fct\'fcnl\'fc\u287 \'fc**, **g\'fcvenlik** ve **karma\u351 \u305 k i\u351  mant\u305 \u287 \u305 ** \'fczerine kurgulanm\u305 \u351 t\u305 r. Sistem; **Y\'f6netici (Admin), Personel ve M\'fc\u351 teri** olmak \'fczere \'fc\'e7 farkl\u305  kullan\u305 c\u305  rol\'fcn\'fc sim\'fcle eder.\
\
---\
\
## \uc0\u55357 \u56960  Temel \'d6zellikler\
\
### \uc0\u55357 \u56594  G\'fcvenlik ve Mimari\
* **ACID Uyumlulu\uc0\u287 u:** Kritik finansal i\u351 lemlerde veri kayb\u305 n\u305  \'f6nlemek i\'e7in MySQL **Transaction (Commit/Rollback)** yap\u305 s\u305  kullan\u305 lm\u305 \u351 t\u305 r.\
* **G\'fcvenli Kimlik Do\uc0\u287 rulama:** SQL Injection sald\u305 r\u305 lar\u305 na kar\u351 \u305  **PDO Prepared Statements** ve \u351 ifre g\'fcvenli\u287 i i\'e7in **MD5** hashing kullan\u305 lm\u305 \u351 t\u305 r.\
* **RBAC (Rol Tabanl\uc0\u305  Yetkilendirme):** Y\'f6netici, Personel ve M\'fc\u351 teri panelleri aras\u305 nda oturum bazl\u305  (session) kat\u305  eri\u351 im kontrolleri mevcuttur.\
\
### \uc0\u55357 \u56420  M\'fc\u351 teri Paneli\
* **Hesap \'d6zeti:** Toplam varl\uc0\u305 klar, hesap detaylar\u305  (IBAN, Bakiye) ve son hareketlerin takibi.\
* **Para Transferleri:** Ger\'e7ek zamanl\uc0\u305  d\'f6viz kurlar\u305  \'fczerinden **otomatik kur \'e7evirimi** (\'d6rn: TL -> USD) ile g\'fcvenli i\'e7 ve d\u305 \u351  transferler.\
* **Kredi \uc0\u304 \u351 lemleri:** Dinamik geri \'f6deme plan\u305  hesaplayan kredi ba\u351 vuru mod\'fcl\'fc.\
* **Vadeli Mevduat:** Anl\uc0\u305 k faiz getirisi hesaplama motoru.\
* **Dijital Dekont:** Yap\uc0\u305 lan her i\u351 lem i\'e7in sistem taraf\u305 ndan otomatik \'fcretilen resmi i\u351 lem dekontu.\
\
### \uc0\u55357 \u56508  Personel Paneli\
* **M\'fc\uc0\u351 teri Y\'f6netimi:** \u350 ube bazl\u305  m\'fc\u351 teri portf\'f6y\'fcn\'fc g\'f6r\'fcnt\'fcleme ve y\'f6netme.\
* **Kredi Operasyonlar\uc0\u305 :** Bekleyen kredi ba\u351 vurular\u305 n\u305  inceleme, onaylama veya reddetme.\
* **\uc0\u350 ube Denetimi:** \u350 ube bazl\u305  likidite ve m\'fc\u351 teri say\u305 lar\u305 n\u305  anl\u305 k izleme.\
\
### \uc0\u55357 \u56522  Y\'f6netici (Admin) Paneli\
* **Likidite Takibi:** Bankan\uc0\u305 n t\'fcm \u351 ubelerdeki toplam mevduat ve varl\u305 k durumunun anl\u305 k raporlanmas\u305 .\
* **Sistem \uc0\u304 zleme:** T\'fcm sistemdeki i\u351 lem loglar\u305 n\u305 n (Audit Logs) ve kullan\u305 c\u305  hareketlerinin canl\u305  ak\u305 \u351 \u305 .\
* **Performans Metrikleri:** Aktif m\'fc\uc0\u351 teri ve personel da\u287 \u305 l\u305 m\u305 n\u305 n g\'f6rsel analizi.\
\
---\
\
## \uc0\u55357 \u57056  Kullan\u305 lan Teknolojiler\
\
* **Backend:** PHP 8 (Nesne Y\'f6nelimli Programlama & PDO)\
* **Veritaban\uc0\u305 :** MySQL (\u304 li\u351 kisel \u350 ema, Stored Procedures, Triggerlar)\
* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript (Dinamik hesaplamalar i\'e7in)\
* **Ara\'e7lar:** VS Code, MAMP/XAMPP, phpMyAdmin\
\
---\
\
## \uc0\u55357 \u56772  Veritaban\u305  Yap\u305 s\u305 \
\
Proje, veri tekrar\uc0\u305 n\u305  en aza indirmek i\'e7in y\'fcksek d\'fczeyde normalize edilmi\u351  bir veritaban\u305  \u351 emas\u305 na sahiptir.\
\
* **Tablolar:** `Users`, `Customers`, `Employees`, `Accounts`, `Transactions`, `Loans`, `Branches`, `AuditLogs`.\
* **Mant\uc0\u305 k:** Veri tutarl\u305 l\u305 \u287 \u305 n\u305  sa\u287 lamak i\'e7in Foreign Key (Yabanc\u305  Anahtar) k\u305 s\u305 tlamalar\u305  kat\u305  bir \u351 ekilde uygulanm\u305 \u351 t\u305 r.\
\
---\
\
## \uc0\u9881 \u65039  Kurulum\
\
1.  **Projeyi Klonlay\uc0\u305 n**\
    ```bash\
    git clone [https://github.com/kullaniciadiniz/bank-of-istun.git](https://github.com/kullaniciadiniz/bank-of-istun.git)\
    ```\
\
2.  **Veritaban\uc0\u305  Kurulumu**\
    * `phpMyAdmin` paneline gidin.\
    * `banka_db_erhan` ad\uc0\u305 nda yeni bir veritaban\u305  olu\u351 turun.\
    * `sql/` klas\'f6r\'fc i\'e7indeki `banka_db.sql` dosyas\uc0\u305 n\u305  i\'e7e aktar\u305 n (Import).\
\
3.  **Konfig\'fcrasyon**\
    * `includes/db.php` dosyas\uc0\u305 n\u305  a\'e7\u305 n.\
    * Veritaban\uc0\u305  ba\u287 lant\u305  bilgilerinizi (`host`, `username`, `password`) kendi yerel sunucunuza g\'f6re g\'fcncelleyin.\
\
4.  **\'c7al\uc0\u305 \u351 t\u305 rma**\
    * Proje klas\'f6r\'fcn\'fc yerel sunucunuzun dizinine ta\uc0\u351 \u305 y\u305 n (MAMP/XAMPP i\'e7in `htdocs`).\
    * Taray\uc0\u305 c\u305 n\u305 zda `http://localhost/bank_of_istun` adresine gidin.\
\
---\
\
## \uc0\u55357 \u56424 \u8205 \u55357 \u56507  Geli\u351 tirici\
\
**Erhan \uc0\u304 M\u304 K**\
* Bilgisayar M\'fchendisli\uc0\u287 i \'d6\u287 rencisi \u304 stanbul Sa\u287 l\u305 k ve Teknoloji \'dcniversitesi\
* [LinkedIn Profilim](https://www.linkedin.com/in/erhan-imik-781809229/)\
\
**Te\uc0\u351 ekk\'fcr:**\
Proje geli\uc0\u351 tirme s\'fcrecindeki de\u287 erli katk\u305 lar\u305  ve yard\u305 mlar\u305  i\'e7in **O\u287 uz CANPOLAT**'a te\u351 ekk\'fcr ederim.\
\
---\
*Bu proje e\uc0\u287 itim ama\'e7l\u305  geli\u351 tirilmi\u351 tir.*\
\
}