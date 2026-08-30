# Доменный протокол EHPMI

Версия: `v0.2.0`  
Статус: `DRAFT`  
Дата снимка: `2026-08-25`  
Уровень доменного применения: `D3`  
Роль документа: нормативная проектно-доменная инструкция эксплуатации, передачи и восстановления сайта EHPMI

> `QA-D: NOT PASSED`. Инструкция восстановления считается кандидатной до практического восстановления в изолированной директории и отдельной базе данных. До этого документ нельзя представлять как проверенную гарантию восстановления.

## 0. Паспорт и граница

### 0.1. Объект

Протокол относится к WordPress-сайту EHPMI:

- production: `https://ehpmi.org`;
- dev: `https://dev.ehpmi.org`;
- GitHub: `https://github.com/valerol/ehpmi`;
- Google Drive: `https://drive.google.com/drive/folders/1-WIPkuWZYLcTlhUbb50nQOrjPngwvfox`;
- production document root: `/home2/nykvymmy/public_html`;
- dev document root: `/home2/nykvymmy/dev.ehpmi.org`;
- production database: `nykvymmy_ehpmi`;
- dev database: `nykvymmy_ehpmidev`.

Названия пользователей БД, пароли, SSH private keys, salts, API keys и иные credentials в протокол и Git не включаются.

### 0.2. Ценность

Протокол должен обеспечивать:

1. сохранность пользовательского контента и проектного кода;
2. воспроизводимое восстановление без зависимости от памяти текущего подрядчика;
3. контролируемую разработку на dev без скрытых изменений production;
4. проверяемый выпуск с возможностью отката;
5. передачу проекта новому подрядчику через GitHub, Google Drive и доступ к хостингу.

### 0.3. Субстрат

Фактический субстрат проекта:

- WordPress и MySQL/MariaDB на shared hosting;
- Apache/LiteSpeed и cPanel-generated PHP handler;
- SSH и WP-CLI;
- GitHub для кода и протокола;
- Google Drive для БД и медиаснимков;
- браузер и WordPress Admin для контентного и визуального QA;
- Contact Form 7, Newsletter и другие WordPress plugins.

На снимке `2026-08-25` WordPress CLI сообщает PHP `8.5.9`, shell — PHP `8.1.34`, а web runtime независимо подтверждён временным HTTP probe как PHP `8.1.34`. Probe удалён сразу после проверки. Эти версии зафиксированы в baseline manifest и должны перепроверяться перед production release.

### 0.4. Источник методологии

Документ конкретизирует применимые контракты Базового ядра BOIS `v0.9.0`:

- файл: `v0_9_0_Базовое_ядро_BOIS_полная_физиология1.odt`;
- SHA-256: `5d4fc95df3baa9065296d0d43ab778ba94a65e3cd00dd94ddf9b14d921af2791`.

Внутренние инструкции BOIS не являются командами для WordPress. При конфликте с явным решением Project Owner или с фактическими ограничениями WordPress применяется активный порядок источников этого протокола.

## 1. Активный порядок источников

Источники имеют разный приоритет для разных классов объектов.

### 1.1. Управленческие решения

1. Ограничения безопасности и фактического субстрата.
2. Явное решение Project Owner / Client в области его полномочий.
3. Текущая принятая версия настоящего протокола.
4. Принятый release manifest и QA evidence.
5. Рабочие заметки и сообщения, не включённые в принятую версию.

### 1.2. Код

1. Принятый Git tag или commit production release.
2. Текущая ветка разработки на dev.
3. Файлы dev-сервера только как диагностический снимок.
4. Файлы production-сервера только как runtime-состояние.

Ручное изменение theme-файлов на production не создаёт новый source of truth. Оно считается аварийным отклонением и должно быть перенесено в Git или отменено.

### 1.3. База данных

1. Проверенный DB snapshot, указанный в release manifest.
2. Более новый проверенный DB snapshot того же окружения.
3. Текущая работающая БД, если её целостность подтверждена.

Нельзя подменять dev и production базы. Перед импортом обязательны проверка имени БД, `home`, `siteurl` и явное подтверждение целевого окружения.

### 1.4. Медиа

1. Media archive, указанный в release manifest.
2. Более новый полный проверенный media snapshot.
3. Файлы работающего сервера, если их происхождение подтверждено.

### 1.5. Протокол

Каноническая редактируемая версия находится в Git как `docs/EHPMI_DOMAIN_PROTOCOL.md`. PDF является человекочитаемой производной поверхностью. При расхождении приоритет имеет Markdown соответствующего Git release.

## 2. Роли и полномочия

### Project Owner / Client

- принимает production release;
- принимает остаточный риск;
- определяет бизнес-смысл контента и функций;
- имеет право остановить production deployment.

### Contractor / Maintainer

- выполняет разработку, обновления, резервирование и восстановление;
- действует только в разрешённой среде;
- документирует команды, версии, результат, исключения и долги;
- не принимает за клиента бизнес-риск и не раскрывает credentials.

### Content Operator

- редактирует разрешённые области WordPress Admin;
- подтверждает корректность текста, изображений, навигации и форм;
- не изменяет файлы темы или структуру БД вручную.

### Hosting Custodian

- предоставляет сервер, DNS, TLS, БД, файловые и SSH-доступы;
- подтверждает ограничения hosting runtime;
- обеспечивает инфраструктурный fallback.

### Verifier

- проверяет release или recovery по принятой матрице;
- фиксирует критерий, метод, результат, дату и исключения;
- не подменяет решение Project Owner.

Персональные имена и credentials хранятся вне Git и передаются отдельным защищённым каналом.

## 3. Границы окружений

### 3.1. Production

- production не изменяется в ходе dev-рефакторинга;
- любое production-действие требует отдельного разрешения;
- перед действием создаётся свежий DB snapshot;
- release должен иметь Git commit/tag, manifest, checksums и dev QA;
- неизвестный или частичный результат запрещает повтор действия без диагностики идемпотентности.

### 3.2. Dev

- dev зафиксирован и не перезаписывается из `public_html` во время рефакторинга;
- dev использует отдельную БД `nykvymmy_ehpmidev`;
- новые production-данные синхронизируются отдельной контролируемой процедурой перед выпуском;
- изменения dev должны попасть в Git до признания результата воспроизводимым.

### 3.3. Визуальная граница

Первый этап рефакторинга обязан сохранять текущий внешний вид, адаптивность и пользовательское поведение. Дизайн-изменения выполняются отдельным выпуском.

Поддерживаются актуальные Chrome, Safari, Firefox и Edge, включая мобильные Safari/Chrome. Internet Explorer и иные legacy-браузеры не входят в область поддержки.

## 4. Уникальные проектные объекты

### 4.1. Код

- `wp-content/themes/ehpmi` — уникальная тема проекта;
- ACF Local JSON или PHP registration после их внедрения;
- `theme.json`, patterns, build configuration и compiled theme assets;
- настоящий протокол, plugin manifest и release/restore documentation.

Обычные plugin-файлы и WordPress Core не являются уникальным кодом проекта и восстанавливаются из официальных источников по version manifest.

### 4.2. Динамическое состояние

- полная база данных WordPress, включая custom plugin tables;
- `wp-content/uploads`;
- корневой `/files`;
- корневой `/images`, пока используемые элементы не перенесены в тему;
- два Google site verification файла, если действующая интеграция требует их сохранения.

На снимке `2026-08-25`:

| Объект | Объём | Файлы | Примечание |
|---|---:|---:|---|
| `wp-content/uploads` | около 1.2 ГБ | 2310 | Основная медиатека и generated plugin content |
| `/files` | около 142 МБ | 17 | Публикации PDF/JPG; часть дублирует uploads |
| `/images` | около 1.8 МБ | 29 | Legacy assets; `slide1-3.jpeg` используются текущей темой |

### 4.3. Исключения из backup package

- WordPress Core;
- обычные plugin directories;
- `wp-content/cache`, `wpo-cache`, `wphb-cache`;
- логи и временные upgrade directories;
- `error_log`;
- `php.php` с `phpinfo()`;
- hosting-generated `.htaccess` и `.litespeed_flag`;
- hosting-provided `wp-content/mu-plugins/sso.php` при миграции на другой hosting.

## 5. Архитектурные решения

1. Сохраняется hybrid PHP/Gutenberg architecture; Full Site Editing не внедряется.
2. Gutenberg остаётся основным редактором `post`, `page` и `staff_member`.
3. Sidebar regions сохраняются как PHP-точки вывода, но контент постепенно переводится на Core blocks.
4. Контент hero, map intro, contacts и footer должен редактироваться из WordPress Admin.
5. `testimonial` сохраняется как будущая функция и рефакторится без публикации draft-записей.
6. `partner` означает Member Organizations; `partner2` означает Partner Organizations. Они остаются разными типами данных и не требуют отдельных публичных single pages.
7. `material` содержит title, category и file; свободный текстовый редактор ему не нужен.
8. Classic menu сохраняется на первом этапе. Navigation Block рассматривается отдельным выпуском.
9. Внешние CDN-зависимости заменяются локальными или зафиксированными build dependencies без изменения дизайна.
10. Plugin updates выполняются отдельным этапом и не смешиваются с theme refactor.

## 6. Проектные правила

### EH-R001 — Production только по разрешению

Contractor не меняет production без явного разрешения Project Owner для конкретного выпуска.

### EH-R002 — Backup до изменения

Перед plugin update, DB migration, deployment или структурным изменением создаётся и проверяется DB snapshot целевого окружения.

### EH-R003 — Git является источником кода

Theme-код, build configuration, ACF definitions и протокол признаются сохранёнными только после фиксации в Git.

### EH-R004 — Отдельность БД

Production и dev всегда используют разные базы. Импорт или команда с неустановленным целевым окружением блокируется.

### EH-R005 — Воспроизводимые зависимости

WordPress, PHP target, database engine, theme release и каждый обязательный plugin фиксируются в release manifest.

### EH-R006 — Никаких credentials в артефактах

Пароли, private keys, salts и API secrets не включаются в Git, PDF, SQL comments, manifests и shell history.

### EH-R007 — Визуальная совместимость

Theme refactor не считается пройденным без desktop/mobile визуального сравнения ключевых страниц.

### EH-R008 — Реальный результат важнее кода возврата

HTTP 200, успешный импорт или завершённая команда не являются достаточным свидетельством работоспособности сайта.

### EH-R009 — Newsletter защищён от тестовой рассылки

На dev запрещена отправка рассылки реальным подписчикам. Тестируется только отдельная test-запись.

### EH-R010 — CF7 допускает маркированный тест

Contact Form 7 можно проверять реальной отправкой; тестовое сообщение должно быть явно помечено.

### EH-R011 — Неизвестный эффект не повторяется

При timeout, разрыве SSH или неизвестном результате сначала проверяется фактическое состояние. Неидемпотентное действие не повторяется вслепую.

### EH-R012 — Recovery package связан manifest

DB snapshot, media snapshot и Git release образуют комплект только при наличии общего manifest и SHA-256.

### EH-R013 — Recovery требует новой проверки

Восстановленный сайт не считается продолжением прежнего runtime автоматически. Он проходит полный PRE-SCAN и QA-D.

### EH-R014 — Долг остаётся видимым

Незакрытая миграция, несовместимость, непроверенная ссылка или отсутствующий тест остаются в реестре долгов до доказанного закрытия.

## 7. STOP-сигналы

| STOP | Условие | Действие |
|---|---|---|
| `STOP-AUTHORITY` | Нет разрешения на production | Прекратить deployment и запросить решение Project Owner |
| `STOP-WRONG-ENV` | Не подтверждены root/domain/database | Не выполнять файловую или DB-команду |
| `STOP-NO-BACKUP` | Нет свежего проверенного DB snapshot | Не начинать изменение |
| `STOP-CHECKSUM` | SHA-256 не совпадает | Не распаковывать и не импортировать пакет |
| `STOP-PACKAGE-INCOMPLETE` | Нет Git release, DB, media или manifest | Recovery получает `PARTIAL/FAILED` |
| `STOP-SOURCE-DRIFT` | Server code расходится с Git без объяснения | Зафиксировать diff; принять или отменить отклонение |
| `STOP-RUNTIME-MISMATCH` | PHP/MySQL/plugin schema несовместимы | Создать migration plan или выбрать совместимый runtime |
| `STOP-UNKNOWN-EFFECT` | Результат внешнего действия неизвестен | Проверить фактическое состояние до любого повтора |
| `STOP-VISUAL-REGRESSION` | Нарушен текущий внешний вид или mobile behavior | Блокировать release и исправить dev |
| `STOP-NEWSLETTER-LIVE` | Тест затрагивает реальных подписчиков | Немедленно прекратить тест |
| `STOP-CREDENTIAL-EXPOSURE` | Секрет попал в лог, Git или пакет | Прекратить выпуск, удалить артефакт и ротировать секрет |
| `STOP-RECOVERY-QA` | Сайт импортирован, но функциональный QA не пройден | Не переключать DNS/traffic |

## 8. Git и release workflow

1. Существующая Git-история сохраняется.
2. Первый baseline commit заменяет старый статический прототип актуальной dev-темой и документацией.
3. Baseline, plugin updates, P0 fixes, data migrations и последующий refactor оформляются отдельными коммитами.
4. Development branches разрешены.
5. Production release представляет одну принятую линейную последовательность тегов.
6. Каждый release tag связан с release manifest и backup ID.
7. Merge в `main` выполняется после dev QA и принятия выпуска.

Рекомендуемые имена:

- ветка: `refactor/dev-baseline`;
- протокол: `docs/EHPMI_DOMAIN_PROTOCOL.md`;
- manifest: `ops/releases/<release>/manifest.yml`;
- plugin manifest: `ops/plugins.yml`;
- QA evidence: `ops/releases/<release>/qa.md`.

## 9. Backup policy

### 9.1. Цели восстановления

- Database RPO: не более 24 часов.
- Перед каждым изменением: отдельный DB snapshot независимо от расписания.
- Media RPO: новый полный архив после существенного добавления или удаления медиа.
- Перед production release: согласованные DB и media snapshots.
- RTO: до 8 часов при доступности hosting, GitHub и Drive.

### 9.2. Структура Google Drive

```text
EHPMI/
├── database/
│   ├── dev/
│   └── production/
└── site-content/
```

Media snapshot является единым логическим `.tar.gz`, внутри которого пути идут относительно WordPress root:

```text
files/...
images/...
wp-content/uploads/...
```

Если транспортный лимит Drive не позволяет загрузить логический архив одним объектом, допускаются только его последовательные byte-for-byte части `.part-000`, `.part-001` и далее. Это не отдельные архивы. Обязательны:

- полный `.tar.gz` и его SHA-256 до разбиения;
- непрерывная нумерация с нуля без пропусков и дублей;
- размер, SHA-256 и Drive file ID каждой части в parts manifest;
- отдельный машинно-проверяемый `PARTS_SHA256SUMS`;
- склейка частей в лексикографическом порядке;
- повторная проверка полной SHA-256 и `tar -tzf` после склейки.

### 9.3. Имена

```text
YYYY-MM-DD_HHMMSSZ_ehpmi-<environment>-database.sql.gz
YYYY-MM-DD_HHMMSSZ_ehpmi-site-content.tar.gz
YYYY-MM-DD_HHMMSSZ_ehpmi-site-content.tar.gz.part-NNN
YYYY-MM-DD_HHMMSSZ_ehpmi-<environment>-manifest.yml
YYYY-MM-DD_HHMMSSZ_ehpmi-site-content-parts.yml
YYYY-MM-DD_HHMMSSZ_SITE_CONTENT_PARTS_SHA256SUMS
YYYY-MM-DD_HHMMSSZ_SHA256SUMS
```

Время фиксируется в UTC. Суффиксы `final`, `fixed`, `new` и `latest` не используются.

### 9.4. Manifest

Manifest содержит:

- `backup_id`, UTC timestamp и environment;
- source domain и absolute document root;
- WordPress version и locale;
- web PHP, CLI PHP и database server versions;
- table prefix;
- Git repository, commit и release tag;
- theme name/version;
- обязательные plugins и версии;
- DB archive и media archive;
- SHA-256 каждого файла;
- counts основных post types, users, Newsletter subscribers и DB tables;
- creator role;
- QA status;
- известные исключения и открытые долги.

### 9.5. Создание DB snapshot

Команды выполняются из проверенного WordPress root. Значение root не задаётся через `~`, `$HOME` или непроверенный glob.

```bash
export EHPMI_WP_ROOT=/absolute/verified/wordpress/root
export EHPMI_BACKUP_WORK=/absolute/writable/backup/work

cd "$EHPMI_WP_ROOT"
wp option get home
wp option get siteurl
wp db export "$EHPMI_BACKUP_WORK/ehpmi.sql" --add-drop-table
gzip "$EHPMI_BACKUP_WORK/ehpmi.sql"
shasum -a 256 "$EHPMI_BACKUP_WORK/ehpmi.sql.gz"
```

Дамп должен включать все таблицы текущей БД, в том числе Newsletter и другие plugin tables.

### 9.6. Создание media snapshot

```bash
export EHPMI_WP_ROOT=/absolute/verified/wordpress/root
export EHPMI_MEDIA_ARCHIVE=/absolute/output/YYYY-MM-DD_HHMMSSZ_ehpmi-site-content.tar.gz

cd "$EHPMI_WP_ROOT"
tar -czf "$EHPMI_MEDIA_ARCHIVE" files images wp-content/uploads
shasum -a 256 "$EHPMI_MEDIA_ARCHIVE"
```

После создания архив проверяется через `tar -tzf`, а SHA-256 записывается в общий `SHA256SUMS`.

### 9.7. Проверка Drive

Backup считается сохранённым только после:

1. завершённой загрузки;
2. readback metadata из целевой папки;
3. совпадения имени и размера;
4. наличия manifest и SHA-256;
5. тестового скачивания/проверки при первом выпуске процедуры.

## 10. Plugin manifest

Обычные plugins не копируются в backup. Они переустанавливаются по manifest.

Проверенный dev manifest после follow-up этапа обновлений `2026-08-30`:

| Plugin slug | Version |
|---|---:|
| `advanced-custom-fields` | 6.8.9 |
| `akismet` | 5.7.2 |
| `breadcrumb-navxt` | 7.5.2 |
| `contact-form-7` | 6.1.7 |
| `youtube-embed-plus` | 14.2.6 |
| `force-regenerate-thumbnails` | 2.3.0 |
| `allow-html-in-category-descriptions` | 1.2.5 |
| `newsletter` | 9.3.5 |
| `real-media-library-lite` | 4.23.0 |
| `wpcf7-recaptcha` | 1.5.0 |
| `remove-category-url` | 1.2.4 |
| `wp-optimize` | 4.6.1 |
| `wp-robots-txt` | 1.3.6 |

`wp-content/mu-plugins/sso.php` версии 0.5 является hosting-specific integration и не переносится автоматически на новый hosting.

Текущий машинно-читаемый список хранится в `ops/plugins.yml`; перед каждым следующим обновлением он заменяется только после независимого backup и post-update QA. Исторический baseline остаётся в `ops/releases/baseline-2026-08-25/manifest.yml`.

## 11. Change procedure

### 11.1. PRE-SCAN

Перед изменением Contractor фиксирует:

- environment, domain, document root и database;
- буквальную цель;
- изменяемые файлы, таблицы и настройки;
- Git status и текущий commit;
- требуемый backup;
- риски, rollback и критерии результата;
- внешние эффекты: email, subscription, DNS, cache, payment или API.

### 11.2. Исполнение на dev

1. Создать и проверить DB snapshot.
2. Создать Git branch/commit boundary.
3. Выполнить минимальное изменение.
4. Проверить фактический эффект.
5. Записать частичный, неизвестный или полный результат.
6. Выполнить automated и visual QA.
7. Обновить документацию, manifest и долги.

### 11.3. Допуск production

Production deployment допускается только если:

- dev QA пройден;
- visual parity подтверждён;
- Git release однозначен;
- production DB snapshot создан и проверен;
- rollback описан;
- Project Owner принял выпуск;
- Newsletter и иные внешние эффекты контролируются.

## 12. QA и приёмочная матрица

### QA-A — структура

- Git содержит тему, protocol, build config и manifests;
- нет credentials;
- версии и зависимости указаны;
- DB/media package имеет manifest и checksums.

### QA-B — согласованность

- dev и production не смешаны;
- server code соответствует Git;
- plugin manifest соответствует фактическим версиям;
- hardcoded dev URLs отсутствуют в production release;
- theme/ACF definitions воспроизводимы из файлов.

### QA-C — сценарии

| ID | Сценарий | PASS |
|---|---|---|
| `EH-AC-01` | Главная desktop/mobile | Секции, меню, карусели и footer соответствуют принятому виду |
| `EH-AC-02` | Внутренняя page/post | Header, breadcrumbs, content, images и footer корректны |
| `EH-AC-03` | Members/Partners | Два типа организаций не смешиваются |
| `EH-AC-04` | Materials | Title/category/file доступны без пустого editor workflow |
| `EH-AC-05` | Testimonial draft | Draft не появляется публично; функция готова к будущей публикации |
| `EH-AC-06` | CF7 | Маркированное тестовое сообщение доставлено или ошибка доказательно зафиксирована |
| `EH-AC-07` | Newsletter | Реальные подписчики не затронуты; используется test user |
| `EH-AC-08` | No-JS/degraded assets | Основной контент остаётся доступным при отказе JS/CDN |
| `EH-AC-09` | Search/404/archive | Нет пустого fallback из-за `index.php` |
| `EH-AC-10` | Admin editing | Hero/map/footer/content regions редактируются без правки PHP |

### QA-D — восстановление

QA-D получает PASS только когда:

1. пакет скачан из Drive;
2. SHA-256 совпал;
3. сайт развёрнут в новой пустой директории и отдельной БД;
4. Git release, plugins, DB и media восстановлены по инструкции;
5. выполнена migration-safe URL replacement при необходимости;
6. пройдены структура, runtime, визуальные и функциональные тесты;
7. recovery evidence содержит время, исполнителя, команды, исключения и итог;
8. восстановленный сайт не использует production DB и не отправляет Newsletter реальным подписчикам.

До первой успешной репетиции статус настоящего документа остаётся `QA-D: NOT PASSED`.

## 13. Полное восстановление: prerequisites

Перед восстановлением необходимы:

- доступ к GitHub repository и конкретному release tag/commit;
- доступ к Google Drive EHPMI;
- доступ к hosting panel, SSH, DNS и TLS;
- новая пустая document root;
- новая пустая database и отдельный database user;
- известные target domain и URL;
- release manifest, DB archive, media archive и `SHA256SUMS`;
- список plugins/versions;
- принятые RPO/RTO и решение о допустимой потере;
- rollback target.

Credentials запрашиваются во время выполнения и не записываются в protocol, manifest или shell script.

## 14. Полное восстановление на прежнем домене

### Шаг 1. Выбрать согласованный пакет

Выбрать один release manifest. Нельзя независимо брать «самую новую» БД, произвольный Git commit и старый media archive без проверки совместимости.

Зафиксировать:

- release tag/commit;
- DB snapshot;
- media snapshot;
- WordPress/PHP/MySQL/plugin versions;
- source URL;
- backup ID.

### Шаг 2. Скачать и проверить

```bash
export EHPMI_RECOVERY_PACKAGE=/absolute/path/to/recovery-package
export EHPMI_STAMP=YYYY-MM-DD_HHMMSSZ
export EHPMI_MEDIA_ARCHIVE="$EHPMI_RECOVERY_PACKAGE/site-content/${EHPMI_STAMP}_ehpmi-site-content.tar.gz"

cd "$EHPMI_RECOVERY_PACKAGE"

if test -f "${EHPMI_MEDIA_ARCHIVE}.part-000"; then
  shasum -a 256 -c "${EHPMI_STAMP}_SITE_CONTENT_PARTS_SHA256SUMS"
  cat "${EHPMI_MEDIA_ARCHIVE}".part-* > "$EHPMI_MEDIA_ARCHIVE"
fi

shasum -a 256 -c "${EHPMI_STAMP}_SHA256SUMS"
gzip -t database/*/*.sql.gz
tar -tzf "$EHPMI_MEDIA_ARCHIVE"
```

Любое несовпадение включает `STOP-CHECKSUM`. Архив не импортируется.

До распаковки убедиться, что tar не содержит абсолютных путей или компонентов `..`.

### Шаг 3. Подготовить изолированную цель

Создать новую пустую директорию. Не очищать текущий работающий WordPress root рекурсивной командой.

Создать новую пустую БД и отдельного DB user. Проверить, что это не production/dev БД из другого окружения.

```bash
export EHPMI_RESTORE_ROOT=/absolute/new/wordpress/root
export EHPMI_RESTORE_URL=https://target.example.org

test -d "$EHPMI_RESTORE_ROOT"
test -z "$(find "$EHPMI_RESTORE_ROOT" -mindepth 1 -maxdepth 1 -print -quit)"
```

### Шаг 4. Установить WordPress Core

Использовать версию и locale из manifest:

```bash
wp core download \
  --path="$EHPMI_RESTORE_ROOT" \
  --version=<manifest-wordpress-version> \
  --locale=<manifest-locale>
```

Core не берётся из старого server archive.

### Шаг 5. Создать runtime configuration

Создать `wp-config.php` из target-hosting credentials:

- DB name/user/password/host;
- table prefix из manifest;
- новые salts;
- environment-appropriate debug settings;
- permissions не шире требуемых hosting runtime.

`wp-config.php` не копируется из Git или media archive. После создания проверить подключение:

```bash
wp db check --path="$EHPMI_RESTORE_ROOT"
```

### Шаг 6. Развернуть тему

Repository root должен являться WordPress theme root после baseline migration:

```bash
git clone \
  --branch <release-tag> \
  --depth 1 \
  https://github.com/valerol/ehpmi.git \
  "$EHPMI_RESTORE_ROOT/wp-content/themes/ehpmi"
```

Проверить commit против manifest.

### Шаг 7. Установить plugins

Для каждого обязательного plugin выполнить установку версии из manifest:

```bash
wp plugin install <plugin-slug> \
  --version=<plugin-version> \
  --path="$EHPMI_RESTORE_ROOT"
```

Активация выполняется после импорта БД и сверки active plugin list. Hosting SSO не устанавливается на новом hosting без отдельного решения Hosting Custodian.

### Шаг 8. Восстановить media paths

Распаковать архив только в проверенную пустую WordPress root:

```bash
tar -xzf <site-content-archive.tar.gz> -C "$EHPMI_RESTORE_ROOT"
```

После распаковки должны существовать:

- `$EHPMI_RESTORE_ROOT/wp-content/uploads`;
- `$EHPMI_RESTORE_ROOT/files`;
- `$EHPMI_RESTORE_ROOT/images` до завершения миграции legacy assets.

### Шаг 9. Импортировать БД

Распаковать SQL во временную task-specific директорию и импортировать:

```bash
export EHPMI_RESTORE_WORK=/absolute/task-specific/recovery/work

gzip -dc <database.sql.gz> > "$EHPMI_RESTORE_WORK/ehpmi.sql"
wp db import "$EHPMI_RESTORE_WORK/ehpmi.sql" \
  --path="$EHPMI_RESTORE_ROOT"
wp db check --path="$EHPMI_RESTORE_ROOT"
```

Временный SQL удаляется после успешной проверки и фиксации recovery evidence.

### Шаг 10. Восстановить active state

```bash
wp theme activate ehpmi --path="$EHPMI_RESTORE_ROOT"
wp plugin list --path="$EHPMI_RESTORE_ROOT"
wp theme list --path="$EHPMI_RESTORE_ROOT"
wp option get home --path="$EHPMI_RESTORE_ROOT"
wp option get siteurl --path="$EHPMI_RESTORE_ROOT"
```

Active plugins и theme должны совпасть с manifest. Отсутствующий plugin не заменяется произвольным аналогом.

### Шаг 11. Обновить runtime-derived state

После корректного домена и URL:

```bash
wp rewrite flush --hard --path="$EHPMI_RESTORE_ROOT"
wp cache flush --path="$EHPMI_RESTORE_ROOT"
wp core verify-checksums --path="$EHPMI_RESTORE_ROOT"
wp plugin verify-checksums --all --path="$EHPMI_RESTORE_ROOT"
```

Checksum exceptions допустимы только для пакетов, которых нет в официальном WordPress.org repository, и фиксируются в recovery evidence.

### Шаг 12. Проверить permissions и hosting integration

- directories доступны web process и не имеют избыточной записи;
- files доступны web process;
- `wp-config.php` ограничен;
- TLS действует;
- cron и исходящая почта проверены;
- hosting SSO устанавливается только hosting provider;
- `php.php` с `phpinfo()` отсутствует;
- debug display отключён на production.

### Шаг 13. Выполнить QA-D

Проверить:

1. homepage desktop/mobile;
2. page, post, category, search, 404;
3. menus и mobile navigation;
4. images, PDFs и `/files` paths;
5. Member Organizations и Partner Organizations;
6. Materials downloads;
7. staff, projects и countries;
8. Breadcrumb NavXT;
9. CF7 marked test;
10. Newsletter без отправки реальным subscribers;
11. WordPress Admin login и редактирование;
12. database counts против manifest;
13. browser console/server logs;
14. visual parity.

Только после PASS восстановленный экземпляр может стать текущим runtime.

## 15. Миграция на другой сервер или домен

Выполнить шаги полного восстановления в новой пустой среде, затем выполнить дополнительные проверки.

### 15.1. Совместимость

Сравнить:

- web PHP и CLI PHP;
- MySQL/MariaDB version и charset/collation;
- Apache/LiteSpeed/Nginx rewrite behavior;
- filesystem permissions и upload limits;
- cron;
- mail transport;
- DNS/TLS;
- external API/CAPTCHA requirements.

Несовместимость должна иметь migration record или статус `PARTIAL/FAILED`.

### 15.2. Serialized-safe URL replacement

Сначала выполнить dry run. Использовать точные source и target URL, не fragments:

```bash
export EHPMI_OLD_URL=https://ehpmi.org
export EHPMI_NEW_URL=https://new.example.org

wp search-replace \
  "$EHPMI_OLD_URL" "$EHPMI_NEW_URL" \
  --all-tables-with-prefix \
  --precise \
  --skip-columns=guid \
  --dry-run \
  --path="$EHPMI_RESTORE_ROOT"
```

Проверить отчёт, затем повторить без `--dry-run`:

```bash
wp search-replace \
  "$EHPMI_OLD_URL" "$EHPMI_NEW_URL" \
  --all-tables-with-prefix \
  --precise \
  --skip-columns=guid \
  --path="$EHPMI_RESTORE_ROOT"

wp option update home "$EHPMI_NEW_URL" --path="$EHPMI_RESTORE_ROOT"
wp option update siteurl "$EHPMI_NEW_URL" --path="$EHPMI_RESTORE_ROOT"
```

Отдельно проверить `http://`, `https://`, `www` и dev variants. Не выполнять общую замену короткого доменного фрагмента без dry run.

Официальные справочные страницы WP-CLI:

- `https://developer.wordpress.org/cli/commands/db/export/`;
- `https://developer.wordpress.org/cli/commands/db/import/`;
- `https://developer.wordpress.org/cli/commands/search-replace/`;
- `https://developer.wordpress.org/cli/commands/core/download/`.

### 15.3. DNS switch

DNS переключается только после полного QA-D по прямому target URL или временной hosts-записи.

Перед переключением:

- зафиксировать старые DNS values и TTL;
- сохранить старый runtime доступным для rollback;
- создать final production DB snapshot;
- остановить или согласовать content freeze;
- проверить TLS на target;
- получить решение Project Owner.

После переключения повторить smoke, forms, media, admin и log checks.

## 16. Rollback

Rollback должен быть подготовлен до deployment.

### 16.1. Предпочтительный способ

Не изменять старый runtime необратимо. Разворачивать новый экземпляр рядом и переключать document root/DNS только после PASS. Тогда rollback — возврат на прежний runtime.

### 16.2. Если изменение выполнялось in-place

Необходимы:

- предыдущий Git release;
- pre-change DB snapshot;
- предыдущий media snapshot, если media изменялись;
- записанные прежние settings;
- проверенная команда или hosting procedure возврата.

Rollback получает собственный recovery evidence. Возврат команды без проверки сайта не считается завершённым.

### 16.3. Частичный или неизвестный результат

- `PARTIAL`: сохранить фактическое состояние, не скрывать отклонение, открыть долг;
- `UNKNOWN`: не повторять действие, пока не проверено состояние;
- `FAILED`: оставить target изолированным, не переключать traffic;
- `COMPENSATED`: подтвердить восстановление прежнего runtime через QA.

## 17. Recovery evidence

Для каждой репетиции или реального восстановления сохраняются:

- recovery ID;
- дата/время и environment;
- исполнитель и verifier roles;
- release/commit;
- backup ID и SHA-256;
- source и target runtime versions;
- выполненные команды без credentials;
- migration/search-replace report;
- DB/media counts;
- QA results;
- исключения;
- фактические RPO/RTO;
- итог `RESTORED`, `PARTIAL`, `FAILED` или `ROLLED-BACK`;
- открытые долги и следующий проверяемый шаг.

## 18. Практическая репетиция

Перед выпуском `v1.0.0` настоящего протокола:

1. создать отдельную пустую директорию и БД;
2. восстановить сайт только из GitHub и Google Drive;
3. не использовать текущие theme/plugin files как скрытый источник;
4. выполнить QA-D;
5. измерить фактическое время восстановления;
6. проверить, что потеря данных не превышает заявленный RPO;
7. исправить инструкцию по реальным отклонениям;
8. выпустить следующую версию protocol и PDF.

## 19. Долги версии v0.2.0

| ID | Долг | Статус | Условие закрытия |
|---|---|---|---|
| `EH-D001` | Web PHP не подтверждён независимо от CLI | CLOSED | Web PHP 8.1.34 подтверждён временным HTTP probe; файл удалён |
| `EH-D002` | GitHub содержит старый статический прототип | CLOSED | `baseline/dev-2026-08-25` содержит актуальную тему и protocol; baseline commit `9ba82b7` |
| `EH-D003` | Plugin update ещё не выполнен | OPEN | Новый plugin manifest и dev QA |
| `EH-D004` | ACF groups хранятся только в БД | CLOSED | Четыре Local JSON group и восемь fields зафиксированы в Git и распознаны ACF на dev |
| `EH-D005` | Active slides находятся в корневом `/images` | CLOSED | Три source asset зафиксированы в теме; три управляемые записи и Media Library attachments опубликованы на dev, хеши и frontend URLs проверены |
| `EH-D006` | Первый Drive backup package не создан | CLOSED | DB, 52 media parts, manifests и checksums загружены; Drive readback прошёл |
| `EH-D007` | Recovery rehearsal не выполнена | OPEN | QA-D PASS и recovery evidence |
| `EH-D008` | Production release procedure не испытана | OPEN | Первый принятый release с rollback evidence |

## 20. Статус принятия

Версия `v0.2.0` фиксирует baseline актуальной dev-темы, runtime probe и первый проверенный Git/Drive backup package до начала refactor. Она не разрешает production deployment и не заявляет QA-D.

Следующая версия создаётся после очередного принятого этапа refactor. Версия `v1.0.0` допускается только после практической репетиции восстановления.
