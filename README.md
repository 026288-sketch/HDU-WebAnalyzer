# 🎓 АВТОМАТИЗОВАНА СИСТЕМА ЗБОРУ, АНАЛІЗУ ТА ОБРОБКИ ІНФОРМАЦІЇ ПРО ХЕРСОНСЬКИЙ ДЕРЖАВНИЙ УНІВЕРСИТЕТ В МЕРЕЖІ ІНТЕРНЕТ

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Python](https://img.shields.io/badge/Python-3.10+-blue.svg)](https://www.python.org)
[![Node.js](https://img.shields.io/badge/Node.js-18.x+-green.svg)](https://nodejs.org)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## 📋 Опис проекту

Веб-система для автоматизованого моніторингу, збору та аналізу інформації про Херсонський державний університет у мережі Інтернет. Проект забезпечує парсинг новинних джерел, аналіз контенту за допомогою AI, виявлення дублікатів та генерацію статистики згадувань.

### ✨ Основні можливості

- 🔍 **Автоматичний парсинг** новинних джерел за регулярними виразами
- 🤖 **AI-аналіз контенту** з використанням Google Gemini API
- 📊 **Виявлення дублікатів** через векторне подання тексту (sentence-transformers)
- 🏷️ **Автоматична генерація тегів** для статей
- 📈 **Статистика та графіки** згадувань у часі
- 🌐 **Puppeteer-інтеграція** для роботи з динамічними сайтами
- ⚙️ **Планувальник завдань** (Cron) для автоматизації
- 📝 **Детальне логування** всіх операцій

---

## 🛠️ Технічний стек

- **Backend**: Laravel 11.x (PHP 8.3+)
- **Frontend**: Blade, TailwindCSS
- **База даних**: MySQL
- **AI/ML**: Google Gemini API, sentence-transformers (Python)
- **Парсинг**: Puppeteer (Node.js), Readability (PHP), DOMDocument
- **Векторна БД**: ChromaDB
- **Автоматизація**: Cronical (Windows Cron)

---

## 📦 Передумови

### Необхідне ПЗ

- **Laragon** (рекомендована версія для Windows)
  - PHP 8.3.16+
  - MySQL 5.7+
  - Composer
- **Node.js** 18.x або новіше
- **Python** 3.10 або новіше
- **Git**
- **Google Gemini API Key** ([отримати тут](https://aistudio.google.com/app/apikey))

### Опціонально

- Visual Studio Code
- PhpMyAdmin (вбудований у Laragon)

---

## 🚀 Встановлення

### 1. Клонування репозиторію

#### Через Laragon Terminal

```bash
# Відкрийте Laragon > Terminal або правою кнопкою миші в треї > Laragon > Terminal
cd Ваш_диск:\laragon\www\

# Клонування проекту
git clone https://github.com/026288-sketch/HDU-WebAnalyzer.git
```

#### Через VS Code

1. Відкрийте VS Code
2. `Ctrl+Shift+P` → `Git: Clone`
3. Введіть URL: `https://github.com/026288-sketch/HDU-WebAnalyzer.git`
4. Оберіть папку `Ваш_диск:\laragon\www\`

---

### 2. Налаштування Laragon

**Важливо!** Налаштуйте Document Root для проекту:

1. Правою кнопкою миші на іконку Laragon в треї
2. `Preferences` → `General`
3. Встановіть **Document Root**: `Ваш_диск:\laragon\www\HDU-WebAnalyzer\public`
4. Погодьтеся на зміни (потрібні права адміністратора)
5. Laragon автоматично пропише hosts-файл

---

### 3. Автоматичне встановлення залежностей

Проект містить скрипт `setup.bat` для автоматичної установки всіх залежностей.

```bash
# Перейдіть в корінь проекту
cd Ваш_диск:\laragon\www\HDU-WebAnalyzer

# Запустіть скрипт установки
setup.bat
```

#### Що встановлює setup.bat:

- ✅ Composer залежності (Laravel)
- ✅ NPM пакети (включаючи Puppeteer)
- ✅ Python Virtual Environment (venv)
- ✅ ML-модель `sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2`
- ✅ Створення `.env` файлу з `.env.example`

⚠️ **Важливо**: 
- Завантаження може тривати 10-20 хвилин (залежить від швидкості інтернету)
- На кроці **"Run database migrations"** оберіть **N** (міграції запустимо пізніше)
- Скрипт можна перезапускати - він перевіряє наявність компонентів

#### Перевірка встановлення

Переконайтеся, що створені такі папки/файли:

```
✅ Ваш_диск:\laragon\www\HDU-WebAnalyzer\node_modules
✅ Ваш_диск:\laragon\www\HDU-WebAnalyzer\vendor
✅ Ваш_диск:\laragon\www\HDU-WebAnalyzer\similarity\.venv
✅ C:\Users\ВАШ_ЮЗЕР\.cache\huggingface\hub\models--sentence-transformers--paraphrase-multilingual-MiniLM-L12-v2
✅ C:\Users\ВАШ_ЮЗЕР\.cache\puppeteer
✅ Ваш_диск:\laragon\www\HDU-WebAnalyzer\.env
```

Якщо щось відсутнє - перезапустіть `setup.bat` (пропускаючи вже встановлені компоненти через `N`).

---

### 4. Налаштування .env файлу

Відредагуйте файл `.env` у корені проекту:

#### База даних

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel        # Або ваша назва БД
DB_USERNAME=root           # Або ваш користувач
DB_PASSWORD=               # Або ваш пароль
```

#### Google Gemini API

```env
GEMINI_API_KEY="ваш_ключ_тут"
GEMINI_BASE_URL=
GEMINI_REQUEST_TIMEOUT=30
```

⚠️ **Без API ключа аналіз статей працювати не буде!**

##### ⚡ Важливо про ліміти API (Rate Limits)

Система оптимізована під модель **`gemini-2.0-flash-exp`**. Для безкоштовного тарифу (**Free Tier**) діють такі обмеження:

- **RPM** (Requests Per Minute): ~15 запитів на хвилину
- **RPD** (Requests Per Day): ~1,500 запитів на день

> 💡 Ліміти можуть змінюватися Google. Перевіряйте актуальні дані в [Google AI Studio](https://aistudio.google.com/app/apikey).

**Програмні обмеження для безпеки:**

Щоб гарантовано уникнути помилки `429 Too Many Requests` при автоматичній роботі, у консольних командах встановлено жорстке програмне обмеження:

- ✅ **3 статті** за один запуск аналізу (`ConsoleAnalizeNodes`)
- ✅ **3 статті** за один запуск генерації тегів (`ConsoleGenerateTags`)

📊 **Сумарне навантаження**: 6 запитів за цикл - безпечно вкладається у хвилинний ліміт.

⚠️ **Не збільшуйте ці ліміти в коді, якщо ви використовуєте безкоштовний ключ!** Для обробки великих обсягів даних розгляньте можливість використання платного тарифу Google AI.

#### Python Service

```env
PYTHON_PATH=Ваш_диск:\laragon\www\HDU-WebAnalyzer\similarity\.venv\Scripts\python.exe
PYTHON_HOST=127.0.0.1
PYTHON_PORT=8000
PYTHON_LOG_LEVEL=info
CHROMA_TELEMETRY=0
```

⚠️ **Перевірте правильність шляху до Python!**

#### Puppeteer

```env
PUPPETEER_PORT=3000
```

#### Налаштування схожості

```env
SIM_THRESHOLD=0.92
SIM_THRESHOLD_SUMMARY=0.95
SIM_CHUNK_SIZE=500
SIM_MIN_CHUNK_RATIO=0.6
SIM_MIN_CHUNK_SIZE=0
SIM_USE_HYBRID=true
```

#### PM2 (опціонально)

```env
PM2_PATH=
```

---

### 5. Створення та налаштування бази даних

#### Варіант 1: Через консоль

```bash
# Підключення до MySQL
mysql -u root
# або з паролем
mysql -u root -p

# Створення БД
CREATE DATABASE laravel;
USE laravel;

# Імпорт даних
SOURCE Ваш_диск:/laragon/www/HDU-WebAnalyzer/storage/database.sql;

# Перевірка таблиць
SHOW TABLES;

# Вихід
EXIT;
```

#### Варіант 2: Через PhpMyAdmin

1. Відкрийте `http://localhost/phpmyadmin`
2. Створіть нову БД `laravel`
3. Імпортуйте файл `storage/database.sql`

---

### 6. Налаштування регулярного виразу

Відредагуйте файл `storage/app/regex.txt`:

**Тестовий варіант** (за замовчуванням):
```regex
/\bХерсон(а|у|ом|е|і)?\b/iu
```

**Продакшн варіант** (рекомендується):
```regex
/\b(?:ХДУ|Херсонськ(?:ий|ого|ому|им|а|е|і|у|ою)?\s?(?:державн(?:ий|ого|ому|им|а|е|і|у|ою)?\sуніверситет(?:у|ом|і)?|держуніверситет(?:у|ом|і)?))\b/iu
```

Це регулярне вираження використовується для фільтрації статей, що містять згадки про ХДУ.

---

## 🎯 Запуск системи

### 1. Перший вхід у систему

Відкрийте браузер і перейдіть за адресою:

```
http://localhost/
```

**Облікові дані за замовчуванням:**
- **Email**: `admin@example.com`
- **Пароль**: `admin`

⚠️ **Рекомендується змінити пароль після першого входу:**
```
http://localhost/profile
```

---

### 2. Запуск сервісів

Після входу ви потрапите на Dashboard: `http://localhost/dashboard`

**Обов'язково запустіть два сервіси:**

1. **Puppeteer Server** (порт 3000) - для парсингу динамічних сайтів
2. **Python Service** (порт 8000) - для AI-аналізу та виявлення дублікатів

Натисніть кнопку **▶️ Запустити всі сервіси**

⏳ **Зачекайте 30-60 секунд** для повного запуску обох серверів (особливо Python Service).

✅ Переконайтеся, що обидва сервіси показують статус **"Running"** (зелений колір).

⚠️ **Без цих сервісів система НЕ працюватиме!**

---

## 💻 Використання системи

### Веб-інтерфейс

#### 📰 Парсинг посилань (ручний режим)
```
http://localhost/parser/links
```
- Натисніть **Start links parsing** для збору посилань
- Потім **Parse Articles** для завантаження статей

#### 🌐 Джерела новин
```
http://localhost/sources
```
- Додайте нові джерела для моніторингу
- За замовчуванням додано 5 джерел
- ⚠️ Для роботи потрібен **Puppeteer Server**

#### 📄 Контент
```
http://localhost/content
```
- Перегляд зібраних статей
- Редагування/видалення статей
- Перехід до оригінальних джерел
- Очищення всієї бази

#### 📊 Логи
```
http://localhost/parser/logs
```
- Детальне логування всіх операцій
- Можливість очищення логів

#### 🧪 Тестування парсера
```
http://localhost/parser/test-parser
```

**Link Parser Test:**
- Source URL: `https://pivdenukraine.com.ua/`
- Regex pattern: `/\bХерсон(а|у|ом|е|і)?\b/iu`

**Full Content Parser Test:**
- Введіть URL статті
- Опція "Використовувати браузер" (потрібен Puppeteer)

#### 🔍 ChromaDB (дублікати)
```
http://localhost/chroma
```
- Перевірка тексту на дублікати
- Управління векторними ембедінгами
- ⚠️ Потрібен **Python Service**

---

### Консольні команди (Artisan)

Відкрийте Laragon Terminal і перейдіть у кореневу папку:

```bash
cd Ваш_диск:\laragon\www\HDU-WebAnalyzer
```

#### Основні команди парсингу

```bash
# Збір посилань зі всіх джерел
php artisan ConsoleParseLinks

# Завантаження повного контенту статей
php artisan ConsoleParseArticles

# Аналіз статей за допомогою AI (Gemini)
php artisan ConsoleAnalizeNodes

# Генерація тегів для статей
php artisan ConsoleGenerateTags

# Виконання всього робочого процесу
php artisan data:workflow
```

⚠️ **Примітка**: Немає сенсу запускати `ConsoleAnalizeNodes` та `ConsoleGenerateTags`, якщо немає зібраних статей.

#### Тестування статистики

Для перевірки роботи графіків згенеруйте тестові дані:

```bash
# Генерація статистики за 90 днів
php artisan stats:test --days=90
```

---

## ⏰ Налаштування автоматизації (Cron)

### Налаштування Cronical у Laragon

#### 1. Редагування Procfile

1. Правою кнопкою миші на іконку Laragon в треї
2. `Procfile` → відкрийте файл
3. Додайте в кінець файлу:

```
Cron: autorun "Cronical --console >NUL 2>&1" PWD=bin\cronical
```

4. Збережіть файл

#### 2. Налаштування завдань Cron

1. Правою кнопкою миші на іконку Laragon в треї
2. `Tools` → `Cron` → `cronical.dat`
3. Додайте рядок:

```
* * * * * cmd /c "D:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe D:\laragon\www\HDU-WebAnalyzer\artisan schedule:run"
```

⚠️ **Замініть шляхи на ваші!**

4. Збережіть файл
5. Перезапустіть Laragon

---

## 📁 Структура проекту

```
HDU-WebAnalyzer/
├── app/                    # Laravel додаток
├── bootstrap/              # Ініціалізація фреймворку
├── config/                 # Конфігураційні файли
├── database/               # Міграції та сіди
├── public/                 # Публічні файли (index.php)
├── resources/              # Views, CSS, JS
├── routes/                 # Маршрути
├── similarity/             # Python AI-модуль
│   ├── .venv/             # Віртуальне середовище
│   └── server.py          # FastAPI сервер
├── storage/
│   ├── app/
│   │   └── regex.txt      # Регулярний вираз для парсингу
│   ├── database.sql       # Дамп БД
│   └── logs/              # Лог-файли
├── node_modules/           # NPM пакети
├── vendor/                 # Composer пакети
├── .env                    # Конфігурація середовища
├── setup.bat               # Скрипт автоматичної установки
├── artisan                 # Laravel CLI
├── composer.json           # PHP залежності
└── package.json            # Node.js залежності
```

---

## 🔧 Усунення несправностей

### Сервіси не запускаються

**Puppeteer Server:**
```bash
# Перевірка наявності Puppeteer
cd Ваш_диск:\laragon\www\HDU-WebAnalyzer
npm list puppeteer
```

**Python Service:**
```bash
# Перевірка Python
Ваш_диск:\laragon\www\HDU-WebAnalyzer\similarity\.venv\Scripts\python.exe --version

# Перевірка залежностей
Ваш_диск:\laragon\www\HDU-WebAnalyzer\similarity\.venv\Scripts\pip.exe list
```

### Помилки з базою даних

```bash
# Перевірка підключення
mysql -u root -p

# Перевірка існування БД
SHOW DATABASES;

# Повторний імпорт
USE laravel;
SOURCE Ваш_диск:/laragon/www/HDU-WebAnalyzer/storage/database.sql;
```

### Помилка "GEMINI_API_KEY not set"

Переконайтеся, що в `.env` додано ключ:
```env
GEMINI_API_KEY="ваш_справжній_ключ"
```

Очистіть кеш конфігурації:
```bash
php artisan config:clear
php artisan cache:clear
```

### Помилки прав доступу (Windows)

Запустіть Laragon **від імені адміністратора**.

---

## 📚 Додаткова інформація

### API Endpoints

- **Dashboard**: `http://localhost/dashboard`
- **Парсер**: `http://localhost/parser/*`
- **Контент**: `http://localhost/content`
- **Джерела**: `http://localhost/sources`
- **ChromaDB**: `http://localhost/chroma`
- **API Документація**: *у розробці*

### Підтримувані формати

- **Статті**: HTML, JSON
- **Експорт**: CSV, JSON
- **Логи**: TXT, JSON

---

## 🤝 Внесок у розробку

Якщо ви знайшли помилку або маєте ідею для покращення:

1. Створіть Issue
2. Fork репозиторію
3. Створіть гілку: `git checkout -b feature/amazing-feature`
4. Commit: `git commit -m 'Add amazing feature'`
5. Push: `git push origin feature/amazing-feature`
6. Відкрийте Pull Request

---

## 📝 Ліцензія

Цей проект розповсюджується під ліцензією MIT. Детальніше: [LICENSE](LICENSE)

---

## 👥 Автори

Розроблено для **Херсонського державного університету**

---

## 📞 Підтримка

Якщо у вас виникли питання:

- 🐛 Issues: [GitHub Issues](https://github.com/026288-sketch/HDU-WebAnalyzer/issues)
- 📖 Wiki: [GitHub Wiki](https://github.com/026288-sketch/HDU-WebAnalyzer/wiki)

---

## ⚡ Швидкий старт (TL;DR)

```bash
# 1. Клонування
cd D:\laragon\www
git clone https://github.com/026288-sketch/HDU-WebAnalyzer.git

# 2. Установка
cd HDU-WebAnalyzer
setup.bat

# 3. Налаштування .env (DB + GEMINI_API_KEY)

# 4. БД
mysql -u root
CREATE DATABASE laravel;
USE laravel;
SOURCE D:/laragon/www/HDU-WebAnalyzer/storage/database.sql;
EXIT;

# 5. Відкрийте http://localhost/
# Логін: admin@example.com / admin

# 6. Запустіть сервіси на Dashboard
```

---

**Приємної роботи! 🚀**
